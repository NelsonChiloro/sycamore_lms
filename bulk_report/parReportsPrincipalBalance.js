const moment = require('moment');
const fs = require('fs');
const path = require('path');
const {
    sqlBranchJoin,
    appendBranchFilter,
    findBranch,
    determineRBMClassification,
    getOfficerIdsUnderSupervisor,
    sqlRelationshipSupervisorNameExpr,
    sqlUnpaidPrincipalExpr
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
 * Get principal balance for a loan (NOT PAID principal amount)
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<number>} Principal balance
 */
async function getPrincipalBalance(loanId, db) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                COALESCE(SUM(CASE WHEN status = 'NOT PAID' THEN principal ELSE 0 END), 0) as principal_balance
            FROM payement_schedules
            WHERE loan_id = ?
        `;

        db.query(query, [loanId], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            resolve(parseFloat(result.principal_balance) || 0);
        });
    });
}

/**
 * Get total payment amount in arrears for a loan (NOT PAID payment schedules.amount)
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<number>} Total payment amount in arrears
 */
async function getPaymentAmountInArrears(loanId, db, asOfDate = moment().format('YYYY-MM-DD')) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                COALESCE(SUM(amount), 0) as total_arrears
            FROM payement_schedules
            WHERE loan_id = ? 
            AND status = 'NOT PAID' 
            AND payment_schedule < DATE(?)
        `;

        db.query(query, [loanId, asOfDate], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            resolve(parseFloat(result.total_arrears) || 0);
        });
    });
}

/**
 * Get oldest overdue payment schedule for a loan
 * @param {number} loanId - Loan ID
 * @param {Object} db - Database connection
 * @returns {Promise<Object>} Oldest overdue payment info
 */
async function getOldestOverduePayment(loanId, db, asOfDate = moment().format('YYYY-MM-DD')) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                payment_schedule,
                DATEDIFF(DATE(?), payment_schedule) as days_overdue
            FROM payement_schedules
            WHERE loan_id = ? 
            AND status = 'NOT PAID' 
            AND payment_schedule < DATE(?)
            ORDER BY payment_schedule ASC
            LIMIT 1
        `;

        db.query(query, [asOfDate, loanId, asOfDate], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            if (results && results.length > 0) {
                resolve({
                    payment_schedule: results[0].payment_schedule,
                    days_overdue: results[0].days_overdue
                });
            } else {
                resolve(null);
            }
        });
    });
}

/**
 * Get total portfolio principal (NOT PAID)
 * @param {Object} db - Database connection
 * @returns {Promise<number>} Total portfolio principal
 */
async function getTotalPortfolioPrincipal(db) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT
                COALESCE(SUM(${sqlUnpaidPrincipalExpr('ps')}), 0) as total_principal
            FROM payement_schedules ps
            INNER JOIN loan l ON ps.loan_id = l.loan_id
            WHERE l.loan_status = 'ACTIVE'
        `;

        db.query(query, [], (err, results) => {
            if (err) {
                reject(err);
                return;
            }

            const result = results[0] || {};
            resolve(parseFloat(result.total_principal) || 0);
        });
    });
}

