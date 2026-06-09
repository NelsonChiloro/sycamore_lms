const moment = require('moment');
const fs = require('fs');
const path = require('path');
const {
    determineRBMClassification,
    sqlLoanMaxDaysInArrearsExpr,
    getOfficerIdsUnderSupervisor,
    sqlRelationshipSupervisorNameExpr,
    sqlBranchJoin
} = require('./databaseHelpers');

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
            filterOptions.supervisor,
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
async function getCollectionsData(branch, loanOfficer, supervisor, fromDate, toDate, reportId, reportTrackers, db) {
    if (!db) {
        throw new Error('Database connection is not available');
    }

    reportTrackers[reportId].percentage = 10;
    console.log('Fetching loan collections data...');

    let whereConditions = [];
    let params = [];

    whereConditions.push("l.loan_status IN ('ACTIVE','APPROVED')");
    whereConditions.push("l.disbursed = 'Yes'");

    if (branch !== 'All') {
        whereConditions.push(`(
            l.branch = ?
            OR l.branch IN (SELECT Code FROM branches WHERE id = ?)
            OR l.branch IN (SELECT BranchCode FROM branches WHERE id = ?)
        )`);
        params.push(branch, branch, branch);
    }

    if (supervisor && supervisor !== 'All') {
        const officerIds = await getOfficerIdsUnderSupervisor(supervisor);
        if (!officerIds.length) {
            whereConditions.push('1=0');
        } else {
            whereConditions.push(`l.loan_added_by IN (${officerIds.join(',')})`);
        }
    } else if (loanOfficer !== 'All') {
        whereConditions.push('l.loan_added_by = ?');
        params.push(loanOfficer);
    }

    return new Promise((resolve, reject) => {

        // Combine conditions
        const whereClause = whereConditions.length > 0
            ? 'WHERE ' + whereConditions.join(' AND ')
            : '';

        const dateFilterEnabled = fromDate !== null && toDate !== null;

        // Query to get loans with aggregated schedule metrics in one pass.
        const query = `
            SELECT l.loan_id, l.loan_number, l.loan_customer, l.customer_type,
                   l.loan_principal, l.loan_amount_total, l.loan_added_date,
                   l.loan_status, l.loan_added_by,
                   employees.Firstname as loan_officer_firstname,
                   employees.Lastname as loan_officer_lastname,
                   ${sqlRelationshipSupervisorNameExpr('rel_sup')} as relationship_supervisor,
                   b.BranchName as branch_name,
                   b.Code as branch_code,
                   CASE
                       WHEN l.customer_type = 'group' THEN CONCAT(g.group_name, ' (', g.group_code, ')')
                       ELSE 'N/A'
                   END AS customer_group_name,
                   CASE
                       WHEN l.customer_type = 'group' THEN CONCAT(g.group_name, ' (', g.group_code, ')')
                       WHEN l.customer_type = 'individual' THEN CONCAT(ic.Firstname, ' ', ic.Lastname, ' (', COALESCE(ic.ClientId, 'No ID'), ')')
                       ELSE 'Unknown Customer'
                   END AS customer_name,
                   COALESCE(ps.total_expected, 0) as total_expected,
                   COALESCE(ps.total_collected, 0) as total_collected,
                   ps.next_repayment_date,
                   ${sqlLoanMaxDaysInArrearsExpr('l')} as days_in_arrears
            FROM loan l
                     LEFT JOIN employees ON employees.id = l.loan_added_by
                     LEFT JOIN employees rel_sup ON rel_sup.id = employees.Supervisor
                     ${sqlBranchJoin('l', 'b')}
                     LEFT JOIN individual_customers ic ON ic.id = l.loan_customer AND l.customer_type = 'individual'
                     LEFT JOIN \`groups\` g ON l.loan_customer = g.group_id AND l.customer_type = 'group'
                     LEFT JOIN (
                        SELECT
                            loan_id,
                            SUM(CASE
                                WHEN ? = 0 THEN COALESCE(amount, 0)
                                WHEN ? = 1 AND DATE(payment_schedule) BETWEEN DATE(?) AND DATE(?) THEN COALESCE(amount, 0)
                                WHEN ? = 1 AND DATE(payment_schedule) < DATE(?)
                                     AND COALESCE(amount, 0) > COALESCE(paid_amount, 0)
                                     THEN (COALESCE(amount, 0) - COALESCE(paid_amount, 0))
                                ELSE 0 END) AS total_expected,
                            SUM(CASE
                                WHEN ? = 1 AND DATE(payment_schedule) BETWEEN DATE(?) AND DATE(?) THEN COALESCE(paid_amount, 0)
                                WHEN ? = 0 THEN COALESCE(paid_amount, 0)
                                ELSE 0 END) AS total_collected,
                            MIN(CASE WHEN COALESCE(amount, 0) > COALESCE(paid_amount, 0) THEN payment_schedule END) AS next_repayment_date
                        FROM payement_schedules
                        GROUP BY loan_id
                     ) ps ON ps.loan_id = l.loan_id
                ${whereClause}
        `;

        // Execute the query
        const df = dateFilterEnabled ? 1 : 0;
        const rangeStart = fromDate || '1900-01-01';
        const rangeEnd = toDate || '2999-12-31';
        const queryParams = [
            // Parameters for payement_schedules aggregate subquery placeholders.
            df,
            df,
            rangeStart,
            rangeEnd,
            df,
            rangeStart,
            df,
            rangeStart,
            rangeEnd,
            df,
            // WHERE-clause filters are appended last because they appear last in SQL.
            ...(params || [])
        ];

        db.query(query, queryParams, async (err, loans) => {
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
                        db.query('SELECT BranchName FROM branches WHERE id = ? OR Code = ? LIMIT 1', [branch, branch], (err, result) => {
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
                    const collectionData = {
                        loan_id: loan.loan_id,
                        loan_number: loan.loan_number,
                        customer_name: loan.customer_name || 'Unknown Customer',
                        customer_group_name: loan.customer_group_name || 'N/A',
                        amount_disbursed: loan.loan_principal,
                        branch_name: loan.branch_name || 'N/A',
                        loan_officer: `${loan.loan_officer_firstname || ''} ${loan.loan_officer_lastname || ''}`.trim() || 'N/A',
                        relationship_supervisor: loan.relationship_supervisor || 'N/A',
                        expected_collection: parseFloat(loan.total_expected) || 0,
                        amount_collected: parseFloat(loan.total_collected) || 0
                    };

                    // Calculate collection rate
                    if (collectionData.expected_collection > 0) {
                        collectionData.collection_rate = (collectionData.amount_collected / collectionData.expected_collection) * 100;
                    } else {
                        collectionData.collection_rate = 0;
                    }

                    collectionData.repayment_date = loan.next_repayment_date || 'N/A';
                    collectionData.days_in_arrears = parseInt(loan.days_in_arrears || 0, 10);
                    collectionData.rbm_classification = determineRBMClassification(collectionData.days_in_arrears);

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
            <td>${collection.customer_group_name || 'N/A'}</td>
            <td>${formatCurrency(collection.amount_disbursed)}</td>
            <td>${formatCurrency(collection.expected_collection)}</td>
            <td>${formatCurrency(collection.amount_collected)}</td>
            <td>${formatNumber(collection.collection_rate)}%</td>
            <td>${formatDate(collection.repayment_date)}</td>
            <td>${collection.rbm_classification || 'Standard'}</td>
            <td>${collection.loan_officer}</td>
            <td>${collection.relationship_supervisor || 'N/A'}</td>
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
                            <td colspan="13">Loan Collections Report - Filter Information</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Branch:</td>
                            <td colspan="11">${filterOptions.branch_name || 'All Branches'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Loan Officer:</td>
                            <td colspan="11">${filterOptions.officer_name || 'All Officers'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Date Range:</td>
                            <td colspan="11">${dateFilterText}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Report Date:</td>
                            <td colspan="11">${moment().format('YYYY-MM-DD HH:mm:ss')}</td>
                        </tr>
                        <!-- Empty row for spacing -->
                        <tr>
                            <td colspan="13">&nbsp;</td>
                        </tr>
                        <!-- Data header row -->
                        <tr>
                            <th>#</th>
                            <th>Branch</th>
                            <th>Loan Number</th>
                            <th>Customer</th>
                            <th>Customer Group</th>
                            <th>Amount Disbursed (MWK)</th>
                            <th>Expected Collection to Date (MWK)</th>
                            <th>Amount Collected (MWK)</th>
                            <th>Collections Rate (%)</th>
                            <th>Next Repayment Date</th>
                            <th>RBM Loan Classification</th>
                            <th>Loan Officer</th>
                            <th>Relationship Supervisor</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">Totals</td>
                            <td>${formatCurrency(totalDisbursed)}</td>
                            <td>${formatCurrency(totalExpected)}</td>
                            <td>${formatCurrency(totalCollected)}</td>
                            <td>${formatNumber(overallCollectionRate)}%</td>
                            <td colspan="4"></td>
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