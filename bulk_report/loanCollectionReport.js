const moment = require('moment');
const fs = require('fs');
const path = require('path');
const {
    sqlLoanMaxDaysInArrearsExpr,
    getOfficerIdsUnderSupervisor,
    sqlRelationshipSupervisorNameExpr,
    sqlBranchJoin
} = require('./databaseHelpers');
const {
    buildPaymentSchedulesBatchSql,
    processLoanToCanonicalRow,
    renderCanonicalLoanTableHtml,
} = require('./parReports');

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
                   l.loan_principal, l.loan_amount_total, l.loan_amount_term,
                   l.loan_date, l.loan_period, l.period_type, l.loan_interest,
                   l.loan_added_date, l.loan_status, l.loan_added_by,
                   lp.product_name,
                   employees.Firstname as loan_officer_firstname,
                   employees.Lastname as loan_officer_lastname,
                   CONCAT(COALESCE(employees.Firstname, ''), ' ', COALESCE(employees.Lastname, '')) as loan_officer,
                   ${sqlRelationshipSupervisorNameExpr('rel_sup')} as relationship_supervisor,
                   b.BranchName as branch_name,
                   b.Code as branch_code,
                   CASE
                       WHEN l.customer_type = 'group' THEN g.group_name
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
                     LEFT JOIN loan_products lp ON lp.loan_product_id = l.loan_product
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

            const asOfDate = toDate || moment().format('YYYY-MM-DD');
            const loanIds = loans.map(l => l.loan_id);
            const paymentMap = {};
            const batchSql = buildPaymentSchedulesBatchSql(loanIds, asOfDate);
            if (batchSql) {
                const paymentData = await new Promise((resolve, reject) => {
                    db.query(batchSql, (err, results) => err ? reject(err) : resolve(results));
                });
                paymentData.forEach(p => { paymentMap[p.loan_id] = p; });
            }

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
                    collections.push(processLoanToCanonicalRow(loan, paymentMap[loan.loan_id] || {}, {
                        last_payment_date: loan.next_repayment_date,
                    }));
                } catch (error) {
                    console.error(`Error processing loan ${loan.loan_id}:`, error);
                    // Continue with next loan instead of failing the whole report
                }
            }

            // Sort collections by collection rate (ascending)
            collections.sort((a, b) => (a.collection_rate || 0) - (b.collection_rate || 0));

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
    const totalDisbursed = collections.reduce((s, l) => s + parseFloat(l.loan_principal || 0), 0);
    const totalExpected = collections.reduce((s, l) => s + parseFloat(l.total_expected_installments || 0), 0);
    const totalCollected = collections.reduce((s, l) => s + parseFloat(l.actual_payments || 0), 0);
    const overallCollectionRate = totalExpected > 0 ? (totalCollected / totalExpected) * 100 : 0;

    let dateFilterText = '';
    if (filterOptions.period && filterOptions.period !== 'custom') {
        dateFilterText = filterOptions.period;
    } else if (filterOptions.from === null && filterOptions.to === null) {
        dateFilterText = 'All dates (no date filtering)';
    } else if (filterOptions.from && filterOptions.to) {
        dateFilterText = `${filterOptions.from} to ${filterOptions.to}`;
    } else if (filterOptions.from) {
        dateFilterText = `${filterOptions.from} to Present`;
    } else if (filterOptions.to) {
        dateFilterText = `Beginning to ${filterOptions.to}`;
    }

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
                <p><strong>Summary:</strong> ${collections.length} loans | Principal MWK ${formatCurrency(totalDisbursed)} | Expected MWK ${formatCurrency(totalExpected)} | Collected MWK ${formatCurrency(totalCollected)} | Rate ${formatNumber(overallCollectionRate)}%</p>
                ${renderCanonicalLoanTableHtml(collections, 'collections-table')}
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