/**
 * Generate Principal Balance PAR Report
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
async function generatePrincipalBalancePARReport(reportId, officer, product, branch, dateFrom, dateTo, supervisor, db, reportTrackers) {
    console.log('====== PRINCIPAL BALANCE PAR REPORT GENERATION STARTED ======');
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

        // Get current date for report
        const currentDate = moment().format('YYYY-MM-DD');
        const reportAsOfDate = dateTo || currentDate;

        console.log('[3/6] Calculating total portfolio principal...');
        
        // Gross Loan Portfolio = ALL unpaid principal for active loans (no date cutoff)
        const totalPortfolioPrincipal = await getTotalPortfolioPrincipal(db);
        console.log(`      Total portfolio principal: K${new Intl.NumberFormat('en-US').format(totalPortfolioPrincipal.toFixed(2))}`);

        reportTrackers[reportId].percentage = 20;

        console.log('[4/6] Fetching loan data for Principal Balance PAR analysis...');
        
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

        return new Promise((resolve, reject) => {
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
                    CASE
                        WHEN l.customer_type = 'group' THEN g.group_name
                        ELSE 'N/A'
                    END as customer_group_name,
                    e.Firstname as officer_first_name,
                    e.Lastname as officer_last_name,
                    ${sqlRelationshipSupervisorNameExpr('rel_sup')} as relationship_supervisor,
                    b.BranchName as branch_name,
                    lp.product_name,
                    COALESCE(ps_metrics.principal_balance, 0) as principal_balance,
                    COALESCE(ps_metrics.total_arrears, 0) as total_arrears,
                    COALESCE(ps_metrics.oldest_overdue_days, 0) as oldest_overdue_days
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
                        COALESCE(SUM(${sqlUnpaidPrincipalExpr('ps')}), 0) as principal_balance,
                        COALESCE(SUM(CASE
                            WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < DATE('${reportAsOfDate}')
                            THEN ps.amount - COALESCE(ps.paid_amount, 0)
                            ELSE 0
                        END), 0) as total_arrears,
                        COALESCE(SUM(CASE
                            WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < DATE('${reportAsOfDate}') THEN ps.principal
                            ELSE 0
                        END), 0) as arrears_principal,
                        COALESCE(MIN(CASE
                            WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < DATE('${reportAsOfDate}')
                            THEN DATEDIFF(DATE('${reportAsOfDate}'), ps.payment_schedule)
                            ELSE NULL
                        END), 0) as oldest_overdue_days
                    FROM payement_schedules ps
                    GROUP BY ps.loan_id
                ) ps_metrics ON ps_metrics.loan_id = l.loan_id
                WHERE ${whereClause}
            `;

            db.query(loanQuery, queryParams, async (err, loans) => {
                if (err) {
                    reject(err);
                    return;
                }

                console.log(`      Found ${loans.length} active loans to analyze`);
                reportTrackers[reportId].percentage = 40;

                console.log('[5/6] Processing loan data for Principal Balance PAR calculations...');
                const processedLoans = [];
                let processedCount = 0;

                try {
                    // Process each loan for Principal Balance PAR calculations
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

                        const principalBalance = parseFloat(loan.principal_balance) || 0;
                        const paymentAmountInArrears = parseFloat(loan.total_arrears) || 0;
                        const arrearsPrincipal = parseFloat(loan.arrears_principal) || 0;
                        const oldestOverdueDays = parseInt(loan.oldest_overdue_days, 10) || 0;

                        // Initialize aging buckets
                        let aged_0_7_days = 0;
                        let aged_8_30_days = 0;
                        let aged_31_60_days = 0;
                        let aged_61_90_days = 0;
                        let aged_91_120_days = 0;
                        let aged_121_180_days = 0;
                        let aged_181_366_days = 0;
                        let aged_367_plus_days = 0;

                        // Place the principal balance in the appropriate aging bucket based on oldest overdue payment
                        if (oldestOverdueDays > 0 && principalBalance > 0) {
                            const daysOverdue = oldestOverdueDays;
                            
                            if (daysOverdue >= 0 && daysOverdue <= 7) {
                                aged_0_7_days = principalBalance;
                            } else if (daysOverdue >= 8 && daysOverdue <= 30) {
                                aged_8_30_days = principalBalance;
                            } else if (daysOverdue >= 31 && daysOverdue <= 60) {
                                aged_31_60_days = principalBalance;
                            } else if (daysOverdue >= 61 && daysOverdue <= 90) {
                                aged_61_90_days = principalBalance;
                            } else if (daysOverdue >= 91 && daysOverdue <= 120) {
                                aged_91_120_days = principalBalance;
                            } else if (daysOverdue >= 121 && daysOverdue <= 180) {
                                aged_121_180_days = principalBalance;
                            } else if (daysOverdue >= 181 && daysOverdue <= 366) {
                                aged_181_366_days = principalBalance;
                            } else if (daysOverdue >= 367) {
                                aged_367_plus_days = principalBalance;
                            }
                        }

                        // Calculate >=1 day total (sum of all aging buckets)
                        const aged_1_plus_days = aged_0_7_days + aged_8_30_days + aged_31_60_days + aged_61_90_days + aged_91_120_days + aged_121_180_days + aged_181_366_days + aged_367_plus_days;
                        
                        const total_arrears = paymentAmountInArrears;

                        processedLoans.push({
                            loanCustomer: loan.loan_customer,
                            customerName,
                            customerGroupName: loan.customer_group_name || 'N/A',
                            loanNumber: loan.loan_number || 'N/A',
                            productName: loan.product_name || 'N/A',
                            officerName,
                            relationshipSupervisor: loan.relationship_supervisor || 'N/A',
                            branchName,
                            loanDate: loan.loan_date,
                            loanPeriod: loan.loan_period,
                            loanInterest: loan.loan_interest,
                            principalBalance: principalBalance,
                            arrearsPrincipal: arrearsPrincipal,
                            oldestOverdueDays: oldestOverdueDays,
                            rbm_classification: determineRBMClassification(oldestOverdueDays),
                            total_arrears,
                            aged_1_plus_days,
                            aged_0_7_days,
                            aged_8_30_days,
                            aged_31_60_days,
                            aged_61_90_days,
                            aged_91_120_days,
                            aged_121_180_days,
                            aged_181_366_days,
                            aged_367_plus_days
                        });

                        processedCount++;
                        if (processedCount % 10 === 0) {
                            console.log(`      Processed ${processedCount}/${loans.length} loans`);
                        }
                        if (loans.length > 0 && (processedCount % 50 === 0 || processedCount === loans.length)) {
                            const p = 40 + Math.floor((processedCount / loans.length) * 40);
                            reportTrackers[reportId].percentage = Math.min(80, p);
                        }
                    }

                    reportTrackers[reportId].percentage = 80;

                    console.log('[6/6] Generating Principal Balance PAR HTML report and saving to file...');

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

                    // Generate HTML report
                    const reportHtml = generatePrincipalBalancePARHTML(
                        currentDate,
                        processedLoans,
                        totalPortfolioPrincipal,
                        selectedBranchName,
                        dateFrom,
                        dateTo
                    );

                    // Write to the file path stored in the tracker
                    fs.writeFileSync(reportTrackers[reportId].filePath, reportHtml);

                    reportTrackers[reportId].percentage = 100;

                    console.log(`\n✅ Principal Balance PAR Report generated successfully: ${reportTrackers[reportId].filePath}`);
                    console.log('====== PRINCIPAL BALANCE PAR REPORT GENERATION COMPLETED ======');

                    // Resolve with success
                    resolve(true);
                } catch (error) {
                    reject(error);
                }
            });
        });
    } catch (error) {
        console.error('\n❌ Error generating Principal Balance PAR report:', error);
        console.log('====== PRINCIPAL BALANCE PAR REPORT GENERATION FAILED ======');
        throw error;
    }
}

/**
 * Function to generate Principal Balance PAR HTML report
 * @param {string} currentDate - Current date
 * @param {Array} loans - Array of processed loans
 * @param {number} totalPortfolioPrincipal - Total portfolio principal amount
 * @param {string} branchName - Branch name
 * @param {string} dateFrom - Start date
 * @param {string} dateTo - End date
 * @returns {string} HTML report content
 */
