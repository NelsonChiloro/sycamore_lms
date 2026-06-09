const moment = require('moment');
const fs = require('fs');
const path = require('path');
const {
    sqlBranchJoin,
    determineRBMClassification,
    getOfficerIdsUnderSupervisor,
    sqlRelationshipSupervisorNameExpr
} = require('./databaseHelpers');
/**
 * Generate an RBM Loan Classification Report HTML
 *
 * @param {Object} filterOptions - Filter parameters for the report
 * @param {number} reportId - The ID of the report record
 * @param {Object} reportTrackers - Object tracking report generation progress
 * @param {Object} db - Database connection
 * @returns {Promise<string>} - HTML content of the report
 */
async function generateRBMClassificationReport(filterOptions, reportId, reportTrackers, baseurl, db) {
    console.log('====== RBM LOAN CLASSIFICATION REPORT GENERATION STARTED ======');
    console.log(`Report ID: ${reportId}`);
    console.log(`Filters: ${JSON.stringify(filterOptions)}`);

    // Set initial progress
    reportTrackers[reportId].percentage = 5;

    try {
        // Get current date for report
        const currentDate = moment().format('YYYY-MM-DD');

        // Calculate total portfolio size
        reportTrackers[reportId].percentage = 10;
        console.log('Calculating total portfolio size...');

        const portfolioResult = await new Promise((resolve, reject) => {
            db.query(`
                SELECT SUM(loan_principal) as total_portfolio
                FROM loan
                WHERE loan_status IN ('APPROVED', 'ACTIVE') 
                AND disbursed = 'Yes'
            `, (err, results) => {
                if (err) return reject(err);
                resolve(results[0]?.total_portfolio || 0);
            });
        });

        const totalPortfolio = parseFloat(portfolioResult);
        console.log(`Total portfolio size: ${formatCurrency(totalPortfolio)}`);

        // Build where conditions for the query based on filters
        let whereConditions = [];
        let params = [];

        // Basic condition for active loans
        whereConditions.push('l.loan_status IN (?, ?)');
        params.push('APPROVED', 'ACTIVE');
        whereConditions.push('l.disbursed = ?');
        params.push('Yes');

        // Apply branch filter if not 'All'
        if (filterOptions.branch && filterOptions.branch !== 'All') {
            whereConditions.push(`(
                l.branch = ?
                OR l.branch IN (SELECT Code FROM branches WHERE id = ?)
                OR l.branch IN (SELECT BranchCode FROM branches WHERE id = ?)
            )`);
            params.push(filterOptions.branch, filterOptions.branch, filterOptions.branch);
        }

        if (filterOptions.supervisor && filterOptions.supervisor !== 'All') {
            const officerIds = await getOfficerIdsUnderSupervisor(filterOptions.supervisor);
            if (!officerIds.length) {
                whereConditions.push('1=0');
            } else {
                whereConditions.push(`l.loan_added_by IN (${officerIds.join(',')})`);
            }
        } else if (filterOptions.officer && filterOptions.officer !== 'All') {
            whereConditions.push('l.loan_added_by = ?');
            params.push(filterOptions.officer);
        }

        // Apply product filter if not 'All'
        if (filterOptions.product && filterOptions.product !== 'All') {
            whereConditions.push('l.loan_product = ?');
            params.push(filterOptions.product);
        }

        // Combine conditions
        const whereClause = whereConditions.length > 0
            ? 'WHERE ' + whereConditions.join(' AND ')
            : '';

        // Query to get loans with basic information
        reportTrackers[reportId].percentage = 20;
        console.log('Fetching loan data...');

        const query = `
            SELECT 
                l.loan_id, l.loan_number, l.loan_customer, l.customer_type,
                l.loan_date, l.loan_principal, l.disbursed_date,
                l.loan_product, l.loan_added_by, l.branch,
                p.product_name,
                e.Firstname as officer_firstname, e.Lastname as officer_lastname,
                ${sqlRelationshipSupervisorNameExpr('rel_sup')} as relationship_supervisor,
                b.BranchName
            FROM loan l
            LEFT JOIN loan_products p ON l.loan_product = p.loan_product_id
            LEFT JOIN employees e ON l.loan_added_by = e.id
            LEFT JOIN employees rel_sup ON rel_sup.id = e.Supervisor
            ${sqlBranchJoin('l', 'b')}
            ${whereClause}
        `;

        const loans = await new Promise((resolve, reject) => {
            db.query(query, params, (err, results) => {
                if (err) return reject(err);
                resolve(results);
            });
        });

        console.log(`Found ${loans.length} active loans`);
        reportTrackers[reportId].percentage = 30;

        // Process each loan to get payment details and days in arrears
        const processedLoans = [];
        const classificationSummary = {
            standard: { count: 0, amount: 0, provision_rate: 0.0 },
            special_mention: { count: 0, amount: 0, provision_rate: 0.20 },
            substandard: { count: 0, amount: 0, provision_rate: 0.50 },
            doubtful: { count: 0, amount: 0, provision_rate: 0.75 }, // Changed from 0.50 to 0.75
            loss: { count: 0, amount: 0, provision_rate: 1.00 }
        };

        let processedCount = 0;
        const totalLoans = loans.length;

        for (const loan of loans) {
            processedCount++;
            console.log(`Processing loan ${processedCount}/${totalLoans}: ${loan.loan_number}`);

            // Update progress percentage based on processed loans
            const processedPercentage = 30 + Math.floor((processedCount / totalLoans) * 60);
            reportTrackers[reportId].percentage = processedPercentage;

            try {
                // Get payment schedules for this loan
                const paymentSchedules = await new Promise((resolve, reject) => {
                    db.query(`
                        SELECT 
                            id, payment_schedule, payment_number, amount, principal, status, paid_amount,
                            DATEDIFF(CURRENT_DATE(), payment_schedule) as days_past_due
                        FROM payement_schedules
                        WHERE loan_id = ?
                        ORDER BY payment_schedule ASC
                    `, [loan.loan_id], (err, results) => {
                        if (err) return reject(err);
                        resolve(results);
                    });
                });

                // Calculate outstanding principal
                const outstandingPrincipal = await calculateOutstandingPrincipal(db, loan.loan_id);

                // Find the oldest unpaid payment to determine days in arrears
                const unpaidPayments = paymentSchedules.filter(
                    p => p.status === 'NOT PAID' && p.days_past_due > 0
                );

                const maxDaysInArrears = unpaidPayments.length > 0
                    ? Math.max(...unpaidPayments.map(p => p.days_past_due))
                    : 0;

                // Get customer name
                const customerName = await getCustomerName(db, loan.loan_customer, loan.customer_type);

                // Determine RBM classification based on days in arrears
                const rbmClassification = determineRBMClassification(maxDaysInArrears);

                // Create processed loan object
                const processedLoan = {
                    loan_id: loan.loan_id,
                    loan_number: loan.loan_number,
                    customer_name: customerName,
                    disbursement_date: loan.disbursed_date,
                    amount_disbursed: parseFloat(loan.loan_principal) || 0,
                    outstanding_principal: outstandingPrincipal,
                    days_in_arrears: maxDaysInArrears,
                    rbm_classification: rbmClassification,
                    branch_name: loan.BranchName || 'N/A',
                    loan_officer: `${loan.officer_firstname || ''} ${loan.officer_lastname || ''}`.trim() || 'N/A',
                    relationship_supervisor: loan.relationship_supervisor || 'N/A',
                    product_name: loan.product_name || 'N/A'
                };

                // Add to classification summary
                updateClassificationSummary(classificationSummary, rbmClassification, outstandingPrincipal);

                processedLoans.push(processedLoan);
            } catch (error) {
                console.error(`Error processing loan ${loan.loan_id}:`, error);
                // Continue with next loan instead of failing the whole report
            }
        }

        // Get filter names for display
        reportTrackers[reportId].percentage = 90;
        console.log('Preparing display information...');

        let filterBranchName = 'All Branches';
        let filterOfficerName = 'All Loan Officers';
        let filterProductName = 'All Products';

        if (filterOptions.branch && filterOptions.branch !== 'All') {
            const branchResult = await new Promise((resolve, reject) => {
                db.query('SELECT BranchName FROM branches WHERE id = ?',
                    [filterOptions.branch], (err, result) => {
                        if (err) return reject(err);
                        resolve(result);
                    });
            });

            if (branchResult && branchResult.length > 0) {
                filterBranchName = branchResult[0].BranchName;
            }
        }

        if (filterOptions.officer && filterOptions.officer !== 'All') {
            const officerResult = await new Promise((resolve, reject) => {
                db.query('SELECT Firstname, Lastname FROM employees WHERE id = ?',
                    [filterOptions.officer], (err, result) => {
                        if (err) return reject(err);
                        resolve(result);
                    });
            });

            if (officerResult && officerResult.length > 0) {
                filterOfficerName = `${officerResult[0].Firstname} ${officerResult[0].Lastname}`;
            }
        }

        if (filterOptions.product && filterOptions.product !== 'All') {
            const productResult = await new Promise((resolve, reject) => {
                db.query('SELECT product_name FROM loan_products WHERE loan_product_id = ?',
                    [filterOptions.product], (err, result) => {
                        if (err) return reject(err);
                        resolve(result);
                    });
            });

            if (productResult && productResult.length > 0) {
                filterProductName = productResult[0].product_name;
            }
        }

        // Calculate percentages and provision amounts
        const classificationData = calculateClassificationData(classificationSummary, totalPortfolio);

        // Generate HTML report
        reportTrackers[reportId].percentage = 95;
        console.log('Generating HTML report...');

        const html = generateRBMReportHtml(
            baseurl,
            processedLoans,
            classificationData,
            totalPortfolio,
            {
                reportDate: currentDate,
                branchName: filterBranchName,
                officerName: filterOfficerName,
                productName: filterProductName
            }
        );

        reportTrackers[reportId].percentage = 100;
        console.log('====== RBM LOAN CLASSIFICATION REPORT GENERATION COMPLETED ======');

        return html;
    } catch (error) {
        console.error('Error generating RBM classification report:', error);
        throw error;
    }
}

