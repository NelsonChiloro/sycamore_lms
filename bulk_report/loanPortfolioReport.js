// loanPortfolioReport.js - With detailed performance logging
const { query, sqlBranchJoin, determineRBMClassification } = require('./databaseHelpers');
const moment = require('moment');
const util = require('util');

// Helper function to log with timestamp and duration
function logStage(stageName, startTime = null) {
    const now = Date.now();
    const timestamp = new Date().toISOString().split('T')[1].split('.')[0];

    if (startTime) {
        const duration = now - startTime;
        console.log(`[${timestamp}] ✅ ${stageName} - COMPLETED in ${duration}ms (${(duration/1000).toFixed(1)}s)`);
    } else {
        console.log(`[${timestamp}] 🚀 ${stageName} - STARTING...`);
    }

    return now;
}

/**
 * Generate loan portfolio report with detailed performance logging
 */
async function generateLoanPortfolioReport(options, reportId, reportTrackers) {
    const { user, branch, branchgp, product, status, from, to } = options;

    const overallStart = logStage('LOAN PORTFOLIO REPORT GENERATION');

    console.log('📊 REPORT FILTERS:');
    console.log(`   - Branch: ${branch}`);
    console.log(`   - User/Officer: ${user}`);
    console.log(`   - Product: ${product}`);
    console.log(`   - Status: ${status}`);
    console.log(`   - Date Range: ${from} to ${to}`);
    console.log('');

    try {
        // Stage 1: Database Connection (using pool)
        let stageStart = logStage('Database Connection');
        // No need to explicitly connect - pool handles this
        logStage('Database Connection', stageStart);
        reportTrackers[reportId].percentage = 5;

        // Stage 2: Build and Execute Main Query
        stageStart = logStage('Building Main SQL Query');

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
                ic.Province,
                ic.City,
                ic.Marital_status,
                ic.Profession,
                ic.SourceOfIncome,
                ic.GrossMonthlyIncome,
                CONCAT(COALESCE(e.Firstname, ''), ' ', COALESCE(e.Lastname, '')) as loan_officer,
                COALESCE(b.BranchName, 'Unknown Branch') as branch_name,
                loan.customer_type
            FROM loan
            LEFT JOIN loan_products ON loan_products.loan_product_id = loan.loan_product
            LEFT JOIN employees e ON e.id = loan.loan_added_by
            LEFT JOIN individual_customers ic ON loan.loan_customer = ic.id AND loan.customer_type = 'individual'
            LEFT JOIN \`groups\` g ON loan.loan_customer = g.group_id AND loan.customer_type = 'group'
            ${sqlBranchJoin('loan', 'b')}
            WHERE loan.loan_status IN ('ACTIVE', 'CLOSED', 'WRITTEN_OFF')
        `;

        // Add filters
        let filterCount = 0;
        if (branch !== 'All') {
            const branchEsc = String(branch).replace(/'/g, "''");
            sql += ` AND (
                loan.branch = '${branchEsc}'
                OR loan.branch IN (SELECT Code FROM branches WHERE id = '${branchEsc}')
                OR loan.branch IN (SELECT BranchCode FROM branches WHERE id = '${branchEsc}')
            )`;
            filterCount++;
        }
        if (status !== 'All') {
            sql += ` AND loan.loan_status = '${status}'`;
            filterCount++;
        }
        if (user !== 'All') {
            sql += ` AND loan.loan_added_by = '${user}'`;
            filterCount++;
        }
        if (product !== 'All') {
            sql += ` AND loan.loan_product = '${product}'`;
            filterCount++;
        }
        if (from && to) {
            sql += ` AND DATE(loan.loan_added_date) BETWEEN '${formatDate(from)}' AND '${formatDate(to)}'`;
            filterCount++;
        } else if (from) {
            sql += ` AND DATE(loan.loan_added_date) >= '${formatDate(from)}'`;
            filterCount++;
        } else if (to) {
            sql += ` AND DATE(loan.loan_added_date) <= '${formatDate(to)}'`;
            filterCount++;
        }

        sql += ` ORDER BY loan.loan_id DESC`;

        console.log(`   📝 Applied ${filterCount} filters to main query`);
        logStage('Building Main SQL Query', stageStart);

        // Stage 3: Execute Main Query
        stageStart = logStage('Executing Main Loan Query');
        const loanData = await query(sql);
        logStage(`Main Loan Query (Found ${loanData.length} loans)`, stageStart);

        reportTrackers[reportId].percentage = 20;

        if (loanData.length === 0) {
            console.log('⚠️  No loans found matching criteria - generating empty report');
            reportTrackers[reportId].percentage = 100;
            // Connection pool handles cleanup automatically
            logStage('LOAN PORTFOLIO REPORT GENERATION', overallStart);
            return generateHTML([]);
        }

        // Stage 4: Prepare Batch Queries
        stageStart = logStage('Preparing Batch Queries');
        const loanIds = loanData.map(loan => loan.loan_id);
        const loanNumbers = loanData.map(loan => loan.loan_number);

        console.log(`   🔍 Processing ${loanIds.length} loan IDs`);
        console.log(`   📞 Will execute 4 batch queries instead of ${loanIds.length * 3}+ individual queries`);
        logStage('Preparing Batch Queries', stageStart);

        // Stage 5: Payment Schedules Batch Query
        stageStart = logStage('Payment Schedules Batch Query');
        const paymentSchedulesQuery = `
            SELECT 
                loan_id,
                SUM(CASE WHEN payment_schedule <= CURDATE() THEN amount ELSE 0 END) as total_expected_installments,
                SUM(paid_amount) as actual_payments,
                SUM(CASE WHEN payment_schedule < CURDATE() AND status != 'PAID' THEN (amount - paid_amount) ELSE 0 END) as amount_in_arrears,
                MIN(CASE WHEN payment_schedule < CURDATE() AND status != 'PAID' THEN DATEDIFF(CURDATE(), payment_schedule) ELSE NULL END) as days_in_arrears,
                MAX(payment_schedule) as maturity_date
            FROM payement_schedules
            WHERE loan_id IN (${loanIds.join(',')})
            GROUP BY loan_id
        `;

        const paymentData = await query(paymentSchedulesQuery);
        const paymentMap = {};
        paymentData.forEach(p => {
            paymentMap[p.loan_id] = p;
        });

        console.log(`   📋 Processed payment schedules for ${paymentData.length} loans`);
        logStage('Payment Schedules Batch Query', stageStart);
        reportTrackers[reportId].percentage = 40;

        // Stage 6: Last Payment Dates Batch Query
        stageStart = logStage('Last Payment Dates Batch Query');
        const lastPaymentQuery = `
            SELECT 
                account_number,
                MAX(server_time) as last_credit_date
            FROM transaction
            WHERE account_number IN ('${loanNumbers.join("','")}')
            AND transaction_type = 'deposit'
            AND reversed = 'NO'
            GROUP BY account_number
        `;

        const lastPaymentData = await query(lastPaymentQuery);
        const lastPaymentMap = {};
        lastPaymentData.forEach(p => {
            lastPaymentMap[p.account_number] = p.last_credit_date;
        });

        console.log(`   💳 Found last payment dates for ${lastPaymentData.length} loans`);
        logStage('Last Payment Dates Batch Query', stageStart);
        reportTrackers[reportId].percentage = 55;

        // Stage 7: Collateral Values Batch Query
        stageStart = logStage('Collateral Values Batch Query');
        const collateralQuery = `
            SELECT 
                loan_id,
                SUM(estimated_price) as collateral_value
            FROM collateral
            WHERE loan_id IN (${loanIds.join(',')})
            GROUP BY loan_id
        `;

        const collateralData = await query(collateralQuery);
        const collateralMap = {};
        collateralData.forEach(c => {
            collateralMap[c.loan_id] = c.collateral_value;
        });

        console.log(`   🏠 Found collateral values for ${collateralData.length} loans`);
        logStage('Collateral Values Batch Query', stageStart);
        reportTrackers[reportId].percentage = 70;

        // Stage 8: Loan Cycles Batch Query
        stageStart = logStage('Loan Cycles Batch Query');
        const cycleQuery = `
            SELECT 
                l1.loan_id,
                COUNT(l2.loan_id) as cycle_count
            FROM loan l1
            LEFT JOIN loan l2 ON l1.loan_customer = l2.loan_customer 
                AND l1.customer_type = l2.customer_type 
                AND l2.loan_date <= l1.loan_date
            WHERE l1.loan_id IN (${loanIds.join(',')})
            GROUP BY l1.loan_id
        `;

        const cycleData = await query(cycleQuery);
        const cycleMap = {};
        cycleData.forEach(c => {
            cycleMap[c.loan_id] = c.cycle_count;
        });

        console.log(`   🔄 Calculated cycles for ${cycleData.length} loans`);
        logStage('Loan Cycles Batch Query', stageStart);
        reportTrackers[reportId].percentage = 85;

        // Stage 9: Data Merging and Processing
        stageStart = logStage('Data Merging and Processing');
        let processedCount = 0;

        loanData.forEach(loan => {
            const paymentInfo = paymentMap[loan.loan_id] || {};

            // Assign calculated values
            loan.total_expected_installments = parseFloat(paymentInfo.total_expected_installments || 0);
            loan.actual_payments = parseFloat(paymentInfo.actual_payments || 0);
            loan.amount_in_arrears = parseFloat(paymentInfo.amount_in_arrears || 0);
            loan.days_in_arrears = parseInt(paymentInfo.days_in_arrears || 0);
            loan.rbm_classification = determineRBMClassification(loan.days_in_arrears);
            loan.maturity_date = paymentInfo.maturity_date;
            loan.last_credit_date = lastPaymentMap[loan.loan_number];
            loan.collateral_value = parseFloat(collateralMap[loan.loan_id] || 0);
            loan.cycle = cycleMap[loan.loan_id] || 1;

            // Calculate outstanding balance
            loan.outstanding_balance = parseFloat(loan.loan_amount_total || 0) - parseFloat(loan.actual_payments || 0);

            processedCount++;

            // Log progress every 100 loans
            if (processedCount % 100 === 0) {
                console.log(`   📊 Processed ${processedCount}/${loanData.length} loans (${Math.round((processedCount/loanData.length)*100)}%)`);
            }
        });

        console.log(`   ✅ Successfully merged data for all ${processedCount} loans`);
        logStage('Data Merging and Processing', stageStart);
        reportTrackers[reportId].percentage = 95;

        // Stage 10: HTML Generation
        stageStart = logStage('HTML Report Generation');
        const html = generateHTML(loanData);
        logStage(`HTML Report Generation (${Math.round(html.length/1024)}KB)`, stageStart);

        reportTrackers[reportId].percentage = 100;

        // Final Summary
        const totalTime = Date.now() - overallStart;
        console.log('');
        console.log('📈 PERFORMANCE SUMMARY:');
        console.log(`   ⏱️  Total Time: ${totalTime}ms (${(totalTime/1000).toFixed(1)}s)`);
        console.log(`   📊 Loans Processed: ${loanData.length}`);
        console.log(`   ⚡ Average Time per Loan: ${(totalTime/loanData.length).toFixed(1)}ms`);
        console.log(`   🔍 Database Queries: 5 batch queries (vs ${loanData.length * 3}+ individual)`);
        console.log(`   💾 Report Size: ${Math.round(html.length/1024)}KB`);
        console.log('');

        logStage('LOAN PORTFOLIO REPORT GENERATION', overallStart);

        // Connection pool handles cleanup automatically
        return html;

    } catch (error) {
        console.error('❌ ERROR in Loan Portfolio Report:', error);
        console.error('   Stage: During report generation');
        console.error('   Time elapsed:', Date.now() - overallStart, 'ms');

        // Connection pool handles cleanup automatically
        throw error;
    }
}

/**
 * Format date to YYYY-MM-DD
 */
function formatDate(date) {
    return moment(date).format('YYYY-MM-DD');
}

/**
 * Format date for display
 */
function formatDisplayDate(date) {
    if (!date) return '';
    return moment(date).format('YYYY-MM-DD');
}

/**
 * Generate HTML report - same as before but with performance info
 */
function generateHTML(loanData) {
    const htmlStart = Date.now();

    let html = `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loan Portfolio Report - Performance Optimized</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #153505; color: white; position: sticky; top: 0; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            tr:hover { background-color: #f1f1f1; }
            .action { float: right; margin-bottom: 20px; }
            button { padding: 6px 20px; cursor: pointer; background-color: #153505; color: white; border: none; border-radius: 4px; margin-left: 5px; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            h1 { color: #153505; }
            .performance-info { background-color: #e8f5e8; padding: 10px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #153505; }
            .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 15px; }
            .stat-item { background: white; padding: 10px; border-radius: 3px; text-align: center; border: 1px solid #ddd; }
        </style>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script>
            function exportData(type) {
                const fileName = 'loan_portfolio_report_optimized.' + type;
                const table = document.getElementById("data-table"); 
                const wb = XLSX.utils.table_to_book(table);
                XLSX.writeFile(wb, fileName);
            }
        </script>
    </head>
    <body>
        <h1>Loan Portfolio Report</h1>
        <div class="performance-info">
            <h3>📊 Report Statistics</h3>
            <div class="stats">
                <div class="stat-item">
                    <strong>Total Loans</strong><br>
                    ${loanData.length}
                </div>
                <div class="stat-item">
                    <strong>Report Generated</strong><br>
                    ${moment().format('YYYY-MM-DD HH:mm:ss')}
                </div>
                <div class="stat-item">
                    <strong>Performance</strong><br>
                    Optimized (5 batch queries)
                </div>
            </div>
        </div>
        
        <div class="action">
            <span>Export table to:</span>
            <button onclick="exportData('xlsx')">Excel (xlsx)</button>
            <button onclick="exportData('xls')">Excel (xls)</button>
            <button onclick="exportData('csv')">CSV</button>
        </div>
        
        <table id="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Branch Name</th>
                    <th>Loan Number</th>
                    <th>Product Name</th>
                    <th>Customer Name</th>
                    <th>Customer Group</th>
                    <th>Cycle</th>
                    <th>Loan Date</th>
                    <th>Principal (MWK)</th>
                    <th>Period</th>
                    <th>Interest Rate</th>
                    <th>Total Amount (MWK)</th>
                    <th>Installment (MWK)</th>
                    <th>Outstanding (MWK)</th>
                    <th>Amount in Arrears (MWK)</th>
                    <th>Days in Arrears</th>
                    <th>RBM Loan Classification</th>
                    <th>Last Payment Date</th>
                    <th>Collateral Value (MWK)</th>
                    <th>Maturity Date</th>
                    <th>Expected Installments (MWK)</th>
                    <th>Actual Payments (MWK)</th>
                    <th>Collection Rate (%)</th>
                    <th>Status</th>
                    <th>Loan Officer</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>`;

    // Generate table rows
    loanData.forEach((loan, index) => {
        const collectionRate = loan.total_expected_installments > 0
            ? ((loan.actual_payments / loan.total_expected_installments) * 100).toFixed(2)
            : '0.00';

        html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${loan.branch_name}</td>
                    <td>${loan.loan_number}</td>
                    <td>${loan.product_name} (${loan.product_code})</td>
                    <td>${loan.customer_name}</td>
                    <td>${loan.customer_group_name}</td>
                    <td>${loan.cycle}</td>
                    <td>${formatDisplayDate(loan.loan_date)}</td>
                    <td class="text-right">${formatNumber(loan.loan_principal)}</td>
                    <td>${loan.loan_period} ${loan.period_type}</td>
                    <td class="text-center">${loan.loan_interest}%</td>
                    <td class="text-right">${formatNumber(loan.loan_amount_total)}</td>
                    <td class="text-right">${formatNumber(loan.loan_amount_term)}</td>
                    <td class="text-right">${formatNumber(loan.outstanding_balance)}</td>
                    <td class="text-right">${formatNumber(loan.amount_in_arrears)}</td>
                    <td class="text-center">${loan.days_in_arrears}</td>
                    <td>${loan.rbm_classification || 'Standard'}</td>
                    <td>${formatDisplayDate(loan.last_credit_date)}</td>
                    <td class="text-right">${formatNumber(loan.collateral_value)}</td>
                    <td>${formatDisplayDate(loan.maturity_date)}</td>
                    <td class="text-right">${formatNumber(loan.total_expected_installments)}</td>
                    <td class="text-right">${formatNumber(loan.actual_payments)}</td>
                    <td class="text-right">${collectionRate}%</td>
                    <td>${loan.loan_status}</td>
                    <td>${loan.loan_officer}</td>
                    <td>${formatDisplayDate(loan.loan_added_date)}</td>
                </tr>`;
    });

    // Add totals row
    const totals = loanData.reduce((acc, loan) => ({
        principal: acc.principal + parseFloat(loan.loan_principal || 0),
        total_amount: acc.total_amount + parseFloat(loan.loan_amount_total || 0),
        outstanding: acc.outstanding + parseFloat(loan.outstanding_balance || 0),
        arrears: acc.arrears + parseFloat(loan.amount_in_arrears || 0),
        collateral: acc.collateral + parseFloat(loan.collateral_value || 0),
        expected: acc.expected + parseFloat(loan.total_expected_installments || 0),
        actual: acc.actual + parseFloat(loan.actual_payments || 0)
    }), { principal: 0, total_amount: 0, outstanding: 0, arrears: 0, collateral: 0, expected: 0, actual: 0 });

    const overallCollectionRate = totals.expected > 0 ? ((totals.actual / totals.expected) * 100).toFixed(2) : '0.00';

    html += `
                <tr style="font-weight: bold; background-color: #e8f5e8;">
                    <td colspan="7">TOTALS:</td>
                    <td class="text-right">${formatNumber(totals.principal)}</td>
                    <td colspan="2"></td>
                    <td class="text-right">${formatNumber(totals.total_amount)}</td>
                    <td></td>
                    <td class="text-right">${formatNumber(totals.outstanding)}</td>
                    <td class="text-right">${formatNumber(totals.arrears)}</td>
                    <td colspan="2"></td>
                    <td class="text-right">${formatNumber(totals.collateral)}</td>
                    <td></td>
                    <td class="text-right">${formatNumber(totals.expected)}</td>
                    <td class="text-right">${formatNumber(totals.actual)}</td>
                    <td class="text-right">${overallCollectionRate}%</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: center; color: #666; font-size: 12px;">
            <p>© Sycamore Credit ${new Date().getFullYear()} - Performance Optimized | HTML Generation: ${Date.now() - htmlStart}ms</p>
        </div>
    </body>
    </html>`;

    return html;
}

/**
 * Format number with commas
 */
function formatNumber(number) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number || 0);
}

module.exports = {
    generateLoanPortfolioReport
};