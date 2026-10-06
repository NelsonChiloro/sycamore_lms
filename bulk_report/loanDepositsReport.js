const moment = require('moment');

/**
 * Generate a Loan Deposits Report HTML
 * This report mirrors the Tellering/track_transaction page logic:
 * - Queries the `transaction` table joined with `loan` on loan.loan_number = transaction.account_number
 * - Filters out transaction_type = 'other'
 * - Optionally filters by date range (from/to on system_time)
 *
 * @param {Object} filterOptions - Filter parameters { from, to }
 * @param {number} reportId - The ID of the report record
 * @param {Object} reportTrackers - Object tracking report generation progress
 * @param {Object} db - Database connection
 * @returns {Promise<string>} - HTML content of the report
 */
async function generateLoanDepositsReport(filterOptions, reportId, reportTrackers, db) {
    console.log('====== LOAN DEPOSITS REPORT GENERATION STARTED ======');
    console.log(`Report ID: ${reportId}`);
    console.log(`Filters: ${JSON.stringify(filterOptions)}`);

    reportTrackers[reportId].percentage = 5;

    try {
        const data = await getLoanDepositsData(
            filterOptions.from || null,
            filterOptions.to || null,
            reportId,
            reportTrackers,
            db
        );

        const html = generateHtml(data.transactions, filterOptions);

        reportTrackers[reportId].percentage = 100;

        console.log('====== LOAN DEPOSITS REPORT GENERATION COMPLETED ======');
        return html;
    } catch (error) {
        console.error('Error generating loan deposits report:', error);
        throw error;
    }
}

/**
 * Fetch loan deposit transactions from the `transaction` table
 * Same logic as Transaction_model->track_all_transactions() in CI
 */
async function getLoanDepositsData(fromDate, toDate, reportId, reportTrackers, db) {
    return new Promise((resolve, reject) => {
        if (!db) {
            return reject(new Error('Database connection is not available'));
        }

        reportTrackers[reportId].percentage = 10;
        console.log('Fetching loan deposit transactions...');

        let sql = `
            SELECT t.*
            FROM transaction t
            JOIN loan l ON l.loan_number = t.account_number
            WHERE t.transaction_type != 'other'
        `;
        let params = [];

        if (fromDate && toDate) {
            sql += ' AND DATE(t.system_time) BETWEEN ? AND ?';
            params.push(fromDate, toDate);
        } else if (fromDate) {
            sql += ' AND DATE(t.system_time) >= ?';
            params.push(fromDate);
        } else if (toDate) {
            sql += ' AND DATE(t.system_time) <= ?';
            params.push(toDate);
        }

        sql += ' ORDER BY t.account_number ASC, t.system_time DESC';

        db.query(sql, params, (err, transactions) => {
            if (err) {
                console.error('Error fetching loan deposit transactions:', err);
                return reject(err);
            }

            reportTrackers[reportId].percentage = 30;
            console.log(`Found ${transactions.length} loan deposit transactions`);

            // Get loan info for each unique account_number to check paid_off status
            const processed = [];
            let processedCount = 0;
            const totalCount = transactions.length;

            if (totalCount === 0) {
                reportTrackers[reportId].percentage = 95;
                return resolve({ transactions: [] });
            }

            const processNext = (index) => {
                if (index >= totalCount) {
                    reportTrackers[reportId].percentage = 95;
                    console.log('Loan deposits data processing completed');
                    return resolve({ transactions: processed });
                }

                const t = transactions[index];
                processedCount++;
                const pct = 30 + Math.floor((processedCount / totalCount) * 60);
                reportTrackers[reportId].percentage = pct;

                // Lookup loan info for reverse eligibility
                db.query(
                    'SELECT paid_off FROM loan WHERE loan_number = ? LIMIT 1',
                    [t.account_number],
                    (loanErr, loanRows) => {
                        const paidOff = (!loanErr && loanRows.length > 0) ? loanRows[0].paid_off : 'NO';
                        processed.push({
                            ...t,
                            paid_off: paidOff
                        });
                        processNext(index + 1);
                    }
                );
            };

            processNext(0);
        });
    });
}

/**
 * Generate HTML for the loan deposits report
 */
function generateHtml(transactions, filterOptions) {
    const formatDate = (dateString) => {
        if (!dateString) return '';
        return moment(dateString).format('YYYY-MM-DD HH:mm:ss');
    };

    let totalCredit = 0;
    let totalDebit = 0;
    transactions.forEach(t => {
        totalCredit += parseFloat(t.credit) || 0;
        totalDebit += parseFloat(t.debit) || 0;
    });

    let tableRows = '';
    transactions.forEach((t, index) => {
        tableRows += `
        <tr>
            <td>${index + 1}</td>
            <td>${t.account_number || ''}</td>
            <td>${t.transaction_id || ''}</td>
            <td>${formatCurrency(t.credit)}</td>
            <td>${formatCurrency(t.debit)}</td>
            <td>${t.transaction_type || ''}</td>
            <td>${formatDate(t.system_time)}</td>
        </tr>`;
    });

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

    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loan Deposits Report</title>
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script>
            function exportData(type) {
                const fileName = 'Loan_Deposits_Report.' + type;
                const table = document.getElementById("deposits-table");
                const wb = XLSX.utils.table_to_book(table);
                XLSX.writeFile(wb, fileName);
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Loan Deposits Report</h1>
            <p>Report generated on: ${moment().format('YYYY-MM-DD HH:mm:ss')}</p>
        </div>

        <div class="card">
            <div class="filter-info">
                <p><strong>Date Range:</strong> ${dateRangeText}</p>
                <p><strong>Total Records:</strong> ${transactions.length}</p>
            </div>

            <div class="export-buttons">
                <span>Export as:</span>
                <button onclick="exportData('xlsx')">Excel (xlsx)</button>
                <button onclick="exportData('xls')">Excel (xls)</button>
                <button onclick="exportData('csv')">CSV</button>
            </div>

            ${transactions.length > 0 ? `
            <div style="overflow-x: auto;">
                <table id="deposits-table">
                    <thead>
                        <tr class="filter-header">
                            <td colspan="7">Loan Deposits Report - Filter Information</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Date Range:</td>
                            <td colspan="5">${dateRangeText}</td>
                        </tr>
                        <tr class="report-info">
                            <td colspan="2">Report Date:</td>
                            <td colspan="5">${moment().format('YYYY-MM-DD HH:mm:ss')}</td>
                        </tr>
                        <tr>
                            <td colspan="7">&nbsp;</td>
                        </tr>
                        <tr>
                            <th>#</th>
                            <th>Account Number</th>
                            <th>Reference Number</th>
                            <th>Credit (MWK)</th>
                            <th>Debit (MWK)</th>
                            <th>Transaction Type</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Totals</td>
                            <td>${formatCurrency(totalCredit)}</td>
                            <td>${formatCurrency(totalDebit)}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            ` : `
            <div class="no-records">
                <p>No loan deposit transactions found. Please adjust your date range.</p>
            </div>
            `}
        </div>
    </body>
    </html>`;
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
}

module.exports = {
    generateLoanDepositsReport
};