/**
 * Calculate total outstanding principal for a loan
 *
 * @param {Object} db - Database connection
 * @param {number} loanId - Loan ID
 * @returns {Promise<number>} - Outstanding principal amount
 */
async function calculateOutstandingPrincipal(db, loanId) {
    return new Promise((resolve, reject) => {
        db.query(`
            SELECT 
                SUM(CASE 
                    WHEN status = 'NOT PAID' THEN principal - paid_amount 
                    ELSE 0 
                END) as outstanding_principal
            FROM payement_schedules
            WHERE loan_id = ?
        `, [loanId], (err, results) => {
            if (err) return reject(err);
            resolve(parseFloat(results[0]?.outstanding_principal) || 0);
        });
    });
}

/**
 * Get customer name based on customer type and ID
 *
 * @param {Object} db - Database connection
 * @param {number} customerId - Customer ID
 * @param {string} customerType - Customer type ('group' or 'individual')
 * @returns {Promise<string>} - Customer name
 */
async function getCustomerName(db, customerId, customerType) {
    return new Promise((resolve, reject) => {
        if (customerType === 'group') {
            db.query(
                'SELECT group_name, group_code FROM `groups` WHERE group_id = ?',
                [customerId],
                (err, results) => {
                    if (err) return reject(err);
                    if (results.length === 0) return resolve('Unknown Group');
                    resolve(`${results[0].group_name} (${results[0].group_code})`);
                }
            );
        } else {
            db.query(
                'SELECT Firstname, Lastname, ClientId FROM individual_customers WHERE id = ?',
                [customerId],
                (err, results) => {
                    if (err) return reject(err);
                    if (results.length === 0) return resolve('Unknown Customer');
                    const clientId = results[0].ClientId ? ` (${results[0].ClientId})` : '';
                    resolve(`${results[0].Firstname} ${results[0].Lastname}${clientId}`);
                }
            );
        }
    });
}

