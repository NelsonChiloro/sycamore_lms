const moment = require('moment');
const {
    determineRBMClassification,
    getOfficerIdsUnderSupervisor,
    sqlRelationshipSupervisorNameExpr,
    sqlBranchJoin,
    appendBranchFilter
} = require('./databaseHelpers');
const fs = require('fs');
const path = require('path');

/**
 * Generate an Upcoming Installment Report HTML
 *
 * @param {Object} filterOptions - Filter parameters for the report
 * @param {number} reportId - The ID of the report record
 * @param {Object} reportTrackers - Object tracking report generation progress
 * @param {Object} db - Database connection
 * @returns {Promise<string>} - HTML content of the report
 */
async function generateUpcomingInstallmentReport(filterOptions, reportId, reportTrackers, db) {
    console.log('====== UPCOMING INSTALLMENT REPORT GENERATION STARTED ======');
    console.log(`Report ID: ${reportId}`);
    console.log(`Filters: ${JSON.stringify(filterOptions)}`);

    // Set initial progress
    reportTrackers[reportId].percentage = 5;
    const asOfDate = filterOptions.to || moment().format('YYYY-MM-DD');

    try {
        // Get upcoming installment data based on filters
        const result = await getUpcomingInstallmentData(
            filterOptions.user || '',
            filterOptions.supervisor,
            filterOptions.product || '',
            filterOptions.branch || '',
            filterOptions.from || '',
            filterOptions.to || '',
            asOfDate,
            reportId,
            reportTrackers,
            db
        );

        // Update filter options with human-readable names
        const updatedFilterOptions = {
            ...filterOptions,
            branchName: result.filterBranchName,
            userName: result.filterOfficerName,
            productName: result.filterProductName,
            fromDate: result.filterFromDate,
            toDate: result.filterToDate
        };

        // Generate HTML using the data
        const html = generateHtml(result.upcomingPayments, updatedFilterOptions);

        // Set final progress
        reportTrackers[reportId].percentage = 100;

        console.log('====== UPCOMING INSTALLMENT REPORT GENERATION COMPLETED ======');
        return html;
    } catch (error) {
        console.error('Error generating upcoming installment report:', error);
        throw error;
    }
}

/**
 * Get upcoming installment data based on filters
 *
 * @param {string} loanOfficer - Loan officer filter
 * @param {string} product - Loan product filter
 * @param {string} branch - Branch filter
 * @param {number} reportId - Report ID for tracking
 * @param {Object} reportTrackers - Progress tracking object
 * @param {Object} db - Database connection
 * @returns {Promise<Object>} - Upcoming payment data with filter names
 */
