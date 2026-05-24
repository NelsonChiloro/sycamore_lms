const moment = require('moment');
const { determineRBMClassification } = require('./databaseHelpers');

/**
 * Generate Arrears Report HTML
 * @param {Object} filterOptions - Filter parameters
 * @param {number} reportId - Report ID for tracking
 * @param {Object} reportTrackers - Report tracking object
 * @param {Object} db - Database connection
 * @returns {Promise<string>} Generated HTML content
 */
async function generateArrearsReport(filterOptions, reportId, reportTrackers, db) {
    return new Promise(async (resolve, reject) => {
        try {
            console.log('Starting Arrears Report generation with filters:', filterOptions);
            
            // Initialize progress tracking
            reportTrackers[reportId].percentage = 5;

            // Build the query for loans in arrears
            let query = `
                SELECT 
                    l.loan_id,
                    l.loan_number,
                    l.loan_customer,
                    l.customer_type,
                    l.loan_principal as amount_disbursed,
                    l.loan_amount_term as repayment_amount,
                    l.loan_period as term,
                    l.period_type as repayment_frequency,
                    l.loan_date as disbursement_date,
                    l.loan_added_by as officer_id,
                    l.branch as loan_branch,
                    lp.product_name,
                    b.BranchName,
                    e.Firstname as officer_firstname,
                    e.Lastname as officer_lastname,
                    SUM(CASE
                        WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < CURDATE()
                            THEN (COALESCE(ps.interest, 0) + COALESCE(ps.padmin_fee, 0) + COALESCE(ps.ploan_cover, 0))
                        ELSE 0
                    END) as loan_charges,
                    MIN(CASE
                        WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < CURDATE()
                            THEN ps.payment_schedule
                        ELSE NULL
                    END) as due_date,
                    SUM(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < CURDATE() THEN 1 ELSE 0 END) as num_missed_payments,
                    SUM(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < CURDATE() THEN (ps.amount - ps.paid_amount) ELSE 0 END) as total_arrears,
                    MAX(ps.paid_date) as last_transaction_date,
                    DATEDIFF(CURDATE(), MIN(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < CURDATE() THEN ps.payment_schedule ELSE NULL END)) as arrear_days
                FROM loan l
                LEFT JOIN payement_schedules ps ON ps.loan_id = l.loan_id
                LEFT JOIN loan_products lp ON lp.loan_product_id = l.loan_product
                LEFT JOIN branches b ON b.id = l.branch
                LEFT JOIN employees e ON e.id = l.loan_added_by
                WHERE l.loan_status IN ('APPROVED', 'ACTIVE') 
                AND l.disbursed = 'Yes'
            `;

            let queryParams = [];

            // Add date filters - support full range and one-sided filtering
            if (filterOptions.start_date && filterOptions.end_date) {
                query += ` AND COALESCE(DATE(l.disbursed_date), l.loan_date) BETWEEN ? AND ?`;
                queryParams.push(filterOptions.start_date, filterOptions.end_date);
            } else if (filterOptions.start_date) {
                query += ` AND COALESCE(DATE(l.disbursed_date), l.loan_date) >= ?`;
                queryParams.push(filterOptions.start_date);
            } else if (filterOptions.end_date) {
                query += ` AND COALESCE(DATE(l.disbursed_date), l.loan_date) <= ?`;
                queryParams.push(filterOptions.end_date);
            }

            if (filterOptions.officer_id && filterOptions.officer_id !== 'All') {
                query += ` AND l.loan_added_by = ?`;
                queryParams.push(filterOptions.officer_id);
            }

            if (filterOptions.branch_id && filterOptions.branch_id !== 'All') {
                query += ` AND l.branch = ?`;
                queryParams.push(filterOptions.branch_id);
            }

            query += `
                GROUP BY l.loan_id
                HAVING num_missed_payments > 0
                ORDER BY arrear_days DESC
            `;

            reportTrackers[reportId].percentage = 10;

            // Execute the query
            db.query(query, queryParams, async (err, results) => {
                if (err) {
                    console.error('Database query error:', err);
                    return reject(err);
                }

                console.log(`Found ${results.length} loans in arrears`);
                reportTrackers[reportId].percentage = 20;

                try {
                    // Generate HTML content
                    const html = await generateArrearsHTML(results, filterOptions, reportId, reportTrackers, db);
                    reportTrackers[reportId].percentage = 100;
                    resolve(html);
                } catch (htmlErr) {
                    console.error('Error generating HTML:', htmlErr);
                    reject(htmlErr);
                }
            });

        } catch (error) {
            console.error('Error in generateArrearsReport:', error);
            reject(error);
        }
    });
}

/**
 * Generate HTML content for Arrears Report
 */
