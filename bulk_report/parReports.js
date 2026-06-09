const moment = require('moment');
const fs = require('fs');
const path = require('path');
const {
    sqlBranchJoin,
    appendBranchFilter,
    findBranch,
    determineRBMClassification,
    getDaysOfArrears,
    getOfficerIdsUnderSupervisor,
    sqlRelationshipSupervisorNameExpr,
    sqlUnpaidPrincipalExpr,
    sqlUnpaidChargesExpr
} = require('./databaseHelpers');

async function appendParOfficerOrSupervisorFilter(whereClause, queryParams, officer, supervisor) {
    if (supervisor && supervisor !== 'All') {
        const ids = await getOfficerIdsUnderSupervisor(supervisor);
        if (!ids.length) {
            return `${whereClause} AND 1=0`;
        }
        return `${whereClause} AND l.loan_added_by IN (${ids.join(',')})`;
    }
    if (officer && officer !== 'All') {
        queryParams.push(officer);
        return `${whereClause} AND l.loan_added_by = ?`;
    }
    return whereClause;
}

// PAR Constants
const PAR_THRESHOLDS = [1, 30, 60, 90];

function normalizeParDateInput(value) {
    if (value === undefined || value === null) {
        return null;
    }
    const raw = String(value).trim();
    if (!raw) {
        return null;
    }
    const parsed = moment(raw, ['YYYY-MM-DD', 'MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY/MM/DD', 'MM-DD-YYYY', 'DD-MM-YYYY'], true);
    if (parsed.isValid()) {
        return parsed.format('YYYY-MM-DD');
    }
    const fallback = moment(raw);
    return fallback.isValid() ? fallback.format('YYYY-MM-DD') : null;
}

/**
 * Additional helper function to validate PAR report parameters
 * @param {Object} params - Parameters to validate
 * @returns {Object} Validated parameters
 */
function validatePARReportParameters(params) {
    const validatedParams = {
        officer: params.officer || 'All',
        product: params.product || 'All',
        branch: params.branch || 'All',
        dateFrom: null,
        dateTo: null
    };

    // Validate dates
    if (params.dateFrom) {
        const fromDate = moment(params.dateFrom);
        if (fromDate.isValid()) {
            validatedParams.dateFrom = fromDate.format('YYYY-MM-DD');
        }
    }

    if (params.dateTo) {
        const toDate = moment(params.dateTo);
        if (toDate.isValid()) {
            validatedParams.dateTo = toDate.format('YYYY-MM-DD');
        }
    }

    // Ensure date range is logical
    if (validatedParams.dateFrom && validatedParams.dateTo) {
        const fromMoment = moment(validatedParams.dateFrom);
        const toMoment = moment(validatedParams.dateTo);

        if (fromMoment.isAfter(toMoment)) {
            // Swap dates if from date is after to date
            validatedParams.dateFrom = toMoment.format('YYYY-MM-DD');
            validatedParams.dateTo = fromMoment.format('YYYY-MM-DD');
        }
    }

    return validatedParams;
}

/**
 * Calculate PAR for a specific days range
 * @param {Array} payments - Array of payment data
 * @param {number} minDays - Minimum days
 * @param {number} maxDays - Maximum days
 * @returns {number} PAR amount for the range
 */
function calculatePARForDaysRange(payments, minDays, maxDays) {
    return payments
        .filter(payment => {
            const days = payment.days_overdue || 0;
            return days >= minDays && days <= maxDays;
        })
        .reduce((total, payment) => total + (parseFloat(payment.amount_due) || 0), 0);
}

/**
 * Get outstanding principal for a loan
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<number>} Outstanding principal
 */
async function getOutstandingPrincipal(loanId, db) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                COALESCE(SUM(principal), 0) - COALESCE(SUM(CASE WHEN status = 'PAID' THEN principal ELSE 0 END), 0) as outstanding_principal
            FROM payement_schedules
            WHERE loan_id = ?
        `;

        db.query(query, [loanId], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            resolve(parseFloat(result.outstanding_principal) || 0);
        });
    });
}

/**
 * Get all payments for a loan
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<Array>} Array of payments
 */
async function getAllPaymentsForLoan(loanId, db, asOfDate = moment().format('YYYY-MM-DD')) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                id, payment_schedule, payment_number, amount, principal, interest,
                paid_amount, status, 
                DATEDIFF(DATE(?), payment_schedule) as days_overdue,
                (amount - paid_amount) as amount_due
            FROM payement_schedules
            WHERE loan_id = ? AND payment_schedule < DATE(?)
            ORDER BY payment_number ASC
        `;

        db.query(query, [asOfDate, loanId, asOfDate], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            resolve(results || []);
        });
    });
}

/**
 * Generate PAR Report V2
 * @param {number} reportId - Report ID
 * @param {string} officer - Officer filter
 * @param {string} product - Product filter
 * @param {string} branch - Branch filter
 * @param {string} dateFrom - Start date
 * @param {string} dateTo - End date
 * @param {Object} db - Database connection
 * @param {Object} reportTrackers - Report trackers object
 * @returns {Promise<boolean>} Success status
 */
