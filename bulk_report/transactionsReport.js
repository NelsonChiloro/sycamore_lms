const moment = require('moment');
const fs = require('fs');
const path = require('path');
const { determineRBMClassification, sqlLoanMaxDaysInArrearsExpr } = require('./databaseHelpers');

/**
 * Generate a Transactions Report HTML
 *
 * @param {Object} filterOptions - Filter parameters for the report
 * @param {number} reportId - The ID of the report record
 * @param {Object} reportTrackers - Object tracking report generation progress
 * @param {Object} db - Database connection
 * @returns {Promise<string>} - HTML content of the report
 */
async function generateTransactionsReport(filterOptions, reportId, reportTrackers, db) {
    console.log('====== TRANSACTIONS REPORT GENERATION STARTED ======');
    console.log(`Report ID: ${reportId}`);
    console.log(`Filters: ${JSON.stringify(filterOptions)}`);

    // Set initial progress
    reportTrackers[reportId].percentage = 5;

    try {
        // Get transactions data based on filters
        const result = await getTransactionsData(
            filterOptions.branch || null,
            filterOptions.transaction_type || null,
            filterOptions.loan || null,
            filterOptions.product || null,
            filterOptions.officer || null,
            filterOptions.from || null,
            filterOptions.to || null,
            reportId,
            reportTrackers,
            db
        );

        // Update filter options with human-readable names
        const updatedFilterOptions = {
            ...filterOptions,
            branchName: result.filterBranchName,
            productName: result.filterProductName,
            transactionTypeName: result.filterTransactionTypeName,
            officerName: result.filterOfficerName
        };

        // Generate HTML using the data
        const html = generateHtml(result.transactions, updatedFilterOptions);

        // Set final progress
        reportTrackers[reportId].percentage = 100;

        console.log('====== TRANSACTIONS REPORT GENERATION COMPLETED ======');
        return html;
    } catch (error) {
        console.error('Error generating transactions report:', error);
        throw error;
    }
}

/**
 * Get transactions data based on filters
 *
 * @param {string|null} branch - Branch filter
 * @param {string|null} transactionType - Transaction type filter
 * @param {string|null} loan - Loan ID filter
 * @param {string|null} product - Product filter
 * @param {string|null} officer - Loan officer filter
 * @param {string|null} fromDate - Start date
 * @param {string|null} toDate - End date
 * @param {number} reportId - Report ID for tracking
 * @param {Object} reportTrackers - Progress tracking object
 * @param {Object} db - Database connection
 * @returns {Promise<Object>} - Transactions data with filter names
 */