async function getUpcomingInstallmentData(loanOfficer, supervisor, product, branch, fromDate, toDate, asOfDate, reportId, reportTrackers, db) {
    if (!db) {
        throw new Error('Database connection is not available');
    }

    reportTrackers[reportId].percentage = 10;
    console.log('Fetching upcoming installment data...');

    let supervisorOfficerFilter = '';
    if (supervisor && supervisor !== 'All') {
        const officerIds = await getOfficerIdsUnderSupervisor(supervisor);
        if (!officerIds.length) {
            supervisorOfficerFilter = '1=0';
        } else {
            supervisorOfficerFilter = `loan.loan_added_by IN (${officerIds.join(',')})`;
        }
    }

    return new Promise((resolve, reject) => {

        // Get branch, officer, and product names for the filters (for display in report header)
        let filterBranchName = 'All Branches';
        let filterOfficerName = 'All Officers';
        let filterProductName = 'All Products';
        let filterFromDate = fromDate || 'Any';
        let filterToDate = toDate || 'Any';

        // Promise array for getting the filter names
        const filterPromises = [];

        // Get branch name if filtered
        if (branch) {
            filterPromises.push(
                new Promise((resolveFilter, rejectFilter) => {
                    db.query('SELECT BranchName FROM branches WHERE id = ? OR Code = ? LIMIT 1', [branch, branch], (err, result) => {
                        if (err) return rejectFilter(err);
                        if (result && result.length > 0) {
                            filterBranchName = result[0].BranchName;
                        }
                        resolveFilter();
                    });
                })
            );
        }

        // Get officer name if filtered
        if (loanOfficer) {
            filterPromises.push(
                new Promise((resolveFilter, rejectFilter) => {
                    db.query('SELECT Firstname, Lastname FROM employees WHERE id = ?', [loanOfficer], (err, result) => {
                        if (err) return rejectFilter(err);
                        if (result && result.length > 0) {
                            filterOfficerName = `${result[0].Firstname} ${result[0].Lastname}`;
                        }
                        resolveFilter();
                    });
                })
            );
        }

        // Get product name if filtered
        if (product) {
            filterPromises.push(
                new Promise((resolveFilter, rejectFilter) => {
                    db.query('SELECT product_name FROM loan_products WHERE loan_product_id = ?', [product], (err, result) => {
                        if (err) return rejectFilter(err);
                        if (result && result.length > 0) {
                            filterProductName = result[0].product_name;
                        }
                        resolveFilter();
                    });
                })
            );
        }

        // Execute all filter name queries in parallel
        Promise.all(filterPromises)
            .then(() => {
                // Build the query - replicating the model's next_payment function
                const conditions = [
                    "loan.loan_status = 'ACTIVE'",
                    "payement_schedules.payment_number = loan.next_payment_id"
                ];
                const params = [];
                if (supervisorOfficerFilter) {
                    conditions.push(supervisorOfficerFilter);
                } else if (loanOfficer) {
                    conditions.push('loan.loan_added_by = ?');
                    params.push(loanOfficer);
                }
                if (product) {
                    conditions.push('loan.loan_product = ?');
                    params.push(product);
                }
                if (branch) {
                    let branchClause = '';
                    branchClause = appendBranchFilter(branchClause, params, branch, 'loan');
                    if (branchClause) {
                        conditions.push(branchClause.replace(/^\s*AND\s+/i, ''));
                    }
                }
                if (fromDate) {
                    conditions.push('DATE(payement_schedules.payment_schedule) >= ?');
                    params.push(fromDate);
                }
                if (toDate) {
                    conditions.push('DATE(payement_schedules.payment_schedule) <= ?');
                    params.push(toDate);
                }

                const query = `
                    SELECT
                        payement_schedules.*,
                        loan.*,
                        loan_products.*,
                        employees.Firstname as efname,
                        employees.Lastname as elname,
                        ${sqlRelationshipSupervisorNameExpr('rel_sup')} as relationship_supervisor,
                        individual_customers.Firstname as ifname,
                        individual_customers.Lastname as ilname,
                        branches.BranchName,
                        g.group_name as customer_group_name
                    FROM
                        payement_schedules
                            JOIN
                        loan ON loan.loan_id = payement_schedules.loan_id
                            LEFT JOIN
                        loan_products ON loan_products.loan_product_id = loan.loan_product
                            LEFT JOIN
                        individual_customers ON individual_customers.id = payement_schedules.customer
                            LEFT JOIN
                        \`groups\` g ON g.group_id = loan.loan_customer AND loan.customer_type = 'group'
                            LEFT JOIN
                        employees ON employees.id = loan.loan_added_by
                            LEFT JOIN
                        employees rel_sup ON rel_sup.id = employees.Supervisor
                            ${sqlBranchJoin('loan', 'branches')}
                    WHERE
                        ${conditions.join(' AND ')}
                `;

                // Execute the query
                db.query(query, params, async (err, payments) => {
                    if (err) {
                        console.error('Error fetching upcoming payments:', err);
                        return reject(err);
                    }

                    reportTrackers[reportId].percentage = 20;
                    console.log(`Found ${payments.length} upcoming payments`);

                    const upcomingPayments = [];
                    let processedCount = 0;
                    const totalCount = payments.length;

                    // Process each payment to get additional data
                    for (const payment of payments) {
                        processedCount++;

                        // Update progress percentage based on processed payments
                        const processedPercentage = 20 + Math.floor((processedCount / totalCount) * 70);
                        reportTrackers[reportId].percentage = processedPercentage;

                        console.log(`Processing payment ${processedCount}/${totalCount} (${processedPercentage}%)`);

                        try {
                            // Get customer name and group
                            let customerName = '';
                            let customerGroupName = payment.customer_group_name || 'N/A';
                            if (payment.customer_type === 'group') {
                                const group = await getGroupById(db, payment.loan_customer);
                                customerName = `${group.group_name} (${group.group_code})`;
                            } else {
                                const customer = await getCustomerById(db, payment.loan_customer);
                                customerName = `${customer.Firstname} ${customer.Lastname}`;
                            }

                            // Get amount in arrears and days in arrears
                            const amountInArrears = await getAmountOfArrears(db, payment.loan_id, asOfDate);
                            const daysInArrears = await getDaysOfArrears(db, payment.loan_id, asOfDate);

                            // Get number of installments in arrears
                            const installmentsInArrears = await getInstallmentsInArrears(db, payment.loan_id, asOfDate);

                            // Get last transaction date
                            const lastTransactionDate = await getLastTransactionDate(db, payment.loan_id);

                            // Get overpayment or balance
                            const overpayment = await getOverpayment(db, payment.loan_id);

                            // Format dates
                            const loanDate = payment.loan_date ? formatDate(payment.loan_date) : 'N/A';
                            const repaymentDate = payment.payment_schedule ? formatDate(payment.payment_schedule) : 'N/A';
                            const lastTransaction = lastTransactionDate !== 'No payments' ? formatDate(lastTransactionDate) : 'No payments';

                            // Calculate total due (upcoming installment amount + any arrears)
                            // Calculate total due: sum of NOT PAID pay_amounts for due/arrears minus sum of paid amounts for due/arrears
                            let totalDue = 0;
                            // Sum all NOT PAID pay_amounts for due/arrears
                            const [notPaidRows] = await new Promise((resolve, reject) => {
                                db.query(
                                    `SELECT SUM(amount) as total_due
                                     FROM payement_schedules
                                     WHERE loan_id = ? AND status = 'NOT PAID' AND payment_schedule <= ?`,
                                    [payment.loan_id, asOfDate],
                                    (err, results) => {
                                        if (err) return reject(err);
                                        resolve([results]);
                                    }
                                );
                            });
                            // Sum all paid amounts for due/arrears
                            const [paidRows] = await new Promise((resolve, reject) => {
                                db.query(
                                    `SELECT SUM(paid_amount) as total_paid
                                     FROM payement_schedules
                                     WHERE loan_id = ? AND status = 'NOT PAID' AND payment_schedule <= ?`,
                                    [payment.loan_id, asOfDate],
                                    (err, results) => {
                                        if (err) return reject(err);
                                        resolve([results]);
                                    }
                                );
                            });
                            const sumDue = notPaidRows[0] && notPaidRows[0].total_due ? parseFloat(notPaidRows[0].total_due) : 0;
                            const sumPaid = paidRows[0] && paidRows[0].total_paid ? parseFloat(paidRows[0].total_paid) : 0;
                            totalDue = sumDue - sumPaid;

                            // Create payment data object
                            const paymentData = {
                                branch_name: payment.BranchName || 'N/A',
                                loan_date: loanDate,
                                customer_name: customerName,
                                customer_group_name: customerGroupName,
                                loan_number: payment.loan_number,
                                loan_principal: payment.loan_principal,
                                payment_schedule: repaymentDate,
                                installment_amount: payment.amount,
                                amount_in_arrears: daysInArrears > 0 ? amountInArrears : 0,
                                total_due: totalDue, // Now uses correct logic
                                overpayment: overpayment,
                                last_transaction_date: lastTransaction,
                                days_in_arrears: daysInArrears,
                                rbm_classification: determineRBMClassification(daysInArrears),
                                installments_in_arrears: installmentsInArrears,
                                officer_name: `${payment.efname || ''} ${payment.elname || ''}`.trim() || 'N/A',
                                relationship_supervisor: payment.relationship_supervisor || 'N/A'
                            };

                            upcomingPayments.push(paymentData);
                        } catch (error) {
                            console.error(`Error processing payment for loan ${payment.loan_id}:`, error);
                            // Continue with next payment instead of failing the whole report
                        }
                    }

                    reportTrackers[reportId].percentage = 95;
                    console.log('Upcoming installment data processing completed');

                    resolve({
                        upcomingPayments,
                        filterBranchName,
                        filterOfficerName,
                        filterProductName,
                        filterFromDate,
                        filterToDate
                    });
                });
            })
            .catch(error => {
                console.error('Error getting filter names:', error);
                reject(error);
            });
    });
}