async function generatePARReportV2(reportId, officer, product, branch, dateFrom, dateTo, supervisor, db, reportTrackers) {
    console.log('====== PAR REPORT GENERATION STARTED ======');
    console.log('[1/6] Establishing database connection...');
    console.log(`Report will be saved to: ${reportTrackers[reportId].filePath}`);
    console.log(`Filters - Officer: ${officer || 'All'}, Product: ${product}, Branch: ${branch }`);
    console.log(`Date Range - From: ${dateFrom} To: ${dateTo}`);

    try {
        dateFrom = normalizeParDateInput(dateFrom);
        dateTo = normalizeParDateInput(dateTo);
        if (dateFrom && dateTo && moment(dateFrom).isAfter(moment(dateTo))) {
            const swap = dateFrom;
            dateFrom = dateTo;
            dateTo = swap;
        }
        reportTrackers[reportId].percentage = 10;
        console.log('[2/6] Database connection established successfully.');
         console.log('new file of par');
        // Outstanding/arrears must be valued as of current date (current month),
        // while dateFrom/dateTo only filter which loans are included.
        const currentDate = moment().format('YYYY-MM-DD');
        const reportAsOfDate = currentDate;

        console.log('[3/6] Calculating total portfolio size...');
        // Calculate total portfolio size using regular callback-style query
        return new Promise((resolve, reject) => {
            db.query(`
                SELECT SUM(loan_principal) as total_portfolio
                FROM loan
                WHERE loan_status = 'ACTIVE'
            `, async (err, portfolioRows) => {
                if (err) {
                    reject(err);
                    return;
                }

                const totalPortfolio = parseFloat(portfolioRows[0]?.total_portfolio || 0);
                console.log(`      Total portfolio size: K${new Intl.NumberFormat('en-US').format(totalPortfolio.toFixed(2))}`);

                reportTrackers[reportId].percentage = 20;

                console.log('[4/6] Fetching loan data for PAR analysis...');
                
                // Build where clause based on filters
                let whereClause = `l.loan_status = 'ACTIVE'`;
                const queryParams = [];

                whereClause = await appendParOfficerOrSupervisorFilter(whereClause, queryParams, officer, supervisor);

                if (product && product !== 'All') {
                    whereClause += ` AND l.loan_product = ?`;
                    queryParams.push(product);
                }

                whereClause = appendBranchFilter(whereClause, queryParams, branch, 'l');
                if (dateFrom && dateTo) {
                    whereClause += ` AND DATE(l.loan_added_date) BETWEEN ? AND ?`;
                    queryParams.push(dateFrom, dateTo);
                } else if (dateFrom) {
                    whereClause += ` AND DATE(l.loan_added_date) >= ?`;
                    queryParams.push(dateFrom);
                } else if (dateTo) {
                    whereClause += ` AND DATE(l.loan_added_date) <= ?`;
                    queryParams.push(dateTo);
                }

                const loanQuery = `
                    SELECT
                        l.loan_id,
                        l.loan_number,
                        l.loan_principal,
                        l.loan_date,
                        l.loan_period,
                        l.loan_interest,
                        l.loan_added_by,
                        l.branch,
                        l.loan_product,
                        l.loan_customer,
                        l.customer_type,
                        CASE
                            WHEN l.customer_type = 'individual' THEN CONCAT(ic.Firstname, ' ', ic.Lastname)
                            WHEN l.customer_type = 'group' THEN CONCAT(g.group_name, ' (', g.group_code, ')')
                            ELSE 'Unknown Customer'
                        END as customer_name,
                        e.Firstname as officer_first_name,
                        e.Lastname as officer_last_name,
                        ${sqlRelationshipSupervisorNameExpr('rel_sup')} as relationship_supervisor,
                        b.BranchName as branch_name,
                        lp.product_name
                    FROM loan l
                    LEFT JOIN individual_customers ic ON l.loan_customer = ic.id AND l.customer_type = 'individual'
                    LEFT JOIN \`groups\` g ON l.loan_customer = g.group_id AND l.customer_type = 'group'
                    LEFT JOIN employees e ON l.loan_added_by = e.id
                    LEFT JOIN employees rel_sup ON rel_sup.id = e.Supervisor
                    ${sqlBranchJoin('l', 'b')}
                    LEFT JOIN loan_products lp ON l.loan_product = lp.loan_product_id
                    WHERE ${whereClause}
                `;

                db.query(loanQuery, queryParams, async (err, loans) => {
                    if (err) {
                        reject(err);
                        return;
                    }

                    console.log(`      Found ${loans.length} active loans to analyze`);
                    reportTrackers[reportId].percentage = 40;

                    console.log('[5/6] Processing loan data for PAR calculations...');
                    const processedLoans = [];
                    let processedCount = 0;

                    try {
                        // Process each loan for PAR calculations
                        for (const loan of loans) {
                            const loanId = loan.loan_id;
                            const customerName = loan.customer_name || 'Unknown';
                            const officerName = `${loan.officer_first_name || ''} ${loan.officer_last_name || ''}`.trim() || 'Unknown';
                            let branchName = loan.branch_name || '';
                            if (!branchName && loan.branch) {
                                const branchRow = await findBranch(loan.branch);
                                branchName = branchRow ? branchRow.BranchName : '';
                            }
                            if (!branchName) {
                                branchName = 'Unknown';
                            }

                            // Get outstanding principal
                            const outstandingPrincipal = await getOutstandingPrincipal(loanId, db);

                            // Get all payments for this loan
                            const allPayments = await getAllPaymentsForLoan(loanId, db, reportAsOfDate);

                            // Calculate PAR for different day ranges
                            const par_1_7_days = calculatePARForDaysRange(allPayments, 1, 7);
                            const par_8_15_days = calculatePARForDaysRange(allPayments, 8, 15);
                            const par_16_30_days = calculatePARForDaysRange(allPayments, 16, 30);
                            const par_31plus_days = calculatePARForDaysRange(allPayments, 31, 999999);
                            const par_1day = calculatePARForDaysRange(allPayments, 1, 999999);
                            const daysInArrears = allPayments.reduce((maxDays, payment) => {
                                const overdueDays = parseInt(payment.days_overdue || 0, 10);
                                return overdueDays > maxDays ? overdueDays : maxDays;
                            }, 0);

                            processedLoans.push({
                                customerName,
                                loanNumber: loan.loan_number || 'N/A',
                                productName: loan.product_name || 'N/A',
                                officerName,
                                relationshipSupervisor: loan.relationship_supervisor || 'N/A',
                                branchName,
                                loanDate: loan.loan_date,
                                loanPeriod: loan.loan_period,
                                loanInterest: loan.loan_interest,
                                currentBalance: parseFloat(outstandingPrincipal) || 0,
                                days_in_arrears: daysInArrears,
                                rbm_classification: determineRBMClassification(daysInArrears),
                                par_1_7_days,
                                par_8_15_days,
                                par_16_30_days,
                                par_31plus_days,
                                par_1day
                            });

                            processedCount++;
                            if (processedCount % 10 === 0) {
                                console.log(`      Processed ${processedCount}/${loans.length} loans`);
                            }
                        }

                        reportTrackers[reportId].percentage = 80;

                        console.log('[6/6] Generating Excel-style HTML report and saving to file...');

                        // Get the branch name if a specific branch was selected
                        let selectedBranchName = 'All Branches';
                        if (branch && branch !== 'All') {
                            try {
                                const branchResult = await new Promise((resolve, reject) => {
                                    db.query('SELECT BranchName FROM branches WHERE id = ?',
                                        [branch], (err, result) => {
                                            if (err) {
                                                reject(err);
                                            } else {
                                                resolve(result);
                                            }
                                        });
                                });

                                if (branchResult && branchResult.length > 0) {
                                    selectedBranchName = branchResult[0].BranchName;
                                }
                            } catch (error) {
                                console.error(`Error getting selected branch name: ${error.message}`);
                            }
                        }

                        // Generate HTML report that matches Excel format
                        const reportHtml = generateExcelStylePARReport(
                            currentDate,
                            processedLoans,
                            totalPortfolio,
                            selectedBranchName,
                            dateFrom,
                            dateTo
                        );

                        // Write to the file path stored in the tracker
                        fs.writeFileSync(reportTrackers[reportId].filePath, reportHtml);

                        reportTrackers[reportId].percentage = 100;

                        console.log(`\n✅ PAR Report generated successfully: ${reportTrackers[reportId].filePath}`);
                        console.log('====== PAR REPORT GENERATION COMPLETED ======');

                        // Resolve with success
                        resolve(true);
                    } catch (error) {
                        reject(error);
                    }
                });
            });
        });
    } catch (error) {
        console.error('\n❌ Error generating PAR report:', error);
        console.log('====== PAR REPORT GENERATION FAILED ======');
        throw error;
    }
}

/**
 * Function to generate HTML report in Excel format - update to include date filters in the header
 * @param {string} currentDate - Current date
 * @param {Array} loans - Array of processed loans
 * @param {number} totalPortfolio - Total portfolio amount
 * @param {string} branchName - Branch name
 * @param {string} dateFrom - Start date
 * @param {string} dateTo - End date
 * @returns {string} HTML report content
 */
