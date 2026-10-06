const moment = require('moment');
const fs = require('fs');
const path = require('path');

/**
 * Generate a Loan Collections Report HTML
 *
 * @param {Object} filterOptions - Filter parameters for the report
 * @param {number} reportId - The ID of the report record
 * @param {Object} reportTrackers - Object tracking report generation progress
 * @param {Object} db - Database connection
 * @returns {Promise<string>} - HTML content of the report
 */
async function generateLoanCollectionsReport(filterOptions, reportId, reportTrackers, db) {
    console.log('====== LOAN COLLECTIONS REPORT GENERATION STARTED ======');
    console.log(`Report ID: ${reportId}`);
    console.log(`Filters: ${JSON.stringify(filterOptions)}`);

    // Set initial progress
    reportTrackers[reportId].percentage = 5;

    try {
        // Get loan collections data based on filters
        const result = await getCollectionsData(
            filterOptions.branch || 'All',
            filterOptions.user || 'All',
            filterOptions.from, // This can be null now
            filterOptions.to,   // This can be null now
            reportId,
            reportTrackers,
            db
        );

        // Update filter options with human-readable names
        const updatedFilterOptions = {
            ...filterOptions,
            branchName: result.filterBranchName,
            userName: result.filterOfficerName,
            dateFilterStatus: result.dateFilterStatus
        };

        // Generate HTML using the data
        const html = generateHtml(result.collections, updatedFilterOptions);

        // Set final progress
        reportTrackers[reportId].percentage = 100;

        console.log('====== LOAN COLLECTIONS REPORT GENERATION COMPLETED ======');
        return html;
    } catch (error) {
        console.error('Error generating loan collections report:', error);
        throw error;
    }
}

/**
 * Get loan collections data based on filters
 *
 * @param {string} branch - Branch filter
 * @param {string} loanOfficer - Loan officer filter
 * @param {string|null} fromDate - Start date (null for no date filtering)
 * @param {string|null} toDate - End date (null for no date filtering)
 * @param {number} reportId - Report ID for tracking
 * @param {Object} reportTrackers - Progress tracking object
 * @param {Object} db - Database connection
 * @returns {Promise<Object>} - Collection data with filter names
 */