/**
 * Get amount of arrears for a loan
 *
 * @param {Object} db - Database connection
 * @param {number} loanId - Loan ID
 * @returns {Promise<number>} - Amount in arrears
 */
async function getAmountOfArrears(db, loanId, asOfDate) {
    return new Promise((resolve, reject) => {
        db.query(
            `SELECT SUM(amount - paid_amount) as total_arrears
             FROM payement_schedules
             WHERE loan_id = ?
               AND payment_schedule < ?
               AND status = 'NOT PAID'`,
            [loanId, asOfDate],
            (err, results) => {
                if (err) return reject(err);
                const totalArrears = results[0] && results[0].total_arrears ? parseFloat(results[0].total_arrears) : 0;
                resolve(totalArrears);
            }
        );
    });
}

/**
 * Get days in arrears for a loan
 *
 * @param {Object} db - Database connection
 * @param {number} loanId - Loan ID
 * @returns {Promise<number>} - Days in arrears
 */
async function getDaysOfArrears(db, loanId, asOfDate) {
    return new Promise((resolve, reject) => {
        const referenceDate = moment(asOfDate);

        db.query(
            `SELECT payment_schedule
             FROM payement_schedules
             WHERE loan_id = ? AND payment_schedule < ? AND status = 'NOT PAID'
             ORDER BY payment_schedule ASC
                 LIMIT 1`,
            [loanId, referenceDate.format('YYYY-MM-DD')],
            (err, results) => {
                if (err) return reject(err);

                if (results.length === 0) {
                    resolve(0); // No arrears
                } else {
                    const paymentDate = moment(results[0].payment_schedule);
                    const daysInArrears = referenceDate.diff(paymentDate, 'days');
                    resolve(daysInArrears > 0 ? daysInArrears : 0);
                }
            }
        );
    });
}