function generatePrincipalBalancePARHTML(currentDate, loans, totalPortfolioPrincipal, branchName, dateFrom, dateTo) {
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

    // Calculate totals for each aging bucket
    const total_principal_balance = loans.reduce((sum, loan) => sum + loan.principalBalance, 0);
    const total_arrears = loans.reduce((sum, loan) => sum + loan.total_arrears, 0);
    const total_arrears_principal = loans.reduce((sum, loan) => sum + (loan.arrearsPrincipal || 0), 0);
    const total_1_plus_days = loans.reduce((sum, loan) => sum + loan.aged_1_plus_days, 0);
    const total_0_7_days = loans.reduce((sum, loan) => sum + loan.aged_0_7_days, 0);
    const total_8_30_days = loans.reduce((sum, loan) => sum + loan.aged_8_30_days, 0);
    const total_31_60_days = loans.reduce((sum, loan) => sum + loan.aged_31_60_days, 0);
    const total_61_90_days = loans.reduce((sum, loan) => sum + loan.aged_61_90_days, 0);
    const total_91_120_days = loans.reduce((sum, loan) => sum + loan.aged_91_120_days, 0);
    const total_121_180_days = loans.reduce((sum, loan) => sum + loan.aged_121_180_days, 0);
    const total_181_366_days = loans.reduce((sum, loan) => sum + loan.aged_181_366_days, 0);
    const total_367_plus_days = loans.reduce((sum, loan) => sum + loan.aged_367_plus_days, 0);

    // Gross Loan Portfolio = total unpaid principal from filtered loans (same source as aging buckets)
    const glp = total_principal_balance;

    // Customer / loan counts
    const totalLoans        = loans.length;
    const loansInArrears    = loans.filter(l => l.oldestOverdueDays > 0).length;
    const loansCurrent      = totalLoans - loansInArrears;
    const distinctCustomers = new Set(loans.map(l => l.loanCustomer)).size;
    const customersInArrears = new Set(loans.filter(l => l.oldestOverdueDays > 0).map(l => l.loanCustomer)).size;
    const customersCurrent   = new Set(loans.filter(l => l.oldestOverdueDays === 0).map(l => l.loanCustomer)).size;

    // Cumulative PAR: PAR(N) = full principal of loans with oldest_overdue > N days / GLP
    const par1_principal   = total_1_plus_days;
    const par30_principal  = total_31_60_days + total_61_90_days + total_91_120_days + total_121_180_days + total_181_366_days + total_367_plus_days;
    const par60_principal  = total_61_90_days + total_91_120_days + total_121_180_days + total_181_366_days + total_367_plus_days;
    const par90_principal  = total_91_120_days + total_121_180_days + total_181_366_days + total_367_plus_days;
    const par180_principal = total_181_366_days + total_367_plus_days;

    const par1_rate   = glp > 0 ? (par1_principal   / glp) * 100 : 0;
    const par30_rate  = glp > 0 ? (par30_principal  / glp) * 100 : 0;
    const par60_rate  = glp > 0 ? (par60_principal  / glp) * 100 : 0;
    const par90_rate  = glp > 0 ? (par90_principal  / glp) * 100 : 0;
    const par180_rate = glp > 0 ? (par180_principal / glp) * 100 : 0;

    // Bucket percentages (share of each age range in the portfolio)
    const par_1_plus_percent  = glp > 0 ? (total_1_plus_days  / glp) * 100 : 0;
    const par_0_7_percent     = glp > 0 ? (total_0_7_days     / glp) * 100 : 0;
    const par_8_30_percent    = glp > 0 ? (total_8_30_days    / glp) * 100 : 0;
    const par_31_60_percent   = glp > 0 ? (total_31_60_days   / glp) * 100 : 0;
    const par_61_90_percent   = glp > 0 ? (total_61_90_days   / glp) * 100 : 0;
    const par_91_120_percent  = glp > 0 ? (total_91_120_days  / glp) * 100 : 0;
    const par_121_180_percent = glp > 0 ? (total_121_180_days / glp) * 100 : 0;
    const par_181_366_percent = glp > 0 ? (total_181_366_days / glp) * 100 : 0;
    const par_367_plus_percent= glp > 0 ? (total_367_plus_days/ glp) * 100 : 0;

    // Generate loan rows
    const loanRows = loans.map(loan => {
        return `
            <tr>
                <td>${loan.customerName}</td>
                <td>${loan.customerGroupName}</td>
                <td>${loan.loanNumber}</td>
                <td>${loan.productName}</td>
                <td>${loan.officerName}</td>
                <td>${loan.relationshipSupervisor || 'N/A'}</td>
                <td>${loan.branchName || 'N/A'}</td>
                <td>${loan.rbm_classification || determineRBMClassification(loan.oldestOverdueDays || 0)}</td>
                <td style="text-align: right;">${formatCurrency(loan.principalBalance)}</td>
                <td style="text-align: right;">${formatCurrency(loan.total_arrears)}</td>
                <td style="text-align: right;">${formatCurrency(loan.aged_1_plus_days)}</td>
                <td style="text-align: right;">${formatCurrency(loan.aged_0_7_days)}</td>
                <td style="text-align: right;">${formatCurrency(loan.aged_8_30_days)}</td>
                <td style="text-align: right;">${formatCurrency(loan.aged_31_60_days)}</td>
                <td style="text-align: right;">${formatCurrency(loan.aged_61_90_days)}</td>
                <td style="text-align: right;">${formatCurrency(loan.aged_91_120_days)}</td>
                <td style="text-align: right;">${formatCurrency(loan.aged_121_180_days)}</td>
                <td style="text-align: right;">${formatCurrency(loan.aged_181_366_days)}</td>
                <td style="text-align: right;">${formatCurrency(loan.aged_367_plus_days)}</td>
            </tr>
        `;
    }).join('');

    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Principal Balance PAR Report - ${moment(currentDate).format('MM/DD/YYYY')}</title>
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
                    const fileName = 'Principal_Balance_PAR_Report.' + type;
                    const table = document.getElementById("results-table");
                    const wb = XLSX.utils.table_to_book(table);
                    XLSX.writeFile(wb, fileName);
                }
            </script>
        </head>
        <body>
            <h2 style="color:#153505;margin-bottom:4px;">Principal Balance PAR Report</h2>
            <p style="margin:0 0 10px;color:#555;font-size:11px;">
                Sycamore Limited (MALAWI) &mdash; As Of: ${moment(currentDate).format('MM/DD/YYYY')}
                &nbsp;|&nbsp; Branch: ${branchName}
                ${dateRangeDisplay ? `&nbsp;|&nbsp; ${dateRangeDisplay}` : ''}
            </p>

            <!-- STATS SUMMARY -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:16px;">
                <div style="background:#f5f5f5;border:1px solid #ddd;border-radius:4px;padding:10px;text-align:center;">
                    <div style="font-size:11px;color:#555;">All Loan Accounts</div>
                    <div style="font-size:18px;font-weight:bold;color:#333;">${totalLoans.toLocaleString()}</div>
                    <div style="font-size:10px;color:#888;">Total active loans (a customer may have more than one)</div>
                </div>
                <div style="background:#e8f5e8;border:1px solid #c8e6c9;border-radius:4px;padding:10px;text-align:center;">
                    <div style="font-size:11px;color:#555;">Distinct Customers</div>
                    <div style="font-size:18px;font-weight:bold;color:#153505;">${distinctCustomers.toLocaleString()}</div>
                    <div style="font-size:10px;color:#888;">Unique individual borrowers</div>
                </div>
                <div style="background:#fff3e0;border:1px solid #ffe0b2;border-radius:4px;padding:10px;text-align:center;">
                    <div style="font-size:11px;color:#555;">Customers in Arrears</div>
                    <div style="font-size:18px;font-weight:bold;color:#e67e22;">${customersInArrears.toLocaleString()}</div>
                    <div style="font-size:10px;color:#888;">${distinctCustomers > 0 ? ((customersInArrears/distinctCustomers)*100).toFixed(1) : '0.0'}% — have at least 1 overdue payment</div>
                </div>
                <div style="background:#e8f5e8;border:1px solid #c8e6c9;border-radius:4px;padding:10px;text-align:center;">
                    <div style="font-size:11px;color:#555;">Customers Current</div>
                    <div style="font-size:18px;font-weight:bold;color:#27ae60;">${customersCurrent.toLocaleString()}</div>
                    <div style="font-size:10px;color:#888;">${distinctCustomers > 0 ? ((customersCurrent/distinctCustomers)*100).toFixed(1) : '0.0'}% — all payments up to date, no overdue schedules</div>
                </div>
                <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:4px;padding:10px;text-align:center;">
                    <div style="font-size:11px;color:#555;">Total Amount in Arrears</div>
                    <div style="font-size:15px;font-weight:bold;color:#b7770d;">K${formatCurrency(total_arrears)}</div>
                    <div style="font-size:10px;color:#888;">Overdue installments (principal + charges)</div>
                </div>
                <div style="background:#fce4ec;border:1px solid #f8bbd0;border-radius:4px;padding:10px;text-align:center;">
                    <div style="font-size:11px;color:#555;">Total Principal at Risk</div>
                    <div style="font-size:15px;font-weight:bold;color:#c0392b;">K${formatCurrency(total_1_plus_days)}</div>
                    <div style="font-size:10px;color:#888;">Full loan principal of all loans with any arrears — ${glp > 0 ? ((total_1_plus_days/glp)*100).toFixed(2) : '0.00'}% of GLP</div>
                </div>
                <div style="background:#e8f5e8;border:1px solid #c8e6c9;border-radius:4px;padding:10px;text-align:center;">
                    <div style="font-size:11px;color:#555;">Gross Loan Portfolio</div>
                    <div style="font-size:15px;font-weight:bold;color:#153505;">K${formatCurrency(glp)}</div>
                    <div style="font-size:10px;color:#888;">Total unpaid principal — all active loans</div>
                </div>
            </div>

            <!-- PAR SUMMARY at the top -->
            <table style="border-collapse:collapse;font-size:12px;margin-bottom:16px;width:auto;">
                <tr>
                    <th colspan="5" style="background:#153505;color:white;padding:6px 12px;border:1px solid #999;text-align:left;">
                        Portfolio at Risk (PAR) &mdash; PAR(N) = Full principal of loans with arrears &gt; N days &divide; Gross Loan Portfolio
                    </th>
                </tr>
                <tr style="background:#f0f0f0;font-weight:bold;">
                    <td style="padding:5px 10px;border:1px solid #999;">Metric</td>
                    <td style="padding:5px 10px;border:1px solid #999;">Threshold</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:right;">Loans at Risk</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:right;">Principal at Risk (MWK)</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:right;">PAR %</td>
                </tr>
                ${[
                    { label:'PAR1',   rate: par1_rate,   principal: par1_principal,   count: loans.filter(l=>l.oldestOverdueDays>0).length   },
                    { label:'PAR30',  rate: par30_rate,  principal: par30_principal,  count: loans.filter(l=>l.oldestOverdueDays>30).length  },
                    { label:'PAR60',  rate: par60_rate,  principal: par60_principal,  count: loans.filter(l=>l.oldestOverdueDays>60).length  },
                    { label:'PAR90',  rate: par90_rate,  principal: par90_principal,  count: loans.filter(l=>l.oldestOverdueDays>90).length  },
                    { label:'PAR180', rate: par180_rate, principal: par180_principal, count: loans.filter(l=>l.oldestOverdueDays>180).length },
                ].map((p,i) => `
                <tr style="background:${i%2===0?'#f9f9f9':'white'}">
                    <td style="padding:5px 10px;border:1px solid #999;font-weight:bold;color:#153505;">${p.label}</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:center;">&gt; ${[0,30,60,90,180][i]} days</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:right;">${p.count.toLocaleString()}</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:right;">${formatCurrency(p.principal)}</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:right;font-weight:bold;color:${p.rate>10?'#c0392b':p.rate>5?'#e67e22':'#27ae60'};">${p.rate.toFixed(2)}%</td>
                </tr>`).join('')}
                <tr style="background:#e8f5e8;font-weight:bold;">
                    <td style="padding:5px 10px;border:1px solid #999;" colspan="2">Gross Loan Portfolio (denominator)</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:right;">${loans.length.toLocaleString()} loans</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:right;">${formatCurrency(glp)}</td>
                    <td style="padding:5px 10px;border:1px solid #999;text-align:right;">100.00%</td>
                </tr>
            </table>

            <div class="action">
                <span>Export table to:</span>
                <button onclick="exportData('xlsx')">Excel (xlsx)</button>
                <button onclick="exportData('xls')">Excel (xls)</button>
                <button onclick="exportData('csv')">CSV</button>
            </div>

            <table id="results-table">
                <tr class="header-row">
                    <td>Sycamore Limited (MALAWI)</td>
                    <td colspan="5">Principal Balance PAR Report</td>
                    <td>As Of:</td>
                    <td colspan="2">${moment(currentDate).format('MM/DD/YYYY')}</td>
                    <td colspan="8"></td>
                </tr>
                <tr class="header-row">
                    <td>Branch:</td>
                    <td colspan="5">${branchName}</td>
                    <td colspan="11" class="date-filter">${dateRangeDisplay}</td>
                </tr>
                <tr class="header-row">
                    <td>Customer</td>
                    <td>Customer Group</td>
                    <td>Loan #</td>
                    <td>Product</td>
                    <td>Loan Officer</td>
                    <td>Relationship Supervisor</td>
                    <td>Branch</td>
                    <td>RBM Classification</td>
                    <td>Unpaid Principal (MWK)</td>
                    <td>Total Amount in Arrears (MWK)</td>
                    <td>Total Principal at Risk (MWK)**</td>
                    <td>0-7 days</td>
                    <td>8-30 days</td>
                    <td>31-60 days</td>
                    <td>61-90 days</td>
                    <td>91-120 days</td>
                    <td>121-180 days</td>
                    <td>181-366 days</td>
                    <td>367+ days</td>
                </tr>
                ${loanRows}
                <tr class="total-row">
                    <td colspan="8">TOTAL</td>
                    <td style="text-align:right;">${formatCurrency(total_principal_balance)}</td>
                    <td style="text-align:right;">${formatCurrency(total_arrears)}</td>
                    <td style="text-align:right;background:#fff3cd;font-weight:bold;">${formatCurrency(total_1_plus_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_0_7_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_8_30_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_31_60_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_61_90_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_91_120_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_121_180_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_181_366_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_367_plus_days)}</td>
                </tr>
                <tr style="background:#fff3cd;">
                    <td colspan="8" style="font-style:italic;font-size:10px;">
                        ** Total Principal at Risk = sum of all age buckets (0-7 + 8-30 + ... + 367+)
                    </td>
                    <td></td>
                    <td></td>
                    <td style="text-align:right;font-weight:bold;">${formatCurrency(total_1_plus_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_0_7_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_8_30_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_31_60_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_61_90_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_91_120_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_121_180_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_181_366_days)}</td>
                    <td style="text-align:right;">${formatCurrency(total_367_plus_days)}</td>
                </tr>
                <tr><td colspan="18" style="height: 10px;"></td></tr>
                <tr class="total-row">
                    <td>GROSS LOAN PORTFOLIO</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: right;">MK${formatCurrency(glp)}</td>
                    <td colspan="12"></td>
                </tr>
                <tr class="total-row">
                    <td>BUCKET % OF PORTFOLIO</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: right;">${formatPercentage(100)}</td>
                    <td style="text-align: right;">${formatPercentage(par_1_plus_percent)}</td>
                    <td style="text-align: right;">${formatPercentage(par_0_7_percent)}</td>
                    <td style="text-align: right;">${formatPercentage(par_8_30_percent)}</td>
                    <td style="text-align: right;">${formatPercentage(par_31_60_percent)}</td>
                    <td style="text-align: right;">${formatPercentage(par_61_90_percent)}</td>
                    <td style="text-align: right;">${formatPercentage(par_91_120_percent)}</td>
                    <td style="text-align: right;">${formatPercentage(par_121_180_percent)}</td>
                    <td style="text-align: right;">${formatPercentage(par_181_366_percent)}</td>
                    <td style="text-align: right;">${formatPercentage(par_367_plus_percent)}</td>
                </tr>
                <tr class="total-row">
                    <td>PAR1  (&gt;0 days)</td><td></td><td></td>
                    <td style="text-align:right;">${formatCurrency(par1_principal)}</td>
                    <td style="text-align:right;font-weight:bold;color:${par1_rate>10?'#c0392b':par1_rate>5?'#e67e22':'#27ae60'};">${par1_rate.toFixed(2)}%</td>
                    <td colspan="12"></td>
                </tr>
                <tr class="total-row">
                    <td>PAR30 (&gt;30 days)</td><td></td><td></td>
                    <td style="text-align:right;">${formatCurrency(par30_principal)}</td>
                    <td style="text-align:right;font-weight:bold;color:${par30_rate>10?'#c0392b':par30_rate>5?'#e67e22':'#27ae60'};">${par30_rate.toFixed(2)}%</td>
                    <td colspan="12"></td>
                </tr>
                <tr class="total-row">
                    <td>PAR60 (&gt;60 days)</td><td></td><td></td>
                    <td style="text-align:right;">${formatCurrency(par60_principal)}</td>
                    <td style="text-align:right;font-weight:bold;color:${par60_rate>10?'#c0392b':par60_rate>5?'#e67e22':'#27ae60'};">${par60_rate.toFixed(2)}%</td>
                    <td colspan="12"></td>
                </tr>
                <tr class="total-row">
                    <td>PAR90 (&gt;90 days)</td><td></td><td></td>
                    <td style="text-align:right;">${formatCurrency(par90_principal)}</td>
                    <td style="text-align:right;font-weight:bold;color:${par90_rate>10?'#c0392b':par90_rate>5?'#e67e22':'#27ae60'};">${par90_rate.toFixed(2)}%</td>
                    <td colspan="12"></td>
                </tr>
                <tr class="total-row">
                    <td>PAR180 (&gt;180 days)</td><td></td><td></td>
                    <td style="text-align:right;">${formatCurrency(par180_principal)}</td>
                    <td style="text-align:right;font-weight:bold;color:${par180_rate>10?'#c0392b':par180_rate>5?'#e67e22':'#27ae60'};">${par180_rate.toFixed(2)}%</td>
                    <td colspan="12"></td>
                </tr>
                <tr><td colspan="19" style="height:10px;"></td></tr>
                <tr style="background:#f5f5f5;font-size:10px;font-style:italic;">
                    <td colspan="19" style="padding:4px 6px;">
                        * Total Amount in Arrears = unpaid portion of all past-due installments (principal + charges). &nbsp;
                        ** Total Principal at Risk = the loan's entire unpaid principal once any payment is overdue = sum of all age bucket columns.
                    </td>
                </tr>
                <tr><td colspan="19" style="height:6px;"></td></tr>
                <tr class="total-row">
                    <td>AGING SUMMARY</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>% of GLP</td>
                    <td>Total Amount in Arrears</td>
                    <td>Total Principal at Risk</td>
                    <td>0-7 days</td>
                    <td>8-30 days</td>
                    <td>31-60 days</td>
                    <td>61-90 days</td>
                    <td>91-120 days</td>
                    <td>121-180 days</td>
                    <td>181-366 days</td>
                    <td>367+ days</td>
                </tr>
            </table>
        </body>
        </html>
    `;
}

module.exports = {
    PAR_THRESHOLDS,
    validatePARReportParameters,
    generatePrincipalBalancePARReport,
    generatePrincipalBalancePARHTML,
    getPrincipalBalance,
    getPaymentAmountInArrears,
    getOldestOverduePayment,
    getTotalPortfolioPrincipal
};