async function getTransactionsData(branch, transactionType, loan, product, officer, fromDate, toDate, reportId, reportTrackers, db) {
    return new Promise((resolve, reject) => {
        if (!db) {
            return reject(new Error('Database connection is not available'));
        }

        // Update progress
        reportTrackers[reportId].percentage = 10;
        console.log('Fetching transactions data...');

        // Build the SELECT query
        const query = `
            SELECT 
                t.*, t.transaction_id as id, 
                l.loan_id, l.loan_number, l.loan_customer, l.customer_type, 
                tt.name, tt.transaction_type_id,
                e.id, e.Firstname, e.Lastname,
                lp.product_name, lp.loan_product_id,
                b.BranchName, b.Code as branch_code,
                ${sqlLoanMaxDaysInArrearsExpr('l')} as days_in_arrears
            FROM transactions t
            JOIN loan l ON l.loan_id = t.loan_id
            JOIN transaction_type tt ON tt.transaction_type_id = t.transaction_type
            JOIN employees e ON e.id = l.loan_added_by
            JOIN loan_products lp ON lp.loan_product_id = l.loan_product
            JOIN branches b ON b.id = l.branch
            WHERE 1=1
        `;

        // Build the WHERE clause based on filters
        let whereClause = '';
        let params = [];

        if (branch) {
            whereClause += ' AND l.branch = ?';
            params.push(branch);
        }

        if (transactionType) {
            whereClause += ' AND t.transaction_type = ?';
            params.push(transactionType);
        }

        if (loan) {
            whereClause += ' AND t.loan_id = ?';
            params.push(loan);
        }

        if (product) {
            whereClause += ' AND l.loan_product = ?';
            params.push(product);
        }

        if (officer) {
            whereClause += ' AND l.loan_added_by = ?';
            params.push(officer);
        }

        if (fromDate && toDate) {
            whereClause += ' AND DATE(t.date_stamp) BETWEEN DATE(?) AND DATE(?)';
            params.push(fromDate, toDate);
        } else if (fromDate) {
            whereClause += ' AND DATE(t.date_stamp) >= DATE(?)';
            params.push(fromDate);
        } else if (toDate) {
            whereClause += ' AND DATE(t.date_stamp) <= DATE(?)';
            params.push(toDate);
        }

        // Order by transaction ID descending
        const orderClause = ' ORDER BY t.transaction_id DESC';

        // Execute the query
        db.query(query + whereClause + orderClause, params, async (err, transactions) => {
            if (err) {
                console.error('Error fetching transactions:', err);
                return reject(err);
            }

            reportTrackers[reportId].percentage = 20;
            console.log(`Found ${transactions.length} transactions matching the filters`);

            // Get filter names for display in report header
            let filterBranchName = 'All Branches';
            let filterProductName = 'All Products';
            let filterTransactionTypeName = 'All Transaction Types';
            let filterOfficerName = 'All Officers';

            try {
                // Get branch name if filter applied
                if (branch) {
                    const branchResult = await new Promise((resolve, reject) => {
                        db.query('SELECT BranchName FROM branches WHERE id = ?', [branch], (err, result) => {
                            if (err) return reject(err);
                            resolve(result);
                        });
                    });
                    if (branchResult && branchResult.length > 0) {
                        filterBranchName = branchResult[0].BranchName;
                    }
                }

                // Get product name if filter applied
                if (product) {
                    const productResult = await new Promise((resolve, reject) => {
                        db.query('SELECT product_name FROM loan_products WHERE loan_product_id = ?', [product], (err, result) => {
                            if (err) return reject(err);
                            resolve(result);
                        });
                    });
                    if (productResult && productResult.length > 0) {
                        filterProductName = productResult[0].product_name;
                    }
                }

                // Get transaction type name if filter applied
                if (transactionType) {
                    const typeResult = await new Promise((resolve, reject) => {
                        db.query('SELECT name FROM transaction_type WHERE transaction_type_id = ?', [transactionType], (err, result) => {
                            if (err) return reject(err);
                            resolve(result);
                        });
                    });
                    if (typeResult && typeResult.length > 0) {
                        filterTransactionTypeName = typeResult[0].name;
                    }
                }

                // Get officer name if filter applied
                if (officer) {
                    const officerResult = await new Promise((resolve, reject) => {
                        db.query('SELECT Firstname, Lastname FROM employees WHERE id = ?', [officer], (err, result) => {
                            if (err) return reject(err);
                            resolve(result);
                        });
                    });
                    if (officerResult && officerResult.length > 0) {
                        filterOfficerName = `${officerResult[0].Firstname} ${officerResult[0].Lastname}`;
                    }
                }
            } catch (error) {
                console.error('Error getting filter names:', error);
                // Continue processing without filter names
            }

            // Process transactions to add customer names
            const processedTransactions = [];
            let processedCount = 0;
            const totalCount = transactions.length;

            for (const transaction of transactions) {
                processedCount++;

                // Update progress percentage based on processed transactions
                const processedPercentage = 20 + Math.floor((processedCount / totalCount) * 70);
                reportTrackers[reportId].percentage = processedPercentage;

                console.log(`Processing transaction ${processedCount}/${totalCount} (${processedPercentage}%)`);

                try {
                    // Get customer name and group name based on customer type
                    let customerName = await getCustomerName(db, transaction.loan_customer, transaction.customer_type);
                    let customerGroupName = await getCustomerGroupName(db, transaction.loan_customer, transaction.customer_type);

                    // Add customer name and group to transaction object
                    const processedTransaction = {
                        ...transaction,
                        customer_name: customerName,
                        customer_group_name: customerGroupName
                    };

                    processedTransactions.push(processedTransaction);
                } catch (error) {
                    console.error(`Error processing transaction ${transaction.transaction_id}:`, error);
                    // Continue with next transaction instead of failing the whole report
                    processedTransactions.push({
                        ...transaction,
                        customer_name: 'Unknown Customer'
                    });
                }
            }

            reportTrackers[reportId].percentage = 95;
            console.log('Transaction data processing completed');

            // Return the processed transactions and filter names
            resolve({
                transactions: processedTransactions,
                filterBranchName,
                filterProductName,
                filterTransactionTypeName,
                filterOfficerName
            });
        });
    });
}

/**
 * Get customer group name (returns 'N/A' for individuals)
 *
 * @param {Object} db - Database connection
 * @param {number} customerId - Customer ID
 * @param {string} customerType - Customer type ('group' or 'individual')
 * @returns {Promise<string>} - Customer group name or 'N/A'
 */
async function getCustomerGroupName(db, customerId, customerType) {
    return new Promise((resolve, reject) => {
        if (customerType === 'group') {
            db.query(
                'SELECT group_name FROM `groups` WHERE group_id = ?',
                [customerId],
                (err, results) => {
                    if (err) return reject(err);
                    if (results.length === 0) return resolve('Unknown Group');
                    resolve(results[0].group_name);
                }
            );
        } else {
            resolve('N/A');
        }
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
                    resolve(`${results[0].Firstname} ${results[0].Lastname} ${results[0].ClientId ? `(${results[0].ClientId})` : ''}`);
                }
            );
        }
    });
}