/**
 * Get number of installments in arrears for a loan
 *
 * @param {Object} db - Database connection
 * @param {number} loanId - Loan ID
 * @returns {Promise<number>} - Number of installments in arrears
 */
async function getInstallmentsInArrears(db, loanId, asOfDate) {
    return new Promise((resolve, reject) => {
        db.query(
            `SELECT COUNT(*) as count
             FROM payement_schedules
             WHERE loan_id = ? AND status = 'NOT PAID' AND payment_schedule < ?`,
            [loanId, asOfDate],
            (err, results) => {
                if (err) return reject(err);
                resolve(results[0] ? parseInt(results[0].count || 0) : 0);
            }
        );
    });
}

/**
 * Get last transaction date for a loan
 *
 * @param {Object} db - Database connection
 * @param {number} loanId - Loan ID
 * @returns {Promise<string>} - Last transaction date or 'No payments'
 */
async function getLastTransactionDate(db, loanId) {
    return new Promise((resolve, reject) => {
        db.query(
            `SELECT date_stamp
             FROM transactions
             WHERE loan_id = ?
             ORDER BY date_stamp DESC
                 LIMIT 1`,
            [loanId],
            (err, results) => {
                if (err) return reject(err);

                if (results.length === 0) {
                    resolve('No payments');
                } else {
                    resolve(results[0].date_stamp);
                }
            }
        );
    });
}

