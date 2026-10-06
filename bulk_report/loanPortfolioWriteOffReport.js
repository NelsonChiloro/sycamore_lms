// loanPortfolioReport.js - Enhanced with country join and removed village
const {
    query,
    sqlBranchJoin,
    buildReportSupervisorContext,
    appendOfficerOrSupervisorLoanFilter,
    sqlRelationshipSupervisorNameExpr
} = require('./databaseHelpers');
const {
    buildPaymentSchedulesBatchSql,
    processLoanToCanonicalRow,
    renderCanonicalLoanTableHtml,
} = require('./parReports');
const moment = require('moment');
const util = require('util');
const fs = require('fs');
const path = require('path');

/**
 * Get loan portfolio report based on filters
 * @param {Object} options - The filter options
 * @param {string} options.user - User/loan officer ID or 'All'
 * @param {string} options.branch - Branch ID or 'All'
 * @param {string} options.branchgp - Branch group ID (for groups filtering)
 * @param {string} options.product - Loan product ID or 'All'
 * @param {string} options.status - Loan status or 'All'
 * @param {string} options.from - Start date for filtering (YYYY-MM-DD)
 * @param {string} options.to - End date for filtering (YYYY-MM-DD)
 * @param {number} reportId - ID of the report being generated
 * @param {Object} reportTrackers - Object to track report generation progress
 * @returns {Promise<string>} - HTML content of the report
 */