/**
 * Generate HTML for the transactions report
 *
 * @param {Array} transactions - Transaction data
 * @param {Object} filterOptions - Filter parameters
 * @returns {string} - HTML content
 */
function generateHtml(transactions, filterOptions) {
    // Format date for display
    const formatDate = (dateString) => {
        if (!dateString) return '';
        return moment(dateString).format('YYYY-MM-DD HH:mm:ss');
    };

    // Calculate total transaction amount
    let totalAmount = 0;
    transactions.forEach(transaction => {
        totalAmount += parseFloat(transaction.amount) || 0;
    });

    // Create table rows for transactions
    let tableRows = '';
    transactions.forEach((transaction, index) => {
        tableRows += `
        <tr>
            <td>${index + 1}</td>
            <td>${transaction.ref || ''}</td>
            <td>${transaction.product_name || ''}</td>
            <td>${transaction.BranchName || ''}</td>
            <td>${transaction.loan_number || ''}</td>
            <td>${transaction.customer_name || ''}</td>
            <td>${transaction.customer_group_name || ''}</td>
            <td>${transaction.name || ''}</td>
            <td>${transaction.payment_number || ''}</td>
            <td>${formatCurrency(transaction.amount)}</td>
            <td>${formatDate(transaction.date_stamp)}</td>
            <td>${determineRBMClassification(transaction.days_in_arrears)}</td>
            <td>${transaction.Firstname} ${transaction.Lastname}</td>
        </tr>`;
    });

    // Generate date range text
    let dateRangeText = '';
    if (filterOptions.from && filterOptions.to) {
        dateRangeText = `${filterOptions.from} to ${filterOptions.to}`;
    } else if (filterOptions.from) {
        dateRangeText = `From ${filterOptions.from} onwards`;
    } else if (filterOptions.to) {
        dateRangeText = `Up to ${filterOptions.to}`;
    } else {
        dateRangeText = 'All dates';
    }

    // Generate complete HTML
    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payments Transactions Report</title>
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
                const fileName = 'Payments_Transactions_Report.' + type;
                const table = document.getElementById("transactions-table");
                const wb = XLSX.utils.table_to_book(table);
                XLSX.writeFile(wb, fileName);
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Payments Transactions Report</h1>
            <p>Report generated on: ${moment().format('YYYY-MM-DD HH:mm:ss')}</p>
        </div>
        
        <div class="card">
            <div class="filter-info">
                <p><strong>Branch:</strong> ${filterOptions.branchName || 'All Branches'}</p>
                <p><strong>Transaction Type:</strong> ${filterOptions.transactionTypeName || 'All Transaction Types'}</p>
                <p><strong>Loan Product:</strong> ${filterOptions.productName || 'All Products'}</p>
                <p><strong>Loan Officer:</strong> ${filterOptions.officerName || 'All Officers'}</p>
                <p><strong>Date Range:</strong> ${dateRangeText}</p>
            </div>
            
            <div class="export-buttons">
                <span>Export as:</span>
                <button onclick="exportData('xlsx')">Excel (xlsx)</button>
                <button onclick="exportData('xls')">Excel (xls)</button>
                <button onclick="exportData('csv')">CSV</button>
            </div>
            
            ${transactions.length > 0 ? `
            <div style="overflow-x: auto;">
                <table id="transactions-table">
                    <thead>
                        <!-- Filter information rows (included in export) -->
                        <tr class="filter-header">
                            <td colspan="13">Payments Transactions Report - Filter Information</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Branch:</td>
                            <td colspan="11">${filterOptions.branchName || 'All Branches'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Transaction Type:</td>
                            <td colspan="11">${filterOptions.transactionTypeName || 'All Transaction Types'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Loan Product:</td>
                            <td colspan="11">${filterOptions.productName || 'All Products'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Loan Officer:</td>
                            <td colspan="11">${filterOptions.officerName || 'All Officers'}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Date Range:</td>
                            <td colspan="11">${dateRangeText}</td>
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
                            <th>Transaction Ref ID</th>
                            <th>Loan Product</th>
                            <th>Branch</th>
                            <th>Loan Number</th>
                            <th>Customer Name</th>
                            <th>Customer Group</th>
                            <th>Transaction Type</th>
                            <th>Payment Number</th>
                            <th>Amount (MWK)</th>
                            
                            <th>Payment Date</th>
                            <th>RBM Loan Classification</th>
                            <th>Officer</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8">Total</td>
                            <td>${formatCurrency(totalAmount)}</td>
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
    generateTransactionsReport
};