/**
 * Update classification summary with loan data
 *
 * @param {Object} summary - Classification summary object
 * @param {string} classification - RBM classification
 * @param {number} amount - Outstanding principal amount
 */
function updateClassificationSummary(summary, classification, amount) {
    switch (classification) {
        case 'Standard':
            summary.standard.count++;
            summary.standard.amount += amount;
            break;
        case 'Special Mention':
            summary.special_mention.count++;
            summary.special_mention.amount += amount;
            break;
        case 'Substandard':
            summary.substandard.count++;
            summary.substandard.amount += amount;
            break;
        case 'Doubtful':
            summary.doubtful.count++;
            summary.doubtful.amount += amount;
            break;
        case 'Loss':
            summary.loss.count++;
            summary.loss.amount += amount;
            break;
    }
}

/**
 * Calculate percentages and provision amounts for classification data
 *
 * @param {Object} summary - Classification summary object
 * @param {number} totalPortfolio - Total portfolio amount
 * @returns {Object} - Enhanced classification data
 */
function calculateClassificationData(summary, totalPortfolio) {
    const result = {
        standard: {
            ...summary.standard,
            percentage: totalPortfolio > 0 ? (summary.standard.amount / totalPortfolio) * 100 : 0,
            provision_amount: summary.standard.amount * summary.standard.provision_rate
        },
        special_mention: {
            ...summary.special_mention,
            percentage: totalPortfolio > 0 ? (summary.special_mention.amount / totalPortfolio) * 100 : 0,
            provision_amount: summary.special_mention.amount * summary.special_mention.provision_rate
        },
        substandard: {
            ...summary.substandard,
            percentage: totalPortfolio > 0 ? (summary.substandard.amount / totalPortfolio) * 100 : 0,
            provision_amount: summary.substandard.amount * summary.substandard.provision_rate
        },
        doubtful: {
            ...summary.doubtful,
            percentage: totalPortfolio > 0 ? (summary.doubtful.amount / totalPortfolio) * 100 : 0,
            provision_amount: summary.doubtful.amount * summary.doubtful.provision_rate
        },
        loss: {
            ...summary.loss,
            percentage: totalPortfolio > 0 ? (summary.loss.amount / totalPortfolio) * 100 : 0,
            provision_amount: summary.loss.amount * summary.loss.provision_rate
        }
    };

    // Calculate totals
    // Calculate totals
    result.total_count = result.standard.count + result.special_mention.count + result.substandard.count + result.doubtful.count + result.loss.count;
    result.total_amount = result.standard.amount + result.special_mention.amount + result.substandard.amount + result.doubtful.amount + result.loss.amount;
    result.total_provision = result.standard.provision_amount + result.special_mention.provision_amount + result.substandard.provision_amount + result.doubtful.provision_amount + result.loss.provision_amount;
    return result;
}