async function getCollectionsData(branch, loanOfficer, fromDate, toDate, reportId, reportTrackers, db) {
    return new Promise((resolve, reject) => {
        if (!db) {
            return reject(new Error('Database connection is not available'));
        }

        // Update progress
        reportTrackers[reportId].percentage = 10;
        console.log('Fetching loan collections data...');

        // Build where conditions for the query
        let whereConditions = [];
        let params = [];

        // Basic condition for active loans
        whereConditions.push('l.loan_status = ?');
        params.push('ACTIVE');

        // Apply branch filter if not 'All'
        if (branch !== 'All') {
            whereConditions.push('l.branch = ?');
            params.push(branch);
        }

        // Apply loan officer filter if not 'All'
        if (loanOfficer !== 'All') {
            whereConditions.push('l.loan_added_by = ?');
            params.push(loanOfficer);
        }

        // Combine conditions
        const whereClause = whereConditions.length > 0
            ? 'WHERE ' + whereConditions.join(' AND ')
            : '';

        // Query to get loans with their payment schedules
        const query = `
            SELECT l.loan_id, l.loan_number, l.loan_customer, l.customer_type,
                   l.loan_principal, l.loan_amount_total, l.loan_added_date,
                   l.loan_status, l.loan_added_by,
                   employees.Firstname as loan_officer_firstname,
                   employees.Lastname as loan_officer_lastname,
                   b.BranchName as branch_name,
                   b.Code as branch_code
            FROM loan l
                     LEFT JOIN employees ON employees.id = l.loan_added_by
                     LEFT JOIN branches b ON b.id = l.branch
                ${whereClause}
        `;

        // Execute the query
        db.query(query, params, async (err, loans) => {
            if (err) {
                console.error('Error fetching loans:', err);
                return reject(err);
            }

            reportTrackers[reportId].percentage = 20;
            console.log(`Found ${loans.length} active loans`);

            const collections = [];
            let processedCount = 0;
            const totalCount = loans.length;

            // Get branch name and officer name for the filters (for display in report header)
            let filterBranchName = 'All Branches';
            let filterOfficerName = 'All Officers';

            if (branch !== 'All') {
                try {
                    const branchResult = await new Promise((resolve, reject) => {
                        db.query('SELECT BranchName FROM branches WHERE Code = ?', [branch], (err, result) => {
                            if (err) return reject(err);
                            resolve(result);
                        });
                    });

                    if (branchResult && branchResult.length > 0) {
                        filterBranchName = branchResult[0].BranchName;
                    }
                } catch (error) {
                    console.error('Error getting branch name:', error);
                }
            }

            if (loanOfficer !== 'All') {
                try {
                    const officerResult = await new Promise((resolve, reject) => {
                        db.query('SELECT Firstname, Lastname FROM employees WHERE id = ?', [loanOfficer], (err, result) => {
                            if (err) return reject(err);
                            resolve(result);
                        });
                    });

                    if (officerResult && officerResult.length > 0) {
                        filterOfficerName = `${officerResult[0].Firstname} ${officerResult[0].Lastname}`;
                    }
                } catch (error) {
                    console.error('Error getting officer name:', error);
                }
            }

            // Process each loan to get collection data
            for (const loan of loans) {
                processedCount++;

                // Update progress percentage based on processed loans
                const processedPercentage = 20 + Math.floor((processedCount / totalCount) * 70);
                reportTrackers[reportId].percentage = processedPercentage;

                console.log(`Processing loan ${processedCount}/${totalCount} (${processedPercentage}%)`);

                try {
                    // Get customer details
                    let customerName = await getCustomerName(db, loan.loan_customer, loan.customer_type);

                    // Create collection data object
                    const collectionData = {
                        loan_id: loan.loan_id,
                        loan_number: loan.loan_number,
                        customer_name: customerName,
                        amount_disbursed: loan.loan_principal,
                        branch_name: loan.branch_name || 'N/A',
                        loan_officer: `${loan.loan_officer_firstname || ''} ${loan.loan_officer_lastname || ''}`.trim() || 'N/A'
                    };

                    // Get payment schedules - handle null dates differently
                    let paymentTotals;

                    if (fromDate === null || toDate === null) {
                        // No date filtering - get all payment schedules for this loan
                        paymentTotals = await getAllPaymentTotals(db, loan.loan_id);
                    } else {
                        // Apply date filtering
                        paymentTotals = await getPaymentTotalsWithDateFilter(db, loan.loan_id, fromDate, toDate);
                    }

                    collectionData.expected_collection = paymentTotals.total_expected || 0;
                    collectionData.amount_collected = paymentTotals.total_collected || 0;

                    // Calculate collection rate
                    if (collectionData.expected_collection > 0) {
                        collectionData.collection_rate = (collectionData.amount_collected / collectionData.expected_collection) * 100;
                    } else {
                        collectionData.collection_rate = 0;
                    }

                    // Get next repayment date
                    const nextPayment = await getNextPayment(db, loan.loan_id);
                    collectionData.repayment_date = nextPayment ? nextPayment.payment_schedule : 'N/A';

                    collections.push(collectionData);
                } catch (error) {
                    console.error(`Error processing loan ${loan.loan_id}:`, error);
                    // Continue with next loan instead of failing the whole report
                }
            }

            // Sort collections by collection rate (ascending)
            collections.sort((a, b) => a.collection_rate - b.collection_rate);

            reportTrackers[reportId].percentage = 95;
            console.log('Loan collection data processing completed');

            // Include the filter names and date information in the result
            const dateFilterStatus = (fromDate === null && toDate === null)
                ? 'All payment schedules (no date filtering)'
                : `Payments from ${fromDate || 'beginning'} to ${toDate || 'today'}`;

            resolve({
                collections,
                filterBranchName,
                filterOfficerName,
                dateFilterStatus
            });
        });
    });
}

/**
 * Get all payment totals for a loan up to current date (no date range filtering)
 *
 * @param {Object} db - Database connection
 * @param {number} loanId - Loan ID
 * @returns {Promise<Object>} - Payment totals
 */
