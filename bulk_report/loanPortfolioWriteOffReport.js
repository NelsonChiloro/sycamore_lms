// loanPortfolioReport.js - Enhanced with country join and removed village
const { query, sqlBranchJoin, determineRBMClassification } = require('./databaseHelpers');
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
    const { user, branch, branchgp, product, status, from, to } = options;

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
                    WHEN g.group_id IS NOT NULL THEN CONCAT(g.group_name, ' ', g.group_code)
                    WHEN ic.id IS NOT NULL THEN CONCAT(ic.Firstname, ' ', ic.Lastname, '(', ic.ClientId, ')')
                    ELSE NULL
                    END AS customer_name,
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
                loan.loan_customer AS cid,
                approver.Firstname AS approverfname,
                approver.Lastname AS approverlname,
                rejecter.Firstname AS rejecterfname,
                rejecter.Lastname AS rejecterlname,
                disburser.Firstname AS disburserfname,
                disburser.Lastname AS disburserlname,
                roff.Firstname AS rofffname,
                roff.Lastname AS rofflname,
                b.BranchName,
                loan.customer_type,

                # Add last credit transaction date
                (
                    SELECT MAX(server_time) 
                    FROM transaction 
                    WHERE account_number = loan.loan_number 
                    AND transaction_type = 'deposit' 
                    AND reversed = 'NO'
                ) AS last_credit_date,
                
                # Add total collateral value
                (
                    SELECT SUM(estimated_price) 
                    FROM collateral 
                    WHERE loan_id = loan.loan_id
                ) AS collateral_value,
                
                # Add loan maturity date (last payment schedule)
                (
                    SELECT MAX(payment_schedule)
                    FROM payement_schedules
                    WHERE loan_id = loan.loan_id
                ) AS maturity_date,
                
                # Add total expected installments
                (
                    SELECT SUM(amount)
                    FROM payement_schedules
                    WHERE loan_id = loan.loan_id
                ) AS total_expected_installments,
                
                # Add actual payments
                (
                    SELECT SUM(paid_amount)
                    FROM payement_schedules
                    WHERE loan_id = loan.loan_id
                ) AS actual_payments
            FROM
                loan
                LEFT JOIN
                loan_products ON loan_products.loan_product_id = loan.loan_product
                LEFT JOIN
                employees e ON e.id = loan.loan_added_by
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

        if (user !== 'All') {
            sql += ` AND loan.loan_added_by = '${user}'`;
        }

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

        // Process each loan to get additional information
        const totalLoans = loanData.length;
        let processedCount = 0;

        // Process loans in batches to update progress
        for (const loan of loanData) {
            processedCount++;

            // Calculate the percentage of processed data
            const processedPercentage = 50 + Math.floor((processedCount / totalLoans) * 40);
            reportTrackers[reportId].percentage = processedPercentage;

            // Calculate loan cycle
            try {
                const cycleQuery = `
                    SELECT COUNT(*) as cycle_count
                    FROM loan
                    WHERE
                        loan_customer = ?
                      AND customer_type = ?
                      AND loan_date <= ?
                `;

                const cycleResult = await query(cycleQuery, [
                    loan.loan_customer,
                    loan.customer_type,
                    loan.loan_date
                ]);

                loan.cycle = cycleResult[0]?.cycle_count || 1;
            } catch (error) {
                console.error(`Error calculating cycle for loan ${loan.loan_id}:`, error);
                loan.cycle = 1;
            }

            // Calculate amount in arrears
            try {
                const arrearsQuery = `
                    SELECT
                        SUM(amount - paid_amount) as amount_in_arrears,
                        DATEDIFF(CURDATE(), MIN(payment_schedule)) as days_in_arrears
                    FROM
                        payement_schedules
                    WHERE
                        loan_id = ?
                      AND payment_schedule < CURDATE()
                      AND status != 'PAID'
                `;

                const arrearsResult = await query(arrearsQuery, [loan.loan_id]);

                loan.amount_in_arrears = arrearsResult[0]?.amount_in_arrears || 0;
                loan.days_in_arrears = parseInt(arrearsResult[0]?.days_in_arrears || 0, 10);
                loan.rbm_classification = determineRBMClassification(loan.days_in_arrears);
            } catch (error) {
                console.error(`Error calculating arrears for loan ${loan.loan_id}:`, error);
                loan.amount_in_arrears = 0;
                loan.days_in_arrears = 0;
            }

            // Note: We don't need to query these again if using the subqueries in the main SQL,
            // but leaving this in as a backup if the subqueries approach doesn't work well

            // If any value is missing from subqueries, try to fetch it separately
            if (!loan.last_credit_date) {
                try {
                    const lastCreditQuery = `
                        SELECT MAX(server_time) as last_credit_date
                        FROM transaction
                        WHERE account_number = ?
                          AND transaction_type = 'deposit'
                          AND reversed = 'NO'
                    `;

                    const lastCreditResult = await query(lastCreditQuery, [loan.loan_number]);
                    loan.last_credit_date = lastCreditResult[0]?.last_credit_date || null;
                } catch (error) {
                    console.error(`Error fetching last credit transaction for loan ${loan.loan_id}:`, error);
                    loan.last_credit_date = null;
                }
            }

            if (!loan.collateral_value) {
                try {
                    const collateralQuery = `
                        SELECT SUM(estimated_price) as total_collateral_value
                        FROM collateral
                        WHERE loan_id = ?
                    `;

                    const collateralResult = await query(collateralQuery, [loan.loan_id]);
                    loan.collateral_value = collateralResult[0]?.total_collateral_value || 0;
                } catch (error) {
                    console.error(`Error calculating collateral value for loan ${loan.loan_id}:`, error);
                    loan.collateral_value = 0;
                }
            }

            if (!loan.maturity_date) {
                try {
                    const maturityQuery = `
                        SELECT MAX(payment_schedule) as maturity_date
                        FROM payement_schedules
                        WHERE loan_id = ?
                    `;

                    const maturityResult = await query(maturityQuery, [loan.loan_id]);
                    loan.maturity_date = maturityResult[0]?.maturity_date || null;
                } catch (error) {
                    console.error(`Error fetching maturity date for loan ${loan.loan_id}:`, error);
                    loan.maturity_date = null;
                }
            }
        }

        // Generate HTML report
        const html = generateHTML(loanData);

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
 * Generate HTML report from loan data
 * @param {Array} loanData - Array of loan objects
 * @returns {string} - HTML content
 */