function generateExcelStylePARReport(currentDate, loans, totalPortfolio, branchName, dateFrom, dateTo) {
    // Format date for display
    const formatDisplayDate = (dateString) => {
        if (!dateString) return '';
        return moment(dateString).format('MM/DD/YYYY');
    };

    // Format functions
    const formatCurrency = (amount) => {
        if (isNaN(amount) || amount === null || amount === undefined) {
            return '0.00';
        }
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    };

    const formatPercentage = (value) => {
        if (isNaN(value) || value === null || value === undefined || value === 0) {
            return '0.00%';
        }
        return new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value / 100);
    };

    // Create date range display
    let dateRangeDisplay = '';
    if (dateFrom && dateTo) {
        dateRangeDisplay = `Date Range: ${formatDisplayDate(dateFrom)} - ${formatDisplayDate(dateTo)}`;
    } else if (dateFrom) {
        dateRangeDisplay = `From: ${formatDisplayDate(dateFrom)}`;
    } else if (dateTo) {
        dateRangeDisplay = `To: ${formatDisplayDate(dateTo)}`;
    }

    // Calculate totals and PAR percentages
    const totalCurrentBalance = loans.reduce((sum, loan) => sum + loan.currentBalance, 0);
    const total_1_7_days = loans.reduce((sum, loan) => sum + loan.par_1_7_days, 0);
    const total_8_15_days = loans.reduce((sum, loan) => sum + loan.par_8_15_days, 0);
    const total_16_30_days = loans.reduce((sum, loan) => sum + loan.par_16_30_days, 0);
    const total_31plus_days = loans.reduce((sum, loan) => sum + loan.par_31plus_days, 0);
    const total_1day = loans.reduce((sum, loan) => sum + loan.par_1day, 0);

    // Calculate percentages
    const par_1_7_percent = totalCurrentBalance > 0 ? (total_1_7_days / totalCurrentBalance) * 100 : 0;
    const par_8_15_percent = totalCurrentBalance > 0 ? (total_8_15_days / totalCurrentBalance) * 100 : 0;
    const par_16_30_percent = totalCurrentBalance > 0 ? (total_16_30_days / totalCurrentBalance) * 100 : 0;
    const par_31plus_percent = totalCurrentBalance > 0 ? (total_31plus_days / totalCurrentBalance) * 100 : 0;
    const par_1day_percent = totalCurrentBalance > 0 ? (total_1day / totalCurrentBalance) * 100 : 0;

    // Generate loan rows
    const loanRows = loans.map(loan => {
        const loan_par_1_7_percent = loan.currentBalance > 0 ? (loan.par_1_7_days / loan.currentBalance) * 100 : 0;
        const loan_par_8_15_percent = loan.currentBalance > 0 ? (loan.par_8_15_days / loan.currentBalance) * 100 : 0;
        const loan_par_16_30_percent = loan.currentBalance > 0 ? (loan.par_16_30_days / loan.currentBalance) * 100 : 0;
        const loan_par_31plus_percent = loan.currentBalance > 0 ? (loan.par_31plus_days / loan.currentBalance) * 100 : 0;
        const loan_par_1day_percent = loan.currentBalance > 0 ? (loan.par_1day / loan.currentBalance) * 100 : 0;

        return `
            <tr>
                <td>${loan.customerName}</td>
                <td>${loan.loanNumber}</td>
                <td>${loan.productName}</td>
                <td>${loan.officerName}</td>
                <td>${loan.relationshipSupervisor || 'N/A'}</td>
                <td>${loan.branchName}</td>
                <td style="text-align: right;">${formatCurrency(loan.currentBalance)}</td>
                <td style="text-align: right;">${formatCurrency(loan.par_1_7_days)}</td>
                <td style="text-align: right;">${formatPercentage(loan_par_1_7_percent)}</td>
                <td style="text-align: right;">${formatCurrency(loan.par_8_15_days)}</td>
                <td style="text-align: right;">${formatPercentage(loan_par_8_15_percent)}</td>
                <td style="text-align: right;">${formatCurrency(loan.par_16_30_days)}</td>
                <td style="text-align: right;">${formatPercentage(loan_par_16_30_percent)}</td>
                <td style="text-align: right;">${formatCurrency(loan.par_31plus_days)}</td>
                <td style="text-align: right;">${formatPercentage(loan_par_31plus_percent)}</td>
                <td style="text-align: right;">${formatCurrency(loan.par_1day)}</td>
                <td style="text-align: right;">${formatPercentage(loan_par_1day_percent)}</td>
                <td>${loan.rbm_classification || determineRBMClassification(loan.days_in_arrears || 0)}</td>
            </tr>
        `;
    }).join('');

    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>PAR Report - ${moment(currentDate).format('MM/DD/YYYY')}</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; margin: 10px; }
                table { border-collapse: collapse; width: 100%; font-size: 11px; }
                th, td { border: 1px solid #000; padding: 4px 6px; }
                .header-row td { background-color: #f0f0f0; font-weight: bold; text-align: center; }
                .total-row { background-color: #e0e0e0; font-weight: bold; }
                .date-filter { font-style: italic; color: #666; }
                @media print { body { margin: 0; } }
                .action { float: right; margin-bottom: 10px; }
                button { padding: 5px 15px; margin-left: 5px; cursor: pointer; }
            </style>
            <!-- Include SheetJS library for Excel exports -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
            <script>
                function exportData(type) {
                    const fileName = 'PAR_Report.' + type;
                    const table = document.getElementById("results-table");
                    const wb = XLSX.utils.table_to_book(table);
                    XLSX.writeFile(wb, fileName);
                }
            </script>
        </head>
        <body>
            <div class="action">
                <span>Export table to:</span>
                <button onclick="exportData('xlsx')">Excel (xlsx)</button>
                <button onclick="exportData('xls')">Excel (xls)</button>
                <button onclick="exportData('csv')">CSV</button>
            </div>
            
            <table id="results-table">
                <tr class="header-row">
                    <td>Sycamore Limited (MALAWI)</td>
                    <td colspan="5">Summarized Outstanding portfolio Report</td>
                    <td>As Of:</td>
                    <td colspan="2">${moment(currentDate).format('MM/DD/YYYY')}</td>
                    <td colspan="7"></td>
                </tr>
                <tr class="header-row">
                    <td>Branch:</td>
                    <td colspan="5">${branchName}</td>
                    <td colspan="10" class="date-filter">${dateRangeDisplay}</td>
                </tr>
                <tr class="header-row">
                    <td>Client Name</td>
                    <td>Loan #</td>
                    <td>Product</td>
                    <td>Loan Officer</td>
                    <td>Relationship Supervisor</td>
                    <td>Branch</td>
                    <td>CURRENT BAL<br>(Principal Balances MK)</td>
                    <td>1-7 days MK</td>
                    <td>%PAR</td>
                    <td>8_15 days MK</td>
                    <td>%PAR</td>
                    <td>16_30D MK</td>
                    <td>%PAR</td>
                    <td>>31DAYS MK</td>
                    <td>%PAR</td>
                    <td>1Day MK</td>
                    <td>%PAR</td>
                    <td>RBM Loan Classification</td>
                </tr>
                ${loanRows}
                <tr class="total-row">
                    <td colspan="5">TOTAL</td>
                    <td style="text-align: right;">${formatCurrency(totalCurrentBalance)}</td>
                    <td style="text-align: right;">${formatCurrency(total_1_7_days)}</td>
                    <td style="text-align: right;">${formatPercentage(par_1_7_percent)}</td>
                    <td style="text-align: right;">${formatCurrency(total_8_15_days)}</td>
                    <td style="text-align: right;">${formatPercentage(par_8_15_percent)}</td>
                    <td style="text-align: right;">${formatCurrency(total_16_30_days)}</td>
                    <td style="text-align: right;">${formatPercentage(par_16_30_percent)}</td>
                    <td style="text-align: right;">${formatCurrency(total_31plus_days)}</td>
                    <td style="text-align: right;">${formatPercentage(par_31plus_percent)}</td>
                    <td style="text-align: right;">${formatCurrency(total_1day)}</td>
                    <td style="text-align: right;">${formatPercentage(par_1day_percent)}</td>
                </tr>
            </table>
        </body>
        </html>
    `;
}

/**
 * Enhanced PAR Report with detailed loan information
 * @param {number} reportId - Report ID
 * @param {string} officer - Officer filter
 * @param {string} product - Product filter
 * @param {string} branch - Branch filter
 * @param {string} dateFrom - Start date
 * @param {string} dateTo - End date
 * @param {Object} db - Database connection
 * @param {Object} reportTrackers - Report trackers object
 * @returns {Promise<boolean>} Success status
 */
async function generatePARReportV2Enhanced(reportId, officer, product, branch, dateFrom, dateTo, supervisor, db, reportTrackers) {
    console.log('====== ENHANCED DETAILED PORTFOLIO REPORT GENERATION STARTED ======');
    console.log('[1/6] Establishing database connection...');
    console.log(`Report will be saved to: ${reportTrackers[reportId].filePath}`);
    console.log(`Filters - Officer: ${officer || 'All'}, Product: ${product}, Branch: ${branch}`);
    console.log(`Date Range - From: ${dateFrom} To: ${dateTo}`);

    try {
        dateFrom = normalizeParDateInput(dateFrom);
        dateTo = normalizeParDateInput(dateTo);
        if (dateFrom && dateTo && moment(dateFrom).isAfter(moment(dateTo))) {
            const swap = dateFrom;
            dateFrom = dateTo;
            dateTo = swap;
        }
        reportTrackers[reportId].percentage = 10;
        console.log('[2/6] Database connection established successfully.');

        // Outstanding/arrears must be valued as of current date (current month),
        // while dateFrom/dateTo only filter which loans are included.
        const currentDate = moment().format('YYYY-MM-DD');
        const reportAsOfDate = currentDate;

        console.log('[3/6] Calculating dashboard-aligned portfolio totals (Reports::get_summary_metrics)...');
        let whereClause = `l.loan_status = 'ACTIVE'`;
        const queryParams = [];

        whereClause = await appendParOfficerOrSupervisorFilter(whereClause, queryParams, officer, supervisor);

        if (product && product !== 'All') {
            whereClause += ` AND l.loan_product = ?`;
            queryParams.push(product);
        }

        whereClause = appendBranchFilter(whereClause, queryParams, branch, 'l');
        if (dateFrom && dateTo) {
            whereClause += ` AND DATE(l.loan_added_date) BETWEEN ? AND ?`;
            queryParams.push(dateFrom, dateTo);
        } else if (dateFrom) {
            whereClause += ` AND DATE(l.loan_added_date) >= ?`;
            queryParams.push(dateFrom);
        } else if (dateTo) {
            whereClause += ` AND DATE(l.loan_added_date) <= ?`;
            queryParams.push(dateTo);
        }

        return new Promise((resolve, reject) => {
                // Gross Loan Portfolio = all unpaid principal (regardless of schedule date)
                // Outstanding Balance  = unpaid principal + accrued charges earned up to end of as-of month
                const dashboardTotalSql = `
                    SELECT
                        COUNT(DISTINCT l.loan_customer) AS customer_count,
                        COALESCE(SUM(${sqlUnpaidPrincipalExpr('ps')}), 0) AS gross_loan_portfolio,
                        COALESCE(SUM(
                            CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID')
                                AND ps.payment_schedule <= LAST_DAY(DATE('${reportAsOfDate}'))
                            THEN ${sqlUnpaidChargesExpr('ps')} ELSE 0 END
                        ), 0) AS total_accrued_charges,
                        COALESCE(SUM(${sqlUnpaidPrincipalExpr('ps')}), 0)
                        + COALESCE(SUM(
                            CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID')
                                AND ps.payment_schedule <= LAST_DAY(DATE('${reportAsOfDate}'))
                            THEN ${sqlUnpaidChargesExpr('ps')} ELSE 0 END
                        ), 0) AS total_outstanding_balance
                    FROM payement_schedules ps
                    INNER JOIN loan l ON l.loan_id = ps.loan_id
                    WHERE ${whereClause}
                `;

                db.query(dashboardTotalSql, queryParams, async (err, portfolioRows) => {
                if (err) {
                    reject(err);
                    return;
                }

                const customerCount = parseInt(portfolioRows[0]?.customer_count || 0);
                const totalPortfolio = parseFloat(portfolioRows[0]?.gross_loan_portfolio || 0);
                const totalAccruedChargesSummary = parseFloat(portfolioRows[0]?.total_accrued_charges || 0);
                const totalOutstandingBalanceSummary = parseFloat(portfolioRows[0]?.total_outstanding_balance || 0);
                console.log(`      Gross Loan Portfolio (unpaid principal): K${new Intl.NumberFormat('en-US').format(totalPortfolio.toFixed(2))}`);
                console.log(`      Total Accrued Charges: K${new Intl.NumberFormat('en-US').format(totalAccruedChargesSummary.toFixed(2))}`);
                console.log(`      Total Outstanding Balance (principal + accrued charges): K${new Intl.NumberFormat('en-US').format(totalOutstandingBalanceSummary.toFixed(2))}`);

                reportTrackers[reportId].percentage = 20;

                console.log('[4/6] Fetching detailed loan data with PAR analysis...');
                // Enhanced query with aggregated metrics (same CASE rules as dashboard for balances / arrears).
                const enhancedLoanQuery = `
                    SELECT 
                        l.loan_id,
                        l.loan_number,
                        l.loan_principal,
                        l.loan_amount_total as total_loan_amount,
                        l.loan_period,
                        l.period_type,
                        l.loan_interest,
                        l.loan_added_date,
                        l.loan_date,
                        l.loan_added_by,
                        l.branch,
                        l.loan_product,
                        l.loan_customer,
                        l.customer_type,
                        l.loan_status,
                        CASE
                            WHEN l.customer_type = 'individual' THEN CONCAT(ic.Firstname, ' ', COALESCE(ic.Lastname, ''))
                            WHEN l.customer_type = 'group' THEN CONCAT(g.group_name, ' (', g.group_code, ')')
                            ELSE 'Unknown Customer'
                        END as customer_name,
                        CASE
                            WHEN l.customer_type = 'group' THEN g.group_name
                            ELSE 'N/A'
                        END as customer_group_name,
                        CONCAT(e.Firstname, ' ', COALESCE(e.Lastname, '')) as loan_officer,
                        ${sqlRelationshipSupervisorNameExpr('rel_sup')} as relationship_supervisor,
                        b.BranchName as branch_name,
                        lp.product_name,
                        l.loan_amount_term as installment_amount,
                        COALESCE(ps_metrics.gross_loan_portfolio, 0) as gross_loan_portfolio,
                        COALESCE(ps_metrics.accrued_charges, 0) as accrued_charges,
                        COALESCE(ps_metrics.outstanding_balance, 0) as outstanding_balance,
                        COALESCE(ps_metrics.total_expected, 0) as total_expected_installments,
                        COALESCE(ps_metrics.total_collected, 0) as actual_payments,
                        COALESCE(ps_metrics.payments_in_arrears, 0) as payments_in_arrears,
                        COALESCE(ps_metrics.amount_in_arrears, 0) as amount_in_arrears,
                        COALESCE(ps_metrics.days_in_arrears, 0) as days_in_arrears,
                        ps_metrics.maturity_date,
                        tx_metrics.last_payment_date
                    FROM loan l
                    LEFT JOIN individual_customers ic ON l.loan_customer = ic.id AND l.customer_type = 'individual'
                    LEFT JOIN \`groups\` g ON l.loan_customer = g.group_id AND l.customer_type = 'group'
                    LEFT JOIN employees e ON l.loan_added_by = e.id
                    LEFT JOIN employees rel_sup ON rel_sup.id = e.Supervisor
                    ${sqlBranchJoin('l', 'b')}
                    LEFT JOIN loan_products lp ON l.loan_product = lp.loan_product_id
                    LEFT JOIN (
                        SELECT
                            ps.loan_id,
                            -- Gross Loan Portfolio: unpaid principal with charge-first allocation
                            SUM(${sqlUnpaidPrincipalExpr('ps')}) AS gross_loan_portfolio,
                            -- Accrued Charges: unpaid interest/fees earned up to end of as-of month (charge-first allocation)
                            SUM(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID')
                                AND ps.payment_schedule <= LAST_DAY(DATE('${reportAsOfDate}'))
                                THEN ${sqlUnpaidChargesExpr('ps')} ELSE 0 END) AS accrued_charges,
                            -- Outstanding Balance: unpaid principal + unpaid accrued charges
                            SUM(${sqlUnpaidPrincipalExpr('ps')})
                                + SUM(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID')
                                    AND ps.payment_schedule <= LAST_DAY(DATE('${reportAsOfDate}'))
                                    THEN ${sqlUnpaidChargesExpr('ps')} ELSE 0 END) AS outstanding_balance,
                            SUM(CASE WHEN li.loan_status = 'ACTIVE' AND ps.payment_schedule <= DATE('${reportAsOfDate}') THEN ps.amount ELSE 0 END) AS total_expected,
                            SUM(CASE WHEN li.loan_status = 'ACTIVE' AND ps.payment_schedule <= DATE('${reportAsOfDate}') THEN ps.paid_amount ELSE 0 END) AS total_collected,
                            COUNT(CASE WHEN li.loan_status = 'ACTIVE'
                                AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < DATE('${reportAsOfDate}')
                                AND COALESCE(ps.amount, 0) > COALESCE(ps.paid_amount, 0) THEN 1 END) AS payments_in_arrears,
                            SUM(CASE WHEN li.loan_status = 'ACTIVE'
                                AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < DATE('${reportAsOfDate}')
                                THEN COALESCE(ps.amount, 0) - COALESCE(ps.paid_amount, 0) ELSE 0 END) AS amount_in_arrears,
                            COALESCE(MAX(CASE WHEN li.loan_status = 'ACTIVE'
                                AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < DATE('${reportAsOfDate}')
                                AND COALESCE(ps.amount, 0) > COALESCE(ps.paid_amount, 0)
                                THEN DATEDIFF(DATE('${reportAsOfDate}'), ps.payment_schedule) END), 0) AS days_in_arrears,
                            MAX(ps.payment_schedule) AS maturity_date
                        FROM payement_schedules ps
                        INNER JOIN loan li ON li.loan_id = ps.loan_id
                        GROUP BY ps.loan_id
                    ) ps_metrics ON ps_metrics.loan_id = l.loan_id
                    LEFT JOIN (
                        SELECT account_number, MAX(server_time) as last_payment_date
                        FROM \`transaction\`
                        WHERE credit > 0
                        GROUP BY account_number
                    ) tx_metrics ON tx_metrics.account_number = l.loan_number
                    WHERE ${whereClause}
                `;

                db.query(enhancedLoanQuery, queryParams, async (err, loans) => {
                    if (err) {
                        reject(err);
                        return;
                    }

                    console.log(`      Found ${loans.length} active loans for enhanced analysis`);
                    reportTrackers[reportId].percentage = 40;

                    console.log('[5/6] Processing enhanced loan data with detailed PAR analysis...');
                    const processedLoans = [];

                    try {
                        // Process each loan with comprehensive analysis
                        for (let i = 0; i < loans.length; i++) {
                            const loan = loans[i];
                            const loanId = loan.loan_id;
                            const loanNum = loan.loan_number;

                            const arrearsInfo = {
                                days_in_arrears: parseInt(loan.days_in_arrears, 10) || 0,
                                amount_in_arrears: parseFloat(loan.amount_in_arrears) || 0,
                                payments_in_arrears: parseInt(loan.payments_in_arrears, 10) || 0
                            };
                            const outstandingBalance = parseFloat(loan.outstanding_balance) || 0;
                            const paymentTotals = {
                                total_expected: parseFloat(loan.total_expected_installments) || 0,
                                total_collected: parseFloat(loan.actual_payments) || 0
                            };
                            const lastPaymentDate = loan.last_payment_date ? moment(loan.last_payment_date).format('YYYY-MM-DD') : null;
                            console.log(`      Processing loan ${i + 1}/${loans.length}: ${loanNum}`);
                            const maturityDate = loan.maturity_date ? moment(loan.maturity_date).format('YYYY-MM-DD') : null;

                            // Calculate collection rate
                            const collectionRate = paymentTotals.total_expected > 0 
                                ? (paymentTotals.total_collected / paymentTotals.total_expected) * 100 
                                : 0;

                            const processedLoan = {
                                // Basic loan information
                                customer_name: loan.customer_name || 'Unknown',
                                customer_group_name: loan.customer_group_name || 'N/A',
                                loan_number: loan.loan_number || 'N/A',
                                product_name: loan.product_name || 'N/A',
                                branch_name: loan.branch_name || 'Unknown',
                                loan_officer: loan.loan_officer || 'Unknown',
                                relationship_supervisor: loan.relationship_supervisor || 'N/A',

                                // Loan details - ADDED MISSING FIELDS
                                loan_date: loan.loan_date,
                                loan_period: loan.loan_period,
                                period_type: loan.period_type,
                                loan_interest: loan.loan_interest,

                                // Financial details
                                loan_principal: parseFloat(loan.loan_principal) || 0,
                                total_loan_amount: parseFloat(loan.total_loan_amount) || 0,
                                installment_amount: parseFloat(loan.installment_amount) || 0,
                                gross_loan_portfolio: parseFloat(loan.gross_loan_portfolio) || 0,
                                accrued_charges: parseFloat(loan.accrued_charges) || 0,
                                outstanding_balance: outstandingBalance,
                                total_expected_installments: paymentTotals.total_expected,
                                actual_payments: paymentTotals.total_collected,
                                collection_rate: collectionRate,
                                collateral_value: 0, // Set to 0 for now

                                // Arrears and PAR information
                                days_in_arrears: arrearsInfo.days_in_arrears,
                                amount_in_arrears: arrearsInfo.amount_in_arrears,
                                payments_in_arrears: arrearsInfo.payments_in_arrears,

                                // PAR classification (most severe bucket this loan falls into)
                                par_classification: arrearsInfo.days_in_arrears > 180 ? 'PAR180'
                                    : arrearsInfo.days_in_arrears > 90  ? 'PAR90'
                                    : arrearsInfo.days_in_arrears > 60  ? 'PAR60'
                                    : arrearsInfo.days_in_arrears > 30  ? 'PAR30'
                                    : arrearsInfo.days_in_arrears > 0   ? 'PAR1'
                                    : 'Current',

                                // Dates
                                last_payment_date: lastPaymentDate,
                                maturity_date: maturityDate,
                                loan_added_date: loan.loan_added_date,

                                // Status and classification
                                loan_status: loan.loan_status,
                                risk_classification: determineRiskClassification(arrearsInfo.days_in_arrears),
                                rbm_classification: determineRBMClassification(arrearsInfo.days_in_arrears),
                                customer_type: loan.customer_type
                            };

                            processedLoans.push(processedLoan);

                            if (loans.length > 0 && (i % 50 === 0 || i === loans.length - 1)) {
                                const p = 40 + Math.floor(((i + 1) / loans.length) * 40);
                                reportTrackers[reportId].percentage = Math.min(80, p);
                            }
                        }

                        console.log(`Successfully processed ${processedLoans.length} loans`);
                        reportTrackers[reportId].percentage = 80;

                        // Compute strict international PAR metrics:
                        // PAR(N) = SUM(full outstanding principal of loans where days_in_arrears > N)
                        //          ─────────────────────────────────────────────────────────────────
                        //                         Total Gross Loan Portfolio
                        const grossTotal = processedLoans.reduce((s, l) => s + (l.gross_loan_portfolio || 0), 0);
                        const parSummary = [
                            { label: 'PAR1',   days: 1   },
                            { label: 'PAR30',  days: 30  },
                            { label: 'PAR60',  days: 60  },
                            { label: 'PAR90',  days: 90  },
                            { label: 'PAR180', days: 180 },
                        ].map(({ label, days }) => {
                            const atRiskLoans = processedLoans.filter(l => (l.days_in_arrears || 0) > days);
                            const principal   = atRiskLoans.reduce((s, l) => s + (l.gross_loan_portfolio || 0), 0);
                            const rate        = grossTotal > 0 ? (principal / grossTotal) * 100 : 0;
                            return { label, days, loanCount: atRiskLoans.length, principal, rate };
                        });
                        console.log('[PAR Summary]', parSummary.map(p => `${p.label}: ${p.rate.toFixed(2)}%`).join(' | '));

                        console.log('[6/6] Generating Enhanced PAR Portfolio Report...');

                        // Get filter display names
                        const filterDisplayNames = await getFilterDisplayNames(branch, officer, product, db);

                        // Generate the enhanced HTML report
                        const reportHtml = generateEnhancedPortfolioReport(
                            currentDate,
                            processedLoans,
                            totalPortfolio,
                            filterDisplayNames,
                            dateFrom,
                            dateTo,
                            reportAsOfDate,
                            totalOutstandingBalanceSummary,
                            totalAccruedChargesSummary,
                            customerCount,
                            parSummary
                        );

                        // Write to file
                        fs.writeFileSync(reportTrackers[reportId].filePath, reportHtml);
                        reportTrackers[reportId].percentage = 100;

                        console.log(`\n✅ Enhanced Portfolio Report generated: ${reportTrackers[reportId].filePath}`);
                        console.log(`Total loans in report: ${processedLoans.length}`);
                        console.log('====== ENHANCED DETAILED PORTFOLIO REPORT COMPLETED ======');

                        resolve(true);

                    } catch (error) {
                        console.error('Error in loan processing loop:', error);
                        reject(error);
                    }
                });
            });
        });
    } catch (error) {
        console.error('\n❌ Error generating enhanced PAR report:', error);
        throw error;
    }
}