async function getAllPaymentTotals(db, loanId) {
    return new Promise((resolve, reject) => {
        // Use current date as the cut-off for expected collections
        const currentDate = moment().format('YYYY-MM-DD');

        db.query(
            `SELECT
                 SUM(CASE WHEN payment_schedule <= ? THEN amount ELSE 0 END) as total_expected,
                 SUM(paid_amount) as total_collected
             FROM payement_schedules
             WHERE loan_id = ?`,
            [currentDate, loanId],
            (err, results) => {
                if (err) return reject(err);
                resolve(results[0] || { total_expected: 0, total_collected: 0 });
            }
        );
    });
}

/**
 * Get payment totals within date range
 *
 * @param {Object} db - Database connection
 * @param {number} loanId - Loan ID
 * @param {string} fromDate - Start date
 * @param {string} toDate - End date
 * @returns {Promise<Object>} - Payment totals
 */
async function getPaymentTotalsWithDateFilter(db, loanId, fromDate, toDate) {
    return new Promise((resolve, reject) => {
        db.query(
            `SELECT
                 SUM(CASE WHEN payment_schedule BETWEEN ? AND ? THEN amount ELSE 0 END) as total_expected,
                 SUM(CASE WHEN payment_schedule BETWEEN ? AND ? THEN paid_amount ELSE 0 END) as total_collected
             FROM payement_schedules
             WHERE loan_id = ?`,
            [fromDate, toDate, fromDate, toDate, loanId],
            (err, results) => {
                if (err) return reject(err);
                resolve(results[0] || { total_expected: 0, total_collected: 0 });
            }
        );
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
                    resolve(`${results[0].Firstname} ${results[0].Lastname} (${results[0].ClientId || 'No ID'})`);
                }
            );
        }
    });
}

/**
 * Get next payment date
 *
 * @param {Object} db - Database connection
 * @param {number} loanId - Loan ID
 * @returns {Promise<Object>} - Next payment
 */
async function getNextPayment(db, loanId) {
    return new Promise((resolve, reject) => {
        db.query(
            `SELECT payment_schedule
             FROM payement_schedules
             WHERE loan_id = ? AND amount > paid_amount
             ORDER BY payment_schedule ASC
                 LIMIT 1`,
            [loanId],
            (err, results) => {
                if (err) return reject(err);
                resolve(results[0] || null);
            }
        );
    });
}

/**
 * Generate HTML for the loan collections report
 *
 * @param {Array} collections - Collection data
 * @param {Object} filterOptions - Filter parameters
 * @returns {string} - HTML content
 */