function generateHTML(loanData) {
    // Start building HTML
    let html = `
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Loan Portfolio Report</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          margin: 0;
          padding: 10px;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          font-size: 12px;
        }
        th, td {
          border: 1px solid #ddd;
          padding: 8px;
          text-align: left;
        }
        th {
          background-color: #153505;
          color: white;
          position: sticky;
          top: 0;
        }
        tr:nth-child(even) {
          background-color: #f9f9f9;
        }
        tr:hover {
          background-color: #f1f1f1;
        }
        .action {
          float: right;
          margin-bottom: 20px;
        }
        span {
          margin-right: 20px;
        }
        button {
          padding: 6px 20px;
          cursor: pointer;
          background-color: #153505;
          color: white;
          border: none;
          border-radius: 4px;
        }
        .text-right {
          text-align: right;
        }
        h1 {
          color: #153505;
        }
      </style>
      <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
      <script>
        function exportData(type) {
          const fileName = 'loan_portfolio_report.' + type;
          const table = document.getElementById("data-table");
          const wb = XLSX.utils.table_to_book(table);
          XLSX.writeFile(wb, fileName);
        }
      </script>
    </head>
    <body>
      <h1>Loan Portfolio Report</h1>
      <p>Generated on: ${moment().format('YYYY-MM-DD HH:mm:ss')}</p>
      
      <div class="action">
        <span>Export table to:</span>
        <button id="b1" onclick="exportData('xlsx')">Xlsx</button>
        <button onclick="exportData('xls')">Xls</button>
        <button onclick="exportData('csv')">CSV</button>
      </div>
      
      <table id="data-table" class="tableCss">
        <thead>
          <tr>
            <th>#</th>
            <th>Branch Name</th>
            <th>Loan Number</th>
            <th>Loan Product</th>
            <th>Loan Customer</th>
            <th>Cycle</th>
            <th>Loan Date</th>
            <th>Loan Principal (MWK)</th>
            <th>Loan Period</th>
            <th>Period Type</th>
            <th>Loan Interest</th>
            <th>Loan Interest amount (MWK)</th>
            <th>Total Expected (MWK)</th>
            <th>Loan Installment Amount (MWK)</th>
            <th>Outstanding Balance (MWK)</th>
            <th>Outstanding Interest (MWK)</th>
            <th>Principal In Arrears (MWK)</th>
            <th>Amount In Arrears (MWK)</th>
            <th>Days In Arrears</th>
            <th>RBM Loan Classification</th>
            <th>Last Credit Trx Date</th>
            <th>Collateral Value (MWK)</th>
            <th>Effective Date</th>
            <th>Loan Maturity Date</th>
            <th>Loan Closed Date</th>
            <th>Total Expected Installments (MWK)</th>
            <th>Actual Payments (MWK)</th>
            <th>Collection Rate (%)</th>
            <th>Loan Status</th>
            <th>Loan officer</th>
            <th>Loan Added Date</th>
  `;

    // Add individual customer columns only if there's at least one individual customer
    const hasIndividualCustomers = loanData.some(loan => loan.customer_type === 'individual');

    if (hasIndividualCustomers) {
        html += `
            <th>Gender</th>
            <th>Date of Birth</th>
            <th>Phone Number</th>
            <th>Email Address</th>
            <th>Address Line 1</th>
            <th>Province</th>
            <th>City</th>
            <th>Country</th>
            <th>Marital Status</th>
            <th>Profession</th>
            <th>Source of Income</th>
            <th>Monthly Income</th>
    `;
    }

    html += `
          </tr>
        </thead>
        <tbody>
  `;

    // Add rows for each loan
    loanData.forEach((loan, index) => {
        // Format numbers with 2 decimal places
        const principal = parseFloat(loan.loan_principal || 0).toFixed(2);
        const interestAmount = parseFloat(loan.loan_interest_amount || 0).toFixed(2);
        const totalExpected = parseFloat(loan.loan_amount_total || 0).toFixed(2);
        const installmentAmount = parseFloat(loan.loan_amount_term || 0).toFixed(2);
        const outstandingBalance = parseFloat(loan.outstanding_balance || 0).toFixed(2);
        const monthlyIncome = parseFloat(loan.GrossMonthlyIncome || 0).toFixed(2);
        const amountInArrears = parseFloat(loan.amount_in_arrears || 0).toFixed(2);
        const daysInArrears = parseInt(loan.days_in_arrears || 0);
        const collateralValue = parseFloat(loan.collateral_value || 0).toFixed(2);
        const totalExpectedInstallments = parseFloat(loan.total_expected_installments || 0).toFixed(2);
        const actualPayments = parseFloat(loan.actual_payments || 0).toFixed(2);

        // Calculate collection rate (as a percentage)
        let collectionRate = 0;
        if (parseFloat(totalExpectedInstallments) > 0) {
            collectionRate = (parseFloat(actualPayments) / parseFloat(totalExpectedInstallments) * 100).toFixed(2);
        }

        // Customer name handling
        let customerName = loan.customer_name || 'Unknown';
        let customerUrl = '';

        if (loan.customer_type === 'group') {
            customerUrl = `Customer_groups/members/${loan.loan_customer}`;
        } else {
            customerUrl = `Individual_customers/view/${loan.loan_customer}`;
        }

        html += `
      <tr>
        <td>${index + 1}</td>
        <td>${loan.BranchName || 'Blantyre'}</td>
        <td>${loan.loan_number || ''}</td>
        <td>${(loan.product_name || '') + '(' + (loan.product_code || '') + ')'}</td>
        <td><a href="${customerUrl}">${customerName}</a></td>
        <td>${loan.cycle || 1}</td>
        <td>${formatDisplayDate(loan.loan_date)}</td>
        <td class="text-right">${formatNumber(principal)}</td>
        <td>${loan.loan_period || ''}</td>
        <td>${loan.period_type || ''}</td>
        <td>${loan.loan_interest || ''}%</td>
        <td class="text-right">${formatNumber(interestAmount)}</td>
        <td class="text-right">${formatNumber(totalExpected)}</td>
        <td class="text-right">${formatNumber(installmentAmount)}</td>
        <td class="text-right">${formatNumber(outstandingBalance)}</td>
        <td class="text-right">0.00</td>
        <td class="text-right">0.00</td>
        <td class="text-right">${formatNumber(amountInArrears)}</td>
        <td class="text-right">${daysInArrears}</td>
        <td>${loan.rbm_classification || determineRBMClassification(daysInArrears)}</td>
        <td>${formatDisplayDate(loan.last_credit_date)}</td>
        <td class="text-right">${formatNumber(collateralValue)}</td>
        <td>${formatDisplayDate(loan.loan_date)}</td>
        <td>${formatDisplayDate(loan.maturity_date)}</td>
        <td>${loan.loan_status === 'CLOSED' ? formatDisplayDate(loan.last_credit_date) : ''}</td>
        <td class="text-right">${formatNumber(totalExpectedInstallments)}</td>
        <td class="text-right">${formatNumber(actualPayments)}</td>
        <td class="text-right">${collectionRate}%</td>
        <td>${loan.loan_status || ''}</td>
        <td>${(loan.efname || '') + ' ' + (loan.elname || '')}</td>
        <td>${formatDisplayDate(loan.loan_added_date)}</td>
    `;

        // Add individual customer details if needed
        if (hasIndividualCustomers) {
            if (loan.customer_type === 'individual') {
                html += `
          <td>${loan.Gender || ''}</td>
          <td>${formatDisplayDate(loan.DateOfBirth)}</td>
          <td>${loan.PhoneNumber || ''}</td>
          <td>${loan.EmailAddress || ''}</td>
          <td>${loan.AddressLine1 || ''}</td>
          <td>${loan.Province || ''}</td>
          <td>${loan.City || ''}</td>
          <td>${loan.country_name || loan.country_code || ''}</td>
          <td>${loan.Marital_status || ''}</td>
          <td>${loan.Profession || ''}</td>
          <td>${loan.SourceOfIncome || ''}</td>
          <td class="text-right">${formatNumber(monthlyIncome)}</td>
        `;
            } else {
                // Empty cells for group customers
                html += `
          <td colspan="12"></td>
        `;
            }
        }

        html += `
      </tr>
    `;
    });

    // Add summary and close HTML
    const totalPrincipal = loanData.reduce((sum, loan) => sum + parseFloat(loan.loan_principal || 0), 0);
    const totalInterest = loanData.reduce((sum, loan) => sum + parseFloat(loan.loan_interest_amount || 0), 0);
    const totalExpected = loanData.reduce((sum, loan) => sum + parseFloat(loan.loan_amount_total || 0), 0);
    const totalAmountInArrears = loanData.reduce((sum, loan) => sum + parseFloat(loan.amount_in_arrears || 0), 0);
    const totalCollateralValue = loanData.reduce((sum, loan) => sum + parseFloat(loan.collateral_value || 0), 0);

    // Calculate the colspan based on whether we have individual customer data
    const summaryColspan = hasIndividualCustomers ? '7' : '7';

    html += `
        <tr style="font-weight: bold; background-color: #e9ffe9;">
          <td colspan="${summaryColspan}" class="text-right">Totals:</td>
          <td class="text-right">${formatNumber(totalPrincipal)}</td>
          <td colspan="3"></td>
          <td class="text-right">${formatNumber(totalInterest)}</td>
          <td class="text-right">${formatNumber(totalExpected)}</td>
          <td colspan="4"></td>
          <td class="text-right">${formatNumber(totalAmountInArrears)}</td>
          <td colspan="2"></td>
          <td class="text-right">${formatNumber(totalCollateralValue)}</td>
          <td colspan="${hasIndividualCustomers ? '18' : '6'}"></td>
        </tr>
        </tbody>
      </table>
      
      <div style="margin-top: 20px; text-align: center; color: #666; font-size: 12px;">
        <p>© Sycamore Credit ${new Date().getFullYear()}. All rights reserved.</p>
      </div>
    </body>
    </html>
  `;

    return html;
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