async function generateLoanPortfolioWriteOffReport(options, reportId, reportTrackers) {
    const { user, branch, branchgp, product, status, from, to, supervisor } = options;
    const asOfDate = to ? formatDate(to) : moment().format('YYYY-MM-DD');
    const supCtx = await buildReportSupervisorContext({ supervisor, user });

    console.log('Started processing Loan Portfolio Report');

    try {
        // Using centralized database connection pool
        console.log('Using centralized database connection pool');

        // Update tracker to 10%
        reportTrackers[reportId].percentage = 10;

        // Build the SQL query with joins to transaction and collateral tables
        let sql = `
            SELECT
                loan.*,
                loan_products.product_name,
                loan_products.product_code,
                CASE
                    WHEN g.group_id IS NOT NULL THEN CONCAT(g.group_name, ' (', g.group_code, ')')
                    WHEN ic.id IS NOT NULL THEN CONCAT(ic.Firstname, ' ', ic.Lastname, ' (', COALESCE(ic.ClientId, 'No ID'), ')')
                    ELSE 'Unknown Customer'
                END AS customer_name,
                CASE
                    WHEN g.group_id IS NOT NULL THEN g.group_name
                    ELSE 'N/A'
                END AS customer_group_name,
                ic.DateOfBirth,
                ic.Gender,
                ic.PhoneNumber,
                ic.EmailAddress,
                ic.AddressLine1,
                ic.AddressLine2,
                ic.AddressLine3,
                ic.Province,
                ic.City,
                ic.Country AS country_code,
                gc.slug AS country_name,
                ic.Marital_status,
                ic.Profession,
                ic.SourceOfIncome,
                ic.GrossMonthlyIncome,
                e.Firstname AS efname,
                e.Lastname AS elname,
                CONCAT(COALESCE(e.Firstname, ''), ' ', COALESCE(e.Lastname, '')) AS loan_officer,
                ${sqlRelationshipSupervisorNameExpr('rel_sup')} AS relationship_supervisor,
                loan.loan_customer AS cid,
                approver.Firstname AS approverfname,
                approver.Lastname AS approverlname,
                rejecter.Firstname AS rejecterfname,
                rejecter.Lastname AS rejecterlname,
                disburser.Firstname AS disburserfname,
                disburser.Lastname AS disburserlname,
                roff.Firstname AS rofffname,
                roff.Lastname AS rofflname,
                COALESCE(b.BranchName, 'Unknown Branch') AS branch_name,
                loan.customer_type
            FROM
                loan
                LEFT JOIN
                loan_products ON loan_products.loan_product_id = loan.loan_product
                LEFT JOIN
                employees e ON e.id = loan.loan_added_by
                LEFT JOIN
                employees rel_sup ON rel_sup.id = e.Supervisor
                LEFT JOIN
                employees approver ON approver.id = loan.loan_approved_by
                LEFT JOIN
                employees disburser ON disburser.id = loan.disbursed_by
                LEFT JOIN
                employees roff ON roff.id = loan.written_off_by
                LEFT JOIN
                employees rejecter ON rejecter.id = loan.rejected_by
                LEFT JOIN
                individual_customers ic ON loan.loan_customer = ic.id AND loan.customer_type = 'individual'
                LEFT JOIN
                geo_countries gc ON ic.Country = gc.code
                LEFT JOIN
                \`groups\` g ON loan.loan_customer = g.group_id AND loan.customer_type = 'group'
                ${sqlBranchJoin('loan', 'b')}
            WHERE
                loan.loan_status IN ('WRITTEN_OFF')
        `;

        // Add filters
        if (branch !== 'All') {
            const branchEsc = String(branch).replace(/'/g, "''");
            sql += ` AND (
                loan.branch = '${branchEsc}'
                OR loan.branch IN (SELECT Code FROM branches WHERE id = '${branchEsc}')
                OR loan.branch IN (SELECT BranchCode FROM branches WHERE id = '${branchEsc}')
            )`;
        }

        if (status !== 'All') {
            sql += ` AND loan.loan_status = '${status}'`;
        }

        sql = appendOfficerOrSupervisorLoanFilter(sql, { ...supCtx, user }, 'loan', 'user');

        if (product !== 'All') {
            sql += ` AND loan.loan_product = '${product}'`;
        }

        if (from && to) {
            sql += ` AND DATE(loan.loan_added_date) BETWEEN '${formatDate(from)}' AND '${formatDate(to)}'`;
        } else if (from) {
            sql += ` AND DATE(loan.loan_added_date) >= '${formatDate(from)}'`;
        } else if (to) {
            sql += ` AND DATE(loan.loan_added_date) <= '${formatDate(to)}'`;
        }

        sql += ` ORDER BY loan.loan_id DESC`;

        // Update tracker to 30%
        reportTrackers[reportId].percentage = 30;

        // Execute query
        const loanData = await query(sql);
        console.log(`Found ${loanData.length} loans matching the criteria`);

        // Update tracker to 50%
        reportTrackers[reportId].percentage = 50;

        if (loanData.length === 0) {
            reportTrackers[reportId].percentage = 100;
            return generateHTML([]);
        }

        const loanIds = loanData.map(loan => loan.loan_id);
        const loanNumbers = loanData.map(loan => loan.loan_number);

        const paymentSchedulesQuery = buildPaymentSchedulesBatchSql(loanIds, asOfDate, { requireActiveLoan: false });
        const paymentData = paymentSchedulesQuery ? await query(paymentSchedulesQuery) : [];
        const paymentMap = {};
        paymentData.forEach(p => { paymentMap[p.loan_id] = p; });

        const lastPaymentData = await query(`
            SELECT account_number, MAX(server_time) as last_credit_date
            FROM transaction
            WHERE account_number IN ('${loanNumbers.join("','")}')
            AND transaction_type = 'deposit' AND reversed = 'NO'
            GROUP BY account_number
        `);
        const lastPaymentMap = {};
        lastPaymentData.forEach(p => { lastPaymentMap[p.account_number] = p.last_credit_date; });

        const collateralData = await query(`
            SELECT loan_id, SUM(estimated_price) as collateral_value
            FROM collateral WHERE loan_id IN (${loanIds.join(',')})
            GROUP BY loan_id
        `);
        const collateralMap = {};
        collateralData.forEach(c => { collateralMap[c.loan_id] = c.collateral_value; });

        reportTrackers[reportId].percentage = 85;

        const canonicalLoans = loanData.map(loan => processLoanToCanonicalRow(loan, paymentMap[loan.loan_id] || {}, {
            last_payment_date: lastPaymentMap[loan.loan_number],
            collateral_value: collateralMap[loan.loan_id] || 0,
        }));

        // Generate HTML report
        const html = generateHTML(canonicalLoans);

        // Update tracker to 100%
        reportTrackers[reportId].percentage = 100;

        // Close the database connection
        // Connection pool handles cleanup automatically

        console.log('Returning loan portfolio data');
        return html;
    } catch (error) {
        console.error('Error generating loan portfolio report:', error);

        // Close the database connection
        // Connection pool handles cleanup automatically

        throw error;
    }
}

/**
 * Format date to YYYY-MM-DD
 * @param {string} date - Date string
 * @returns {string} - Formatted date
 */
function formatDate(date) {
    return moment(date).format('YYYY-MM-DD');
}

/**
 * Format date for display (YYYY-MM-DD)
 * @param {string|Date} date - Date to format
 * @returns {string} - Formatted date or empty string if invalid
 */
function formatDisplayDate(date) {
    if (!date) return '';
    return moment(date).format('YYYY-MM-DD');
}

/**
 * Generate HTML report from canonical loan rows (Detailed Portfolio Report columns)
 */
function generateHTML(loanData) {
    const totals = loanData.reduce((acc, loan) => ({
        grossLoanPortfolio: acc.grossLoanPortfolio + parseFloat(loan.gross_loan_portfolio || 0),
        outstandingBalance: acc.outstandingBalance + parseFloat(loan.outstanding_balance || 0),
        amountInArrears: acc.amountInArrears + parseFloat(loan.amount_in_arrears || 0),
    }), { grossLoanPortfolio: 0, outstandingBalance: 0, amountInArrears: 0 });

    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Written-Off Loan Portfolio Report</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #153505; color: white; position: sticky; top: 0; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .action { float: right; margin-bottom: 20px; }
        button { padding: 6px 20px; cursor: pointer; background-color: #153505; color: white; border: none; border-radius: 4px; margin-left: 5px; }
        .text-right { text-align: right; }
        h1 { color: #153505; }
        .summary-section { background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #153505; }
      </style>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
      <script>
        function exportData(type) {
          const fileName = 'written_off_portfolio_report.' + type;
          const table = document.getElementById("data-table");
          const wb = XLSX.utils.table_to_book(table);
          XLSX.writeFile(wb, fileName);
        }
      </script>
    </head>
    <body>
      <h1>Written-Off Loan Portfolio Report</h1>
      <p>Generated on: ${moment().format('YYYY-MM-DD HH:mm:ss')}</p>
      <div class="summary-section">
        <strong>Total Loans:</strong> ${loanData.length.toLocaleString()} |
        <strong>Gross Portfolio:</strong> MWK ${formatNumber(totals.grossLoanPortfolio)} |
        <strong>Outstanding Balance:</strong> MWK ${formatNumber(totals.outstandingBalance)} |
        <strong>Amount in Arrears:</strong> MWK ${formatNumber(totals.amountInArrears)}
      </div>
      <div class="action">
        <span>Export table to:</span>
        <button onclick="exportData('xlsx')">Xlsx</button>
        <button onclick="exportData('csv')">CSV</button>
      </div>
      ${renderCanonicalLoanTableHtml(loanData, 'data-table')}
      <div style="margin-top: 20px; text-align: center; color: #666; font-size: 12px;">
        <p>© Sycamore Credit ${new Date().getFullYear()}. All rights reserved.</p>
      </div>
    </body>
    </html>`;
}

/**
 * Format number with commas for display
 * @param {string|number} number - Number to format
 * @returns {string} - Formatted number
 */
function formatNumber(number) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
}

module.exports = {
    generateLoanPortfolioWriteOffReport
};