async function generateArrearsHTML(loans, filterOptions, reportId, reportTrackers, db) {
    return new Promise(async (resolve, reject) => {
        try {
            const totalLoans = loans.length;
            let processedCount = 0;

            // Header and styles
            let html = `
<!DOCTYPE html>
<html>
<head>
    <title>Arrears Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .filter-info { background-color: #f5f5f5; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .summary { background-color: #e8f4fd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #153505; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .amount { text-align: right; }
        .days { text-align: center; }
        .export-buttons { margin-bottom: 15px; text-align: right; }
        .export-buttons button { 
            padding: 6px 12px; background-color: #153505; color: white; 
            border: none; border-radius: 3px; margin-left: 5px; cursor: pointer; 
        }
        .export-buttons button:hover { background-color: #2e7d32; }
        .risk-high { background-color: #ffebee; }
        .risk-medium { background-color: #fff3e0; }
        .risk-low { background-color: #f3e5f5; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportData(type) {
            const fileName = 'Arrears_Report.' + type;
            const table = document.getElementById("arrearsTable");
            const wb = XLSX.utils.table_to_book(table);
            XLSX.writeFile(wb, fileName);
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>ARREARS REPORT</h1>
        <h3>Generated on: ${moment().format('MMMM Do, YYYY [at] HH:mm:ss')}</h3>
    </div>

    <div class="filter-info">
        <strong>Filter Applied:</strong><br>
        Date Range: ${filterOptions.start_date ? moment(filterOptions.start_date).format('MMM DD, YYYY') : 'All Time'} - 
        ${filterOptions.end_date ? moment(filterOptions.end_date).format('MMM DD, YYYY') : 'Present'}<br>
        Officer: ${filterOptions.officer_name || 'All Officers'}<br>
        Branch: ${filterOptions.branch_name || 'All Branches'}
    </div>
`;

            // Calculate summary statistics
            let totalArrears = 0;
            let totalLoansCount = loans.length;
            let highRiskCount = 0; // >90 days
            let mediumRiskCount = 0; // 31-90 days
            let lowRiskCount = 0; // 1-30 days

            loans.forEach(loan => {
                totalArrears += parseFloat(loan.total_arrears || 0);
                const days = parseInt(loan.arrear_days || 0);
                if (days > 90) highRiskCount++;
                else if (days > 30) mediumRiskCount++;
                else lowRiskCount++;
            });

            html += `
    <div class="summary">
        <h3>SUMMARY</h3>
        <table style="width: 50%; margin: 0;">
            <tr><td><strong>Total Loans in Arrears:</strong></td><td>${totalLoansCount}</td></tr>
            <tr><td><strong>Total Arrears Amount (MWK):</strong></td><td>${totalArrears.toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>
            <tr><td><strong>High Risk (>90 days):</strong></td><td>${highRiskCount} loans</td></tr>
            <tr><td><strong>Medium Risk (31-90 days):</strong></td><td>${mediumRiskCount} loans</td></tr>
            <tr><td><strong>Low Risk (1-30 days):</strong></td><td>${lowRiskCount} loans</td></tr>
        </table>
    </div>

    <div class="export-buttons">
        <span>Export as:</span>
        <button onclick="exportData('xlsx')">Excel (xlsx)</button>
        <button onclick="exportData('xls')">Excel (xls)</button>
        <button onclick="exportData('csv')">CSV</button>
    </div>

    <table id="arrearsTable">
        <thead>
            <tr>
                <th>Loan No.</th>
                <th>Client Name</th>
                <th>Customer Group</th>
                <th>Product</th>
                <th>Amount Disbursed (MWK)</th>
                <th>Loan Charges (MWK)</th>
                <th>Term</th>
                <th>Repayment Frequency</th>
                <th>Repayment Amount (MWK)</th>
                <th>Due Date</th>
                <th>Missed Payments</th>
                <th>Total Arrears (MWK)</th>
                <th>Last Transaction</th>
                <th>Days in Arrears</th>
                <th>RBM Loan Classification</th>
                <th>Risk Level</th>
                <th>Loan Officer</th>
                <th>Branch</th>
            </tr>
        </thead>
        <tbody>
`;

            // Process each loan
            for (const loan of loans) {
                processedCount++;
                const progressPercent = 20 + (processedCount / totalLoans) * 70;
                reportTrackers[reportId].percentage = Math.round(progressPercent);

                // Get customer name and group (both names for all reports per Issue 12)
                let customerName = '';
                let customerGroupName = 'N/A';
                if (loan.customer_type === 'individual') {
                    const customerQuery = 'SELECT Firstname, Lastname FROM individual_customers WHERE id = ?';
                    const customerResult = await queryDatabase(db, customerQuery, [loan.loan_customer]);
                    if (customerResult.length > 0) {
                        customerName = `${customerResult[0].Firstname} ${customerResult[0].Lastname}`;
                    } else {
                        customerName = 'Unknown Customer';
                    }
                    // Get group membership for individual customers
                    const groupQuery = 'SELECT g.group_name, g.group_code FROM customer_groups cg JOIN `groups` g ON g.group_id = cg.group_id WHERE cg.customer = ?';
                    const groupResult = await queryDatabase(db, groupQuery, [loan.loan_customer]);
                    if (groupResult.length > 0) {
                        customerGroupName = `${groupResult[0].group_name} (${groupResult[0].group_code})`;
                    }
                } else if (loan.customer_type === 'group') {
                    const groupQuery = 'SELECT group_name, group_code FROM `groups` WHERE group_id = ?';
                    const groupResult = await queryDatabase(db, groupQuery, [loan.loan_customer]);
                    if (groupResult.length > 0) {
                        customerName = `${groupResult[0].group_name} (${groupResult[0].group_code})`;
                        customerGroupName = groupResult[0].group_name;
                    } else {
                        customerName = 'Unknown Group';
                        customerGroupName = 'Unknown Group';
                    }
                }

                // Determine risk level and CSS class
                const arrearDays = parseInt(loan.arrear_days || 0);
                const rbmClassification = determineRBMClassification(arrearDays);
                let riskLevel = '';
                let rowClass = '';
                
                if (arrearDays > 90) {
                    riskLevel = 'High Risk';
                    rowClass = 'risk-high';
                } else if (arrearDays > 30) {
                    riskLevel = 'Medium Risk';
                    rowClass = 'risk-medium';
                } else {
                    riskLevel = 'Low Risk';
                    rowClass = 'risk-low';
                }

                html += `
            <tr class="${rowClass}">
                <td>${loan.loan_number || 'N/A'}</td>
                <td>${customerName}</td>
                <td>${customerGroupName}</td>
                <td>${loan.product_name || 'N/A'}</td>
                <td class="amount">${parseFloat(loan.amount_disbursed || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="amount">${parseFloat(loan.loan_charges || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="days">${loan.term || 'N/A'}</td>
                <td>${loan.repayment_frequency || 'N/A'}</td>
                <td class="amount">${parseFloat(loan.repayment_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td>${loan.due_date ? moment(loan.due_date).format('MMM DD, YYYY') : 'N/A'}</td>
                <td class="days">${loan.num_missed_payments || 0}</td>
                <td class="amount">${parseFloat(loan.total_arrears || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td>${loan.last_transaction_date ? moment(loan.last_transaction_date).format('MMM DD, YYYY') : 'No Payment'}</td>
                <td class="days">${arrearDays}</td>
                <td>${rbmClassification}</td>
                <td>${riskLevel}</td>
                <td>${loan.officer_firstname || ''} ${loan.officer_lastname || ''}</td>
                <td>${loan.BranchName || 'N/A'}</td>
            </tr>
                `;
            }

            html += `
        </tbody>
        <tfoot>
            <tr style="background-color: #f0f0f0; font-weight: bold;">
                <td colspan="3">TOTAL</td>
                <td class="amount">${loans.reduce((sum, loan) => sum + parseFloat(loan.amount_disbursed || 0), 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="amount">${loans.reduce((sum, loan) => sum + parseFloat(loan.loan_charges || 0), 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td colspan="4"></td>
                <td class="days">${loans.reduce((sum, loan) => sum + parseInt(loan.num_missed_payments || 0), 0)}</td>
                <td class="amount">${totalArrears.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td colspan="6"></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px; font-size: 11px; color: #666;">
        <p><strong>Legend:</strong></p>
        <p><span style="background-color: #ffebee; padding: 2px 5px;">High Risk</span> - More than 90 days in arrears</p>
        <p><span style="background-color: #fff3e0; padding: 2px 5px;">Medium Risk</span> - 31-90 days in arrears</p>
        <p><span style="background-color: #f3e5f5; padding: 2px 5px;">Low Risk</span> - 1-30 days in arrears</p>
    </div>

</body>
</html>
            `;

            resolve(html);

        } catch (error) {
            console.error('Error generating Arrears HTML:', error);
            reject(error);
        }
    });
}

/**
 * Helper function to execute database queries
 */
function queryDatabase(db, query, params = []) {
    return new Promise((resolve, reject) => {
        db.query(query, params, (err, results) => {
            if (err) {
                reject(err);
            } else {
                resolve(results);
            }
        });
    });
}

module.exports = {
    generateArrearsReport
};