/**
 * Calculate maturity date based on loan date and period
 * @param {Date} loanDate - Loan date
 * @param {number} period - Loan period
 * @param {string} periodType - Period type (days, weeks, months, years)
 * @returns {string} Formatted maturity date
 */
async function calculateMaturityDate(loanId, db) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT MAX(payment_schedule) as maturity_date
            FROM payement_schedules
            WHERE loan_id = ?
        `;

        db.query(query, [loanId], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            const date = result.maturity_date;
            resolve(date ? moment(date).format('YYYY-MM-DD') : null);
        });
    });
}

/**
 * Get detailed payment analysis for a loan
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<Object>} Payment analysis data
 */
async function getDetailedPaymentAnalysis(loanId, db, asOfDate = moment().format('YYYY-MM-DD')) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                SUM(CASE WHEN DATEDIFF(DATE(?), payment_schedule) BETWEEN 1 AND 14
                    AND status = 'NOT PAID' THEN (amount - paid_amount) ELSE 0 END) as par_1_14,
                SUM(CASE WHEN DATEDIFF(DATE(?), payment_schedule) BETWEEN 15 AND 29
                    AND status = 'NOT PAID' THEN (amount - paid_amount) ELSE 0 END) as par_15_29,
                SUM(CASE WHEN DATEDIFF(DATE(?), payment_schedule) BETWEEN 30 AND 59
                    AND status = 'NOT PAID' THEN (amount - paid_amount) ELSE 0 END) as par_30_59,
                SUM(CASE WHEN DATEDIFF(DATE(?), payment_schedule) BETWEEN 60 AND 89
                    AND status = 'NOT PAID' THEN (amount - paid_amount) ELSE 0 END) as par_60_89,
                SUM(CASE WHEN DATEDIFF(DATE(?), payment_schedule) BETWEEN 90 AND 179
                    AND status = 'NOT PAID' THEN (amount - paid_amount) ELSE 0 END) as par_90_179,
                SUM(CASE WHEN DATEDIFF(DATE(?), payment_schedule) BETWEEN 180 AND 364
                    AND status = 'NOT PAID' THEN (amount - paid_amount) ELSE 0 END) as par_180_364,
                SUM(CASE WHEN DATEDIFF(DATE(?), payment_schedule) >= 365
                    AND status = 'NOT PAID' THEN (amount - paid_amount) ELSE 0 END) as par_365_plus
            FROM payement_schedules
            WHERE loan_id = ? AND payment_schedule < DATE(?)
        `;

        db.query(query, [asOfDate, asOfDate, asOfDate, asOfDate, asOfDate, asOfDate, asOfDate, loanId, asOfDate], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            resolve({
                par_1_14: parseFloat(result.par_1_14) || 0,
                par_15_29: parseFloat(result.par_15_29) || 0,
                par_30_59: parseFloat(result.par_30_59) || 0,
                par_60_89: parseFloat(result.par_60_89) || 0,
                par_90_179: parseFloat(result.par_90_179) || 0,
                par_180_364: parseFloat(result.par_180_364) || 0,
                par_365_plus: parseFloat(result.par_365_plus) || 0
            });
        });
    });
}