/**
 * Get overpayment or balance in settlement account
 *
 * @param {Object} db - Database connection
 * @param {number} loanId - Loan ID
 * @returns {Promise<number>} - Overpayment amount
 */
async function getOverpayment(db, loanId) {
    // This function can be expanded based on your business logic
    // Currently returning 0 as per the original code
    return Promise.resolve(0);
}

/**
 * Get group by ID
 *
 * @param {Object} db - Database connection
 * @param {number} groupId - Group ID
 * @returns {Promise<Object>} - Group data
 */
async function getGroupById(db, groupId) {
    return new Promise((resolve, reject) => {
        db.query(
            'SELECT group_name, group_code FROM `groups` WHERE group_id = ?',
            [groupId],
            (err, results) => {
                if (err) return reject(err);

                if (results.length === 0) {
                    resolve({ group_name: 'Unknown Group', group_code: 'N/A' });
                } else {
                    resolve(results[0]);
                }
            }
        );
    });
}

/**
 * Get customer by ID
 *
 * @param {Object} db - Database connection
 * @param {number} customerId - Customer ID
 * @returns {Promise<Object>} - Customer data
 */
async function getCustomerById(db, customerId) {
    return new Promise((resolve, reject) => {
        db.query(
            'SELECT Firstname, Lastname FROM individual_customers WHERE id = ?',
            [customerId],
            (err, results) => {
                if (err) return reject(err);

                if (results.length === 0) {
                    resolve({ Firstname: 'Unknown', Lastname: 'Customer' });
                } else {
                    resolve(results[0]);
                }
            }
        );
    });
}

/**
 * Format date for display
 *
 * @param {string} dateString - Date string
 * @returns {string} - Formatted date
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return moment(dateString).format('YYYY-MM-DD');
}

/**
 * Generate HTML for the upcoming installment report
 *
 * @param {Array} upcomingPayments - Upcoming payment data
 * @param {Object} filterOptions - Filter parameters
 * @returns {string} - HTML content
 */