function generateHtml(collections, filterOptions) {
    // Format date for display
    const formatDate = (dateString) => {
        if (!dateString || dateString === 'N/A') return 'N/A';
        return moment(dateString).format('YYYY-MM-DD');
    };

    // Calculate totals
    let totalDisbursed = 0;
    let totalExpected = 0;
    let totalCollected = 0;

    collections.forEach(collection => {
        totalDisbursed += parseFloat(collection.amount_disbursed) || 0;
        totalExpected += parseFloat(collection.expected_collection) || 0;
        totalCollected += parseFloat(collection.amount_collected) || 0;
    });

    // Calculate overall collection rate
    const overallCollectionRate = totalExpected > 0
        ? (totalCollected / totalExpected) * 100
        : 0;

    // Create table rows for collections
    let tableRows = '';
    collections.forEach((collection, index) => {
        tableRows += `
        <tr>
            <td>${index + 1}</td>
            <td>${collection.branch_name || 'N/A'}</td>
            <td>${collection.loan_number}</td>
            <td>${collection.customer_name}</td>
            <td>${formatCurrency(collection.amount_disbursed)}</td>
            <td>${formatCurrency(collection.expected_collection)}</td>
            <td>${formatCurrency(collection.amount_collected)}</td>
            <td>${formatNumber(collection.collection_rate)}%</td>
            <td>${formatDate(collection.repayment_date)}</td>
            <td>${collection.loan_officer}</td>
        </tr>`;
    });

    // Determine date filtering text
    let dateFilterText = '';

    if (filterOptions.period && filterOptions.period !== 'custom') {
        // Predefined period
        dateFilterText = filterOptions.period;
    } else {
        // Custom period
        if (filterOptions.from === null && filterOptions.to === null) {
            dateFilterText = 'All dates (no date filtering)';
        } else if (filterOptions.from && filterOptions.to) {
            dateFilterText = `${filterOptions.from} to ${filterOptions.to}`;
        } else if (filterOptions.from) {
            dateFilterText = `${filterOptions.from} to Present`;
        } else if (filterOptions.to) {
            dateFilterText = `Beginning to ${filterOptions.to}`;
        }
    }

    // Generate complete HTML with date range and filters info
    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loan Collections Report</title>
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
            tfoot tr {
                font-weight: bold;
                background-color: #e9e9e9;
            }
            .btn {
                display: inline-block;
                padding: 4px 8px;
                background-color: #153505;
                color: white;
                text-decoration: none;
                border-radius: 3px;
                font-size: 12px;
            }
            .no-records {
                padding: 20px;
                background-color: #e1f5fe;
                border-radius: 5px;
                text-align: center;
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
            .filter-header {
                background-color: #f9f9f9;
                font-weight: bold;
            }
            .filter-header td {
                font-weight: bold;
            }
            .report-info td {
                background-color: #f5f5f5;
            }
        </style>
        <!-- Include SheetJS library for Excel exports -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script>
            function exportData(type) {
                const fileName = 'Loan_Collections_Report.' + type;
                const table = document.getElementById("collections-table");
                const wb = XLSX.utils.table_to_book(table);
                XLSX.writeFile(wb, fileName);
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Loan Collections Report</h1>
            <p>Report generated on: ${moment().format('YYYY-MM-DD HH:mm:ss')}</p>
        </div>
        
        <div class="card">
            <div class="filter-info">
                <p><strong>Branch:</strong> ${filterOptions.branch_name || 'All Branches'}</p>
                <p><strong>Loan Officer:</strong> ${filterOptions.officer_name || 'All Officers'}</p>
                <p><strong>Date Range:</strong> ${dateFilterText}</p>
            </div>
            
            <div class="export-buttons">
                <span>Export as:</span>
                <button onclick="exportData('xlsx')">Excel (xlsx)</button>
                <button onclick="exportData('xls')">Excel (xls)</button>
                <button onclick="exportData('csv')">CSV</button>
            </div>
            
            ${collections.length > 0 ? `
            <div style="overflow-x: auto;">
                <table id="collections-table">
                    <thead>
                        <!-- Filter information rows (included in export) -->
                        <tr class="filter-header">
                            <td colspan="10">Loan Collections Report - Filter Information</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Branch:</td>
                            <td colspan="8">${filterOptions.branch_name || 'All Branches'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Loan Officer:</td>
                            <td colspan="8">${filterOptions.officer_name || 'All Officers'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Date Range:</td>
                            <td colspan="8">${dateFilterText}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Report Date:</td>
                            <td colspan="8">${moment().format('YYYY-MM-DD HH:mm:ss')}</td>
                        </tr>
                        <!-- Empty row for spacing -->
                        <tr>
                            <td colspan="10">&nbsp;</td>
                        </tr>
                        <!-- Data header row -->
                        <tr>
                            <th>#</th>
                            <th>Branch</th>
                            <th>Loan Number</th>
                            <th>Customer</th>
                            <th>Amount Disbursed (MWK)</th>
                            <th>Expected Collection to Date (MWK)</th>
                            <th>Amount Collected (MWK)</th>
                            <th>Collections Rate (%)</th>
                            <th>Next Repayment Date</th>
                            <th>Officer</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">Totals</td>
                            <td>${formatCurrency(totalDisbursed)}</td>
                            <td>${formatCurrency(totalExpected)}</td>
                            <td>${formatCurrency(totalCollected)}</td>
                            <td>${formatNumber(overallCollectionRate)}%</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            ` : `
            <div class="no-records">
                <p>No records found. Please adjust your search criteria.</p>
            </div>
            `}
        </div>
    </body>
    </html>`;
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

module.exports = {
    generateLoanCollectionsReport
};