/**
 * Calculate outstanding balance for a loan
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<number>} Outstanding balance
 */
async function calculateOutstandingBalance(loanId, db, asOfDate = moment().format('YYYY-MM-DD')) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                COALESCE(SUM(amount), 0) - COALESCE(SUM(paid_amount), 0) as outstanding_balance
            FROM payement_schedules
            WHERE loan_id = ? AND payment_schedule <= DATE(?)
        `;

        db.query(query, [loanId, asOfDate], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            resolve(parseFloat(result.outstanding_balance) || 0);
        });
    });
}

/**
 * Helper function to calculate detailed arrears information
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<Object>} Arrears details
 */
async function calculateArrearsDetails(loanId, db, asOfDate = moment().format('YYYY-MM-DD')) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                COUNT(CASE WHEN status = 'NOT PAID' AND payment_schedule < DATE(?) THEN 1 END) as payments_in_arrears,
                SUM(CASE WHEN status = 'NOT PAID' AND payment_schedule < DATE(?) 
                         THEN (amount - paid_amount) ELSE 0 END) as amount_in_arrears,
                MAX(CASE WHEN status = 'NOT PAID' AND payment_schedule < DATE(?) 
                         THEN DATEDIFF(DATE(?), payment_schedule) ELSE 0 END) as days_in_arrears
            FROM payement_schedules
            WHERE loan_id = ?
        `;

        db.query(query, [asOfDate, asOfDate, asOfDate, asOfDate, loanId], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            resolve({
                payments_in_arrears: parseInt(result.payments_in_arrears) || 0,
                amount_in_arrears: parseFloat(result.amount_in_arrears) || 0,
                days_in_arrears: parseInt(result.days_in_arrears) || 0
            });
        });
    });
}