function generateHtml(upcomingPayments, filterOptions) {
    // Calculate totals
    let totalDisbursed = 0;
    let totalInstallmentAmount = 0;
    let totalArrears = 0;
    let totalDue = 0;
    let totalOverpayment = 0;

    upcomingPayments.forEach(payment => {
        totalDisbursed += parseFloat(payment.loan_principal) || 0;
        totalInstallmentAmount += parseFloat(payment.installment_amount) || 0;
        totalArrears += parseFloat(payment.amount_in_arrears) || 0;
        totalDue += parseFloat(payment.total_due) || 0;
        totalOverpayment += parseFloat(payment.overpayment) || 0;
    });

    // Create table rows for upcoming payments
    let tableRows = '';
    upcomingPayments.forEach((payment, index) => {
        tableRows += `
        <tr>
            <td>${payment.branch_name}</td>
            <td>${payment.loan_date}</td>
            <td>${payment.customer_name}</td>
            <td>${payment.customer_group_name}</td>
            <td>${payment.loan_number}</td>
            <td>${formatCurrency(payment.loan_principal)}</td>
            <td>${payment.payment_schedule}</td>
            <td>${formatCurrency(payment.installment_amount)}</td>
            <td>${formatCurrency(payment.amount_in_arrears)}</td>
            <td>${formatCurrency(payment.total_due)}</td>
            <td>${formatCurrency(payment.overpayment)}</td>
            <td>${payment.last_transaction_date}</td>
            <td>${payment.days_in_arrears}</td>
            <td>${payment.rbm_classification || 'Standard'}</td>
            <td>${payment.installments_in_arrears}</td>
            <td>${payment.officer_name}</td>
            <td>${payment.relationship_supervisor || 'N/A'}</td>
        </tr>`;
    });

    // Generate complete HTML with filters info
    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Upcoming Installment Report</title>
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
                const fileName = 'Upcoming_Installment_Report.' + type;
                const table = document.getElementById("upcoming-table");
                const wb = XLSX.utils.table_to_book(table);
                XLSX.writeFile(wb, fileName);
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Upcoming Installment Report</h1>
            <p>Report generated on: ${moment().format('YYYY-MM-DD HH:mm:ss')}</p>
        </div>
        
        <div class="card">
            <div class="filter-info">
                <p><strong>Branch:</strong> ${filterOptions.branchName || 'All Branches'}</p>
                <p><strong>Loan Officer:</strong> ${filterOptions.userName || 'All Officers'}</p>
                <p><strong>Loan Product:</strong> ${filterOptions.productName || 'All Products'}</p>
                <p><strong>From Date:</strong> ${filterOptions.fromDate || 'Any'}</p>
                <p><strong>To Date:</strong> ${filterOptions.toDate || 'Any'}</p>
            </div>
            
            <div class="export-buttons">
                <span>Export as:</span>
                <button onclick="exportData('xlsx')">Excel (xlsx)</button>
                <button onclick="exportData('xls')">Excel (xls)</button>
                <button onclick="exportData('csv')">CSV</button>
            </div>
            
            ${upcomingPayments.length > 0 ? `
            <div style="overflow-x: auto;">
                <table id="upcoming-table">
                    <thead>
                        <!-- Filter information rows (included in export) -->
                        <tr class="filter-header">
                            <td colspan="17">Upcoming Installment Report - Filter Information</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="3">Branch:</td>
                            <td colspan="14">${filterOptions.branchName || 'All Branches'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="3">Loan Officer:</td>
                            <td colspan="14">${filterOptions.userName || 'All Officers'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="3">Loan Product:</td>
                            <td colspan="14">${filterOptions.productName || 'All Products'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="3">From Date:</td>
                            <td colspan="14">${filterOptions.fromDate || 'Any'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="3">To Date:</td>
                            <td colspan="14">${filterOptions.toDate || 'Any'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="3">Report Date:</td>
                            <td colspan="14">${moment().format('YYYY-MM-DD HH:mm:ss')}</td>
                        </tr>
                        <!-- Empty row for spacing -->
                        <tr>
                            <td colspan="17">&nbsp;</td>
                        </tr>
                        <!-- Data header row -->
                        <tr>
                            <th>Branch</th>
                            <th>Date</th>
                            <th>Customer Name</th>
                            <th>Customer Group</th>
                            <th>Loan</th>
                            <th>Amount Disbursed</th>
                            <th>Repayment Date</th>
                            <th>Instalment</th>
                            <th>Arrears</th>
                            <th>Total Due (Instalment + Arrears)</th>
                            <th>Overpayment/Balance</th>
                            <th>Last Transaction Date</th>
                            <th>No of Days in Arrears</th>
                            <th>RBM Loan Classification</th>
                            <th>Number of Instalments in Arrears</th>
                            <th>Loan Officer</th>
                            <th>Relationship Supervisor</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Totals</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>${formatCurrency(totalDisbursed)}</td>
                            <td></td>
                            <td>${formatCurrency(totalInstallmentAmount)}</td>
                            <td>${formatCurrency(totalArrears)}</td>
                            <td>${formatCurrency(totalDue)}</td>
                            <td>${formatCurrency(totalOverpayment)}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            ` : `
            <div class="no-records">
                <p>No upcoming installments found. Please adjust your search criteria.</p>
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
    generateUpcomingInstallmentReport
};