/**
 * Format number with commas and two decimal places
 *
 * @param {number} value - Number to format
 * @returns {string} - Formatted number
 */
function formatNumber(value) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
}

/**
 * Format currency value
 *
 * @param {number} value - Currency value
 * @returns {string} - Formatted currency
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
}

/**
 * Format percentage value
 *
 * @param {number} value - Percentage value
 * @returns {string} - Formatted percentage
 */
function formatPercentage(value) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
        style: 'percent'
    }).format(value / 100);
}

/**
 * Format date for display
 *
 * @param {string} dateString - Date string
 * @returns {string} - Formatted date
 */
function formatDate(dateString) {
    if (!dateString) return '';
    return moment(dateString).format('YYYY-MM-DD');
}

/**
 * Generate HTML for the RBM loan classification report
 *
 * @param {Array} loans - Processed loan data
 * @param {Object} classificationData - Classification summary data
 * @param {number} totalPortfolio - Total portfolio amount
 * @param {Object} metadata - Report metadata
 * @returns {string} - HTML content
 */
function generateRBMReportHtml(baseUrl, loans, classificationData, totalPortfolio, metadata) {
    // Sort loans by RBM Classification severity (Loss first, Standard last)
    const sortedLoans = [...loans].sort((a, b) => {
        const order = {
            'Loss': 5,
            'Doubtful': 4,
            'Substandard': 3,
            'Special Mention': 2,
            'Standard': 1
        };
        return order[b.rbm_classification] - order[a.rbm_classification];
    });

    // Create table rows for classification summary
    let summaryCategoryRows = '';

    // Standard loans
    summaryCategoryRows += `
        <tr class="status-standard">
            <td>Standard (0-29 days)</td>
            <td class="summary-value">${classificationData.standard.count}</td>
            <td class="summary-value">${formatCurrency(classificationData.standard.amount)}</td>
            <td class="summary-value">${formatPercentage(classificationData.standard.percentage)}</td>
            <td class="summary-value">${formatPercentage(classificationData.standard.provision_rate * 100)}</td>
            <td class="summary-value">${formatCurrency(classificationData.standard.provision_amount)}</td>
        </tr>
    `;

    // Special Mention loans
    summaryCategoryRows += `
        <tr class="status-special-mention">
            <td>Special Mention (30-59 days)</td>
            <td class="summary-value">${classificationData.special_mention.count}</td>
            <td class="summary-value">${formatCurrency(classificationData.special_mention.amount)}</td>
            <td class="summary-value">${formatPercentage(classificationData.special_mention.percentage)}</td>
            <td class="summary-value">${formatPercentage(classificationData.special_mention.provision_rate * 100)}</td>
            <td class="summary-value">${formatCurrency(classificationData.special_mention.provision_amount)}</td>
        </tr>
    `;

    // Substandard loans
    summaryCategoryRows += `
        <tr class="status-substandard">
            <td>Substandard (60-89 days)</td>
            <td class="summary-value">${classificationData.substandard.count}</td>
            <td class="summary-value">${formatCurrency(classificationData.substandard.amount)}</td>
            <td class="summary-value">${formatPercentage(classificationData.substandard.percentage)}</td>
            <td class="summary-value">${formatPercentage(classificationData.substandard.provision_rate * 100)}</td>
            <td class="summary-value">${formatCurrency(classificationData.substandard.provision_amount)}</td>
        </tr>
    `;

    // Doubtful loans
    summaryCategoryRows += `
        <tr class="status-doubtful">
            <td>Doubtful (90-179 days)</td>
            <td class="summary-value">${classificationData.doubtful.count}</td>
            <td class="summary-value">${formatCurrency(classificationData.doubtful.amount)}</td>
            <td class="summary-value">${formatPercentage(classificationData.doubtful.percentage)}</td>
            <td class="summary-value">${formatPercentage(classificationData.doubtful.provision_rate * 100)}</td>
            <td class="summary-value">${formatCurrency(classificationData.doubtful.provision_amount)}</td>
        </tr>
    `;

    // Loss loans
    summaryCategoryRows += `
        <tr class="status-loss">
            <td>Loss (180+ days)</td>
            <td class="summary-value">${classificationData.loss.count}</td>
            <td class="summary-value">${formatCurrency(classificationData.loss.amount)}</td>
            <td class="summary-value">${formatPercentage(classificationData.loss.percentage)}</td>
            <td class="summary-value">${formatPercentage(classificationData.loss.provision_rate * 100)}</td>
            <td class="summary-value">${formatCurrency(classificationData.loss.provision_amount)}</td>
        </tr>
    `;

    // Total row - Fixed to show the actual total amount
    summaryCategoryRows += `
        <tr class="total-row">
            <td><strong>Total</strong></td>
            <td class="summary-value"><strong>${classificationData.total_count}</strong></td>
            <td class="summary-value"><strong>${formatCurrency(classificationData.total_amount)}</strong></td>
            <td class="summary-value"><strong>100.0%</strong></td>
            <td class="summary-value">-</td>
            <td class="summary-value"><strong>${formatCurrency(classificationData.total_provision)}</strong></td>
        </tr>
    `;

    // Create table rows for loan details
    let loanDetailsRows = '';

    sortedLoans.forEach((loan, index) => {
        const rowClass = `status-${loan.rbm_classification.toLowerCase().replace(' ', '-')}`;
        const url = baseUrl+'reports/rbm_loan_details/'+loan.loan_id;
        loanDetailsRows += `
            <tr class="${rowClass}">
                <td>${index + 1}</td>
                <td>${loan.loan_number}</td>
                <td>${loan.customer_name}</td>
                <td>${formatDate(loan.disbursement_date)}</td>
                <td class="value-right">${formatCurrency(loan.amount_disbursed)}</td>
                <td class="value-right">${formatCurrency(loan.outstanding_principal)}</td>
                <td class="value-right">${loan.days_in_arrears}</td>
                <td>${loan.rbm_classification}</td>
                <td>${loan.branch_name}</td>
                <td>${loan.loan_officer}</td>
                <td>${loan.relationship_supervisor || 'N/A'}</td>
                <td><a href="${url}" target="_blank">Add risk Officer</a></td>
            </tr>
        `;
    });

    // If no loans found, display message
    if (loanDetailsRows === '') {
        loanDetailsRows = `
            <tr>
                <td colspan="11" class="no-data">No loans matching the filter criteria</td>
            </tr>
        `;
    }

    // Generate complete HTML
    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>RBM Loan Classification Status Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                color: #333;
            }
            .header {
                margin-bottom: 20px;
            }
            .header h1 {
                color: #153505;
                margin-bottom: 5px;
            }
            .header p {
                color: #666;
                margin: 5px 0;
            }
            .card {
                border: 2px solid #153505;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .filter-info {
                background-color: #f5f5f5;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .filter-info p {
                margin: 5px 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 14px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #153505;
                color: white;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            .status-standard {
                background-color: #c8e6c9;
            }
            .status-special-mention {
                background-color: #fff9c4;
            }
            .status-substandard {
                background-color: #ffecb3;
            }
            .status-doubtful {
                background-color: #ffccbc;
            }
            .status-loss {
                background-color: #ffcdd2;
            }
            .summary-table {
                width: 70%;
                margin-top: 30px;
                margin-bottom: 30px;
            }
            .summary-table th {
                text-align: center;
            }
            .summary-value {
                text-align: right !important;
            }
            .value-right {
                text-align: right;
            }
            .export-buttons {
                margin-bottom: 15px;
                text-align: right;
            }
            .export-buttons button {
                padding: 6px 12px;
                background-color: #153505;
                color: white;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                margin-left: 5px;
            }
            .total-row {
                background-color: #eaf4fd;
                font-weight: bold;
            }
            .no-data {
                text-align: center;
                padding: 20px;
                background-color: #f9f9f9;
                color: #777;
            }
        </style>
        <!-- Include SheetJS library for Excel exports -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script>
            function exportData(type) {
                const fileName = 'RBM_Loan_Classification_Report.' + type;
                const table = document.getElementById("loan-details-table");
                const wb = XLSX.utils.table_to_book(table);
                XLSX.writeFile(wb, fileName);
            }
            
            function exportSummary(type) {
                const fileName = 'RBM_Classification_Summary.' + type;
                const table = document.getElementById("summary-table");
                const wb = XLSX.utils.table_to_book(table);
                XLSX.writeFile(wb, fileName);
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>RBM Loan Classification Status Report</h1>
            <p>Report generated on: ${formatDate(metadata.reportDate)}</p>
        </div>
        
        <div class="card">
            <div class="filter-info">
                <p><strong>Branch:</strong> ${metadata.branchName}</p>
                <p><strong>Loan Officer:</strong> ${metadata.officerName}</p>
                <p><strong>Product:</strong> ${metadata.productName}</p>
                <p><strong>Date:</strong> All active loans as of ${formatDate(metadata.reportDate)}</p>
            </div>
            
            <div class="export-buttons">
                <span>Export as:</span>
                <button onclick="exportSummary('xlsx')">Export Summary</button>
                <button onclick="exportData('xlsx')">Export Details</button>
            </div>
            
            <h2>Loan Classification Summary</h2>
            
            <!-- Summary Statistics -->
            <table id="summary-table" class="summary-table">
                <thead>
                    <tr>
                        <th>Status Classification</th>
                        <th>Number of Loans</th>
                        <th>Outstanding Principal (MWK)</th>
                        <th>% of Portfolio</th>
                        <th>Required Provision (%)</th>
                        <th>Provision Amount (MWK)</th>
                    </tr>
                </thead>
                <tbody>
                    ${summaryCategoryRows}
                </tbody>
            </table>
            
            <h2>Loan Details by Classification</h2>
            
            <!-- Detailed loan listing -->
            <table id="loan-details-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Loan Number</th>
                        <th>Customer Name</th>
                        <th>Disbursement Date</th>
                        <th>Amount Disbursed</th>
                        <th>Outstanding Principal</th>
                        <th>Days in Arrears</th>
                        <th>RBM Classification</th>
                        <th>Branch</th>
                        <th>Loan Officer</th>
                        <th>Relationship Supervisor</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    ${loanDetailsRows}
                </tbody>
            </table>
        </div>
    </body>
    </html>`;
}

module.exports = {
    generateRBMClassificationReport
};