/**
 * Calculate payment totals for a loan
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<Object>} Payment totals
 */
async function calculatePaymentTotals(loanId, db, asOfDate = moment().format('YYYY-MM-DD')) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                SUM(amount) as total_expected,
                SUM(paid_amount) as total_collected
            FROM payement_schedules
            WHERE loan_id = ? AND payment_schedule <= DATE(?)
        `;

        db.query(query, [loanId, asOfDate], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            resolve({
                total_expected: parseFloat(result.total_expected) || 0,
                total_collected: parseFloat(result.total_collected) || 0
            });
        });
    });
}

/**
 * Get last payment date for a loan from transaction table
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<string>} Last payment date
 */
async function getLastPaymentDate(loanNumber, db) {
    return new Promise((resolve, reject) => {
        const transactionQuery = `
            SELECT MAX(server_time) as last_payment_date
            FROM transaction
            WHERE account_number = ? AND credit > 0
        `;

        db.query(transactionQuery, [loanNumber], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            const date = result.last_payment_date;
            resolve(date ? moment(date).format('YYYY-MM-DD') : null);
        });
    });
}
/**
 * Determine risk classification based on days in arrears
 * @param {number} daysInArrears - Number of days in arrears
 * @returns {string} Risk classification
 */
function determineRiskClassification(daysInArrears) {
    if (daysInArrears === 0) return 'Current';
    if (daysInArrears <= 30) return 'Watch';
    if (daysInArrears <= 90) return 'Substandard';
    if (daysInArrears <= 180) return 'Doubtful';
    return 'Loss';
}

/**
 * Get filter display names
 * @param {string} branch - Branch ID
 * @param {string} officer - Officer ID
 * @param {string} product - Product ID
 * @param {Object} db - Database connection
 * @returns {Promise<Object>} Filter display names
 */
async function getFilterDisplayNames(branch, officer, product, db) {
    const filterNames = {
        branchName: 'All Branches',
        officerName: 'All Officers',
        productName: 'All Products'
    };

    try {
        if (branch && branch !== 'All') {
            const branchResult = await new Promise((resolve, reject) => {
                db.query('SELECT BranchName FROM branches WHERE id = ?', [branch], (err, result) => {
                    if (err) reject(err);
                    else resolve(result);
                });
            });
            if (branchResult && branchResult.length > 0) {
                filterNames.branchName = branchResult[0].BranchName;
            }
        }

        if (officer && officer !== 'All') {
            const officerResult = await new Promise((resolve, reject) => {
                db.query('SELECT CONCAT(Firstname, " ", Lastname) as name FROM employees WHERE id = ?', [officer], (err, result) => {
                    if (err) reject(err);
                    else resolve(result);
                });
            });
            if (officerResult && officerResult.length > 0) {
                filterNames.officerName = officerResult[0].name;
            }
        }

        if (product && product !== 'All') {
            const productResult = await new Promise((resolve, reject) => {
                db.query('SELECT product_name FROM loan_products WHERE loan_product_id = ?', [product], (err, result) => {
                    if (err) reject(err);
                    else resolve(result);
                });
            });
            if (productResult && productResult.length > 0) {
                filterNames.productName = productResult[0].product_name;
            }
        }
    } catch (error) {
        console.error('Error getting filter names:', error);
    }

    return filterNames;
}

/**
 * Generate Enhanced Portfolio Report HTML
 * @param {string} currentDate - Current date
 * @param {Array} loans - Array of processed loans
 * @param {number} totalPortfolio - Total portfolio amount
 * @param {Object} filterDisplayNames - Filter display names
 * @param {string} dateFrom - Start date
 * @param {string} dateTo - End date
 * @returns {string} HTML report content
 */
function generateEnhancedPortfolioReport(currentDate, loans, totalPortfolio, filterDisplayNames, dateFrom, dateTo, reportAsOfDate = null, totalOutstandingBalanceSummary = null, totalAccruedChargesSummary = null, customerCount = null, parSummary = []) {
    const formatCurrency = (amount) => {
        if (isNaN(amount) || amount === null || amount === undefined) {
            return '0.00';
        }
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    };

    const formatPercentage = (value) => {
        if (isNaN(value) || value === null || value === undefined) {
            return '0.00%';
        }
        return new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value / 100);
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return moment(dateString).format('MM/DD/YYYY');
    };

    // Calculate summary statistics — all derived from the same loans array used for PAR
    const totalLoans = loans.length;
    const totalGrossPortfolio     = loans.reduce((sum, loan) => sum + (loan.gross_loan_portfolio || 0), 0);
    const totalAccruedCharges     = loans.reduce((sum, loan) => sum + (loan.accrued_charges || 0), 0);
    const totalOutstandingBalance = loans.reduce((sum, loan) => sum + (loan.outstanding_balance || 0), 0);
    const totalInArrears          = loans.reduce((sum, loan) => sum + (loan.amount_in_arrears || 0), 0);
    const totalPrincipalDisbursed = loans.reduce((sum, loan) => sum + (loan.loan_principal || 0), 0);
    const totalExpectedInstallments = loans.reduce((sum, loan) => sum + (loan.total_expected_installments || 0), 0);
    const totalActualPayments     = loans.reduce((sum, loan) => sum + (loan.actual_payments || 0), 0);
    const totalCollateralValue    = loans.reduce((sum, loan) => sum + (loan.collateral_value || 0), 0);

    // Generate loan rows
    const loanRows = loans.map((loan, index) => {
        return `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td>${loan.customer_name || 'N/A'}</td>
                <td>${loan.customer_group_name || 'N/A'}</td>
                <td>${loan.loan_number || 'N/A'}</td>
                <td>${loan.product_name || 'N/A'}</td>
                <td>${loan.branch_name || 'N/A'}</td>
                <td>${formatDate(loan.loan_date)}</td>
                <td class="text-right">${formatCurrency(loan.loan_principal)}</td>
                <td>${loan.loan_period || 0} ${loan.period_type || ''}</td>
                <td>${loan.loan_interest || 0}%</td>
                <td class="text-right">${formatCurrency(loan.total_loan_amount)}</td>
                <td class="text-right">${formatCurrency(loan.installment_amount)}</td>
                <td class="text-right">${formatCurrency(loan.gross_loan_portfolio)}</td>
                <td class="text-right">${formatCurrency(loan.accrued_charges)}</td>
                <td class="text-right">${formatCurrency(loan.outstanding_balance)}</td>
                <td class="text-right">${formatCurrency(loan.amount_in_arrears)}</td>
                <td class="text-center">${loan.days_in_arrears || 0}</td>
                <td style="font-weight:bold;color:${loan.par_classification === 'Current' ? '#27ae60' : loan.par_classification === 'PAR1' ? '#e67e22' : '#c0392b'};">${loan.par_classification || 'Current'}</td>
                <td>${loan.rbm_classification || determineRBMClassification(loan.days_in_arrears || 0)}</td>
                <td class="text-center">${loan.payments_in_arrears || 0}</td>
                <td class="text-right">${formatPercentage(loan.collection_rate)}</td>
                <td>${formatDate(loan.last_payment_date)}</td>
                <td class="text-right">${formatCurrency(loan.collateral_value)}</td>
                <td>${formatDate(loan.maturity_date)}</td>
                <td class="text-right">${formatCurrency(loan.total_expected_installments)}</td>
                <td class="text-right">${formatCurrency(loan.actual_payments)}</td>
                <td>${loan.loan_status || 'N/A'}</td>
                <td>${loan.loan_officer || 'N/A'}</td>
                <td>${loan.relationship_supervisor || 'N/A'}</td>
                <td>${formatDate(loan.loan_added_date)}</td>
            </tr>
        `;
    });

    // Generate the complete HTML with CORRECT PAR structure
    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detailed Portfolio Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 10px; font-size: 12px; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #153505; padding-bottom: 10px; }
            .header h1 { color: #153505; margin: 0; font-size: 24px; }
            .filters { background-color: #f5f5f5; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #153505; }
            .summary { background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
            .summary-item { text-align: center; padding: 8px; background-color: white; border-radius: 3px; border: 1px solid #ddd; }
            .summary-label { font-weight: bold; color: #153505; font-size: 11px; }
            .summary-value { font-size: 14px; font-weight: bold; color: #333; }
            table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 6px 4px; text-align: left; white-space: nowrap; }
            th { background-color: #153505; color: white; font-weight: bold; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .export-buttons { margin-bottom: 15px; text-align: right; }
            .export-buttons button { padding: 8px 15px; margin-left: 5px; cursor: pointer; background-color: #153505; color: white; border: none; border-radius: 3px; }
            .export-buttons button:hover { background-color: #0d2503; }
            @media print { 
                .export-buttons { display: none; }
                body { padding: 0; font-size: 10px; }
                th, td { padding: 3px 2px; }
            }
        </style>
        <!-- Include SheetJS library for Excel exports -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script>
            function exportData(type) {
                const fileName = 'Detailed_Portfolio_Report.' + type;
                const table = document.getElementById("main-table");
                const wb = XLSX.utils.table_to_book(table);
                XLSX.writeFile(wb, fileName);
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Detailed Portfolio Report</h1>
            <div>Sycamore Credit Limited - Generated on ${formatDate(currentDate)}</div>
        </div>

        <div class="filters">
            <strong>Filters Applied:</strong> 
            Branch: ${filterDisplayNames.branchName} | 
            Officer: ${filterDisplayNames.officerName} | 
            Product: ${filterDisplayNames.productName}
            ${dateFrom && dateTo ? ` | Period: ${formatDate(dateFrom)} to ${formatDate(dateTo)}` : ''}
            ${reportAsOfDate ? ` | Balance As At: ${formatDate(reportAsOfDate)}` : ''}
        </div>

        ${parSummary.length ? `
        <div style="margin-bottom:20px;">
            <h3 style="color:#153505;margin-bottom:8px;">Portfolio at Risk (PAR)</h3>
            <p style="font-size:11px;color:#555;margin-bottom:8px;">
                PAR(N) = Full outstanding principal of loans with arrears &gt; N days &divide; Total Gross Loan Portfolio.
                Each loan's <em>entire</em> unpaid principal is counted once it crosses the threshold.
            </p>
            <table style="width:auto;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr>
                        <th style="background:#153505;color:white;padding:6px 12px;border:1px solid #ddd;">Metric</th>
                        <th style="background:#153505;color:white;padding:6px 12px;border:1px solid #ddd;">Threshold</th>
                        <th style="background:#153505;color:white;padding:6px 12px;border:1px solid #ddd;">Loans at Risk</th>
                        <th style="background:#153505;color:white;padding:6px 12px;border:1px solid #ddd;">Principal at Risk (MWK)</th>
                        <th style="background:#153505;color:white;padding:6px 12px;border:1px solid #ddd;">PAR %</th>
                    </tr>
                </thead>
                <tbody>
                    ${parSummary.map((p, i) => `
                    <tr style="background:${i % 2 === 0 ? '#f9f9f9' : 'white'}">
                        <td style="padding:6px 12px;border:1px solid #ddd;font-weight:bold;color:#153505;">${p.label}</td>
                        <td style="padding:6px 12px;border:1px solid #ddd;text-align:center;">&gt; ${p.days} days</td>
                        <td style="padding:6px 12px;border:1px solid #ddd;text-align:right;">${p.loanCount.toLocaleString()}</td>
                        <td style="padding:6px 12px;border:1px solid #ddd;text-align:right;">${formatCurrency(p.principal)}</td>
                        <td style="padding:6px 12px;border:1px solid #ddd;text-align:right;font-weight:bold;color:${p.rate > 10 ? '#c0392b' : p.rate > 5 ? '#e67e22' : '#27ae60'};">${p.rate.toFixed(2)}%</td>
                    </tr>`).join('')}
                </tbody>
                <tfoot>
                    <tr style="background:#e8f5e8;font-weight:bold;">
                        <td style="padding:6px 12px;border:1px solid #ddd;" colspan="2">Total Gross Loan Portfolio</td>
                        <td style="padding:6px 12px;border:1px solid #ddd;text-align:right;">${loans.length.toLocaleString()} loans</td>
                        <td style="padding:6px 12px;border:1px solid #ddd;text-align:right;">${formatCurrency(totalGrossPortfolio)}</td>
                        <td style="padding:6px 12px;border:1px solid #ddd;text-align:right;">100.00%</td>
                    </tr>
                </tfoot>
            </table>
        </div>` : ''}

        <div class="summary">
            <div class="summary-item">
                <div class="summary-label">Total Customers</div>
                <div class="summary-value">${customerCount !== null ? customerCount.toLocaleString() : totalLoans}</div>
                <div style="font-size:10px;color:#888">Distinct borrowers</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Loans</div>
                <div class="summary-value">${totalLoans}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Gross Loan Portfolio</div>
                <div class="summary-value">K${formatCurrency(totalGrossPortfolio)}</div>
                <div style="font-size:10px;color:#888">Unpaid principal only</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Accrued Charges</div>
                <div class="summary-value">K${formatCurrency(totalAccruedCharges)}</div>
                <div style="font-size:10px;color:#888">Interest/fees earned, unpaid</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Outstanding Loan Balance</div>
                <div class="summary-value">K${formatCurrency(totalOutstandingBalance)}</div>
                <div style="font-size:10px;color:#888">Principal + accrued charges</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total in Arrears</div>
                <div class="summary-value">K${formatCurrency(totalInArrears)}</div>
                <div style="font-size:10px;color:#888">Overdue unpaid amounts</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Principal Disbursed</div>
                <div class="summary-value">K${formatCurrency(totalPrincipalDisbursed)}</div>
                <div style="font-size:10px;color:#888">Original disbursed principal</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Collection Rate</div>
                <div class="summary-value">${formatPercentage(totalExpectedInstallments > 0 ? (totalActualPayments / totalExpectedInstallments) * 100 : 0)}</div>
            </div>
        </div>

        <div class="export-buttons">
            <span>Export as:</span>
            <button onclick="exportData('xlsx')">Excel (xlsx)</button>
            <button onclick="exportData('csv')">CSV</button>
            <button onclick="window.print()">Print</button>
        </div>

        <table id="main-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Customer Group</th>
                    <th>Loan Number</th>
                    <th>Product</th>
                    <th>Branch</th>
                    <th>Loan Date</th>
                    <th>Principal Amount</th>
                    <th>Loan Period</th>
                    <th>Interest Rate</th>
                    <th>Total Loan Amount</th>
                    <th>Installment Amount</th>
                    <th>Unpaid Principal (MWK)</th>
                    <th>Accrued Charges (MWK)</th>
                    <th>Outstanding Balance (MWK)</th>
                    <th>Amount in Arrears (MWK)</th>
                    <th>Days in Arrears</th>
                    <th>PAR Classification</th>
                    <th>RBM Loan Classification</th>
                    <th>Payments in Arrears</th>
                    <th>Collection Rate</th>
                    <th>Last Payment Date</th>
                    <th>Collateral Value</th>
                    <th>Maturity Date</th>
                    <th>Expected Installments</th>
                    <th>Actual Payments</th>
                    <th>Loan Status</th>
                    <th>Loan Officer</th>
                    <th>Relationship Supervisor</th>
                    <th>Date Added</th>
                </tr>
            </thead>            <tbody>
                ${loanRows.join('')}
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f8f0; font-weight: bold; border-top: 2px solid #153505;">
                    <td class="text-center" colspan="7">TOTALS</td>
                    <td class="text-right">${formatCurrency(loans.reduce((sum, loan) => sum + (loan.loan_principal || 0), 0))}</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-right">${formatCurrency(loans.reduce((sum, loan) => sum + (loan.total_loan_amount || 0), 0))}</td>
                    <td class="text-right">${formatCurrency(loans.reduce((sum, loan) => sum + (loan.installment_amount || 0), 0))}</td>
                    <td class="text-right">${formatCurrency(loans.reduce((sum, loan) => sum + (loan.gross_loan_portfolio || 0), 0))}</td>
                    <td class="text-right">${formatCurrency(loans.reduce((sum, loan) => sum + (loan.accrued_charges || 0), 0))}</td>
                    <td class="text-right">${formatCurrency(totalOutstandingBalance)}</td>
                    <td class="text-right">${formatCurrency(totalInArrears)}</td>
                    <td class="text-center">${Math.round(loans.reduce((sum, loan) => sum + (loan.days_in_arrears || 0), 0) / totalLoans)}</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">${loans.reduce((sum, loan) => sum + (loan.payments_in_arrears || 0), 0)}</td>
                    <td class="text-right">${formatPercentage(loans.reduce((sum, loan) => sum + (loan.collection_rate || 0), 0) / totalLoans)}</td>
                    <td class="text-center">-</td>
                    <td class="text-right">${formatCurrency(loans.reduce((sum, loan) => sum + (loan.collateral_value || 0), 0))}</td>
                    <td class="text-center">-</td>
                    <td class="text-right">${formatCurrency(loans.reduce((sum, loan) => sum + (loan.total_expected_installments || 0), 0))}</td>
                    <td class="text-right">${formatCurrency(loans.reduce((sum, loan) => sum + (loan.actual_payments || 0), 0))}</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </tr>
                </tbody>
            </table>
        </div>


        <div style="margin-top: 30px; padding: 15px; background-color: #f5f5f5; border-radius: 5px; font-size: 11px;">
            <h3 style="margin-top: 0; color: #153505;">Report Notes:</h3>
            <ul style="margin: 0; padding-left: 20px;">
                <li>This is a comprehensive portfolio report showing detailed loan information for all active loans</li>
                <li>Collection Rate = (Actual Payments / Expected Installments) × 100</li>
                <li>Unpaid Principal = total unpaid principal across all outstanding schedules (no charges)</li>
                <li>Accrued Charges = interest/fees earned up to end of current month that remain unpaid</li>
                <li>Outstanding Loan Balance = Unpaid Principal + Accrued Charges</li>
                <li>PAR(N) = Full outstanding principal of loans with arrears &gt; N days &divide; Total Gross Loan Portfolio. Once a loan crosses the threshold, the <em>entire</em> unpaid principal is counted — not just the overdue installment.</li>
                <li>Arrears information shows loans with missed or overdue payments</li>
                <li>This report includes all active loans meeting the specified filter criteria</li>
            </ul>
        </div>
    </body>
    </html>
    `;
}

module.exports = {
    PAR_THRESHOLDS,
    validatePARReportParameters,
    calculatePARForDaysRange,
    generatePARReportV2,
    generateExcelStylePARReport,
    generatePARReportV2Enhanced,
    getDetailedPaymentAnalysis,
    calculateOutstandingBalance,
    calculateArrearsDetails,
    calculatePaymentTotals,
    getLastPaymentDate,
    determineRiskClassification,
    getFilterDisplayNames,
    generateEnhancedPortfolioReport,
    getOutstandingPrincipal,
    getAllPaymentsForLoan,
    calculateMaturityDate
};