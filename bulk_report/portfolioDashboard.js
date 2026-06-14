'use strict';
/**
 * Portfolio Dashboard — hourly data computation module.
 * Queries all active loans, aggregates KPIs, branch/product/officer breakdowns,
 * 18-month disbursement trend, aging buckets, and persists everything to the
 * pd_* snapshot tables for instant frontend retrieval.
 */

const moment = require('moment');
const {
    query,
    sqlUnpaidPrincipalExpr,
    sqlUnpaidChargesExpr,
    sqlRelationshipSupervisorNameExpr,
    determineRBMClassification,
} = require('./databaseHelpers');

// ─── helpers ───────────────────────────────────────────────────────────────

const toF = (v) => parseFloat(v || 0);
const toI = (v) => parseInt(v || 0, 10);
const clamp = (v, lo, hi) => Math.min(Math.max(v, lo), hi);
const km    = (v) => toF(v) / 1e6;  // value → KM (millions)

function parClassification(daysInArrears) {
    const days = toI(daysInArrears);
    if (days > 180) return 'PAR180';
    if (days > 90) return 'PAR90';
    if (days > 60) return 'PAR60';
    if (days > 30) return 'PAR30';
    if (days > 0) return 'PAR1';
    return 'Current';
}

const PD_LOANS_EXTRA_COLUMNS = [
    ['customer_group', 'VARCHAR(500) NULL'],
    ['loan_date', 'DATE NULL'],
    ['loan_principal_amount', 'DECIMAL(20,2) NOT NULL DEFAULT 0'],
    ['total_loan_amount', 'DECIMAL(20,2) NOT NULL DEFAULT 0'],
    ['accrued_charges', 'DECIMAL(20,2) NOT NULL DEFAULT 0'],
    ['payments_in_arrears', 'INT NOT NULL DEFAULT 0'],
    ['actual_payments', 'DECIMAL(20,2) NOT NULL DEFAULT 0'],
    ['par_classification', 'VARCHAR(20) NULL'],
    ['rbm_classification', 'VARCHAR(50) NULL'],
    ['last_payment_date', 'DATE NULL'],
    ['collateral_value', 'DECIMAL(20,2) NOT NULL DEFAULT 0'],
    ['maturity_date', 'DATE NULL'],
    ['relationship_supervisor', 'VARCHAR(200) NULL'],
    ['loan_added_date', 'DATE NULL'],
];

async function ensurePdLoansSchema() {
    for (const [col, def] of PD_LOANS_EXTRA_COLUMNS) {
        try {
            // eslint-disable-next-line no-await-in-loop
            await query(`ALTER TABLE pd_loans ADD COLUMN ${col} ${def}`);
        } catch (err) {
            if (!/Duplicate column name/i.test(err.message || '')) {
                throw err;
            }
        }
    }
}

// PAR(N) = sum(gross loan portfolio of loans with days_in_arrears > N) / total gross loan portfolio
function parRateByPrincipal(loans, daysThreshold) {
    const grossTotal = loans.reduce((s, l) => s + l._principal, 0);
    if (grossTotal <= 0) {
        return 0;
    }
    const atRiskPrincipal = loans
        .filter(l => l._daysArrears > daysThreshold)
        .reduce((s, l) => s + l._principal, 0);
    return clamp((atRiskPrincipal / grossTotal) * 100, 0, 100);
}

// ─── main export ───────────────────────────────────────────────────────────

/**
 * Runs one complete portfolio dashboard snapshot.
 * Called on startup and then every hour.
 */
async function computeAndStorePortfolioDashboard() {
    const startedAt = Date.now();
    const asOfDate  = moment().format('YYYY-MM-DD');
    console.log(`[Portfolio Dashboard] Starting snapshot for ${asOfDate}...`);
    await ensurePdLoansSchema();

    // ── 1. Fetch all active loans ─────────────────────────────────────────
    const loanSQL = `
        SELECT
            l.loan_id,
            l.loan_number,
            l.loan_principal,
            l.loan_amount_total,
            l.loan_status,
            l.loan_date,
            l.loan_added_date,
            l.disbursed_date,
            l.loan_interest,
            l.loan_period,
            l.period_type,
            l.loan_amount_term AS installment_amount,
            lp.product_name,
            COALESCE(b.BranchName, 'Unknown') AS branch_name,
            CONCAT(COALESCE(e.Firstname,''), ' ', COALESCE(e.Lastname,'')) AS officer_name,
            ${sqlRelationshipSupervisorNameExpr('rel_sup')} AS relationship_supervisor,
            CASE
                WHEN l.customer_type = 'group'
                    THEN CONCAT(COALESCE(g.group_name,''), ' (Group)')
                ELSE CONCAT(COALESCE(ic.Firstname,''), ' ', COALESCE(ic.Lastname,''))
            END AS customer_name,
            CASE
                WHEN l.customer_type = 'group' THEN COALESCE(g.group_name, 'N/A')
                ELSE 'N/A'
            END AS customer_group
        FROM loan l
        LEFT JOIN loan_products lp ON lp.loan_product_id = l.loan_product
        LEFT JOIN branches b ON b.id = l.branch
        LEFT JOIN employees e ON e.id = l.loan_added_by
        LEFT JOIN employees rel_sup ON rel_sup.id = e.Supervisor
        LEFT JOIN individual_customers ic
               ON ic.id = l.loan_customer AND l.customer_type = 'individual'
        LEFT JOIN \`groups\` g
               ON g.group_id = l.loan_customer AND l.customer_type = 'group'
        WHERE l.loan_status = 'ACTIVE'
    `;

    const loans = await query(loanSQL);
    if (!loans.length) {
        console.log('[Portfolio Dashboard] No active loans found; skipping snapshot.');
        return null;
    }

    const loanIds = loans.map(l => l.loan_id);
    console.log(`[Portfolio Dashboard] Found ${loanIds.length} active loans.`);

    // ── 2. Batch: payment schedule metrics per loan (portfolio analysis formulae) ──
    const unpaidPrincipal = sqlUnpaidPrincipalExpr('ps');
    const unpaidCharges = sqlUnpaidChargesExpr('ps');
    const psSQL = `
        SELECT
            loan_id,
            SUM(${unpaidPrincipal}) AS gross_loan_portfolio,
            SUM(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID')
                AND ps.payment_schedule <= LAST_DAY(DATE(?))
                THEN ${unpaidCharges} ELSE 0 END) AS accrued_charges,
            SUM(CASE WHEN ps.payment_schedule <= DATE(?)
                THEN ps.amount ELSE 0 END) AS total_expected,
            SUM(CASE WHEN ps.payment_schedule <= DATE(?)
                THEN COALESCE(ps.paid_amount, 0) ELSE 0 END) AS total_paid,
            SUM(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID')
                AND ps.payment_schedule < DATE(?)
                THEN COALESCE(ps.amount, 0) - COALESCE(ps.paid_amount, 0) ELSE 0 END) AS arrears,
            MAX(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID')
                AND ps.payment_schedule < DATE(?)
                AND COALESCE(ps.amount, 0) > COALESCE(ps.paid_amount, 0)
                THEN DATEDIFF(DATE(?), ps.payment_schedule) ELSE 0 END) AS days_arrears,
            COUNT(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID')
                AND ps.payment_schedule < DATE(?)
                AND COALESCE(ps.amount, 0) > COALESCE(ps.paid_amount, 0) THEN 1 END) AS payments_in_arrears,
            MAX(ps.payment_schedule) AS maturity_date
        FROM payement_schedules ps
        WHERE loan_id IN (${loanIds.join(',')})
        GROUP BY loan_id
    `;

    const psRows = await query(psSQL, [asOfDate, asOfDate, asOfDate, asOfDate, asOfDate, asOfDate, asOfDate]);
    const psMap  = {};
    psRows.forEach(r => { psMap[r.loan_id] = r; });

    const loanNumbers = loans.map(l => l.loan_number).filter(Boolean);
    const lastPayMap = {};
    if (loanNumbers.length) {
        const lastPayRows = await query(`
            SELECT account_number, MAX(server_time) AS last_payment_date
            FROM \`transaction\`
            WHERE credit > 0
              AND account_number IN (${loanNumbers.map(n => `'${String(n).replace(/'/g, "''")}'`).join(',')})
            GROUP BY account_number
        `);
        lastPayRows.forEach(r => { lastPayMap[r.account_number] = r.last_payment_date; });
    }

    // ── 3. Merge and compute per-loan fields ──────────────────────────────
    const processed = [];
    loans.forEach(l => {
        const ps = psMap[l.loan_id] || {};
        const grossLoanPortfolio = toF(ps.gross_loan_portfolio);
        const accruedCharges = toF(ps.accrued_charges);
        const totalExpected  = toF(ps.total_expected);
        const totalPaid      = toF(ps.total_paid);
        const outstanding    = grossLoanPortfolio + accruedCharges;
        const arrears        = toF(ps.arrears);
        const daysArrears    = toI(ps.days_arrears);
        const collRate       = totalExpected > 0
            ? clamp((totalPaid / totalExpected) * 100, 0, 100)
            : 0;

        const parClass = parClassification(daysArrears);
        const rbmClass = determineRBMClassification(daysArrears);
        processed.push({
            // raw loan fields
            loan_id:           l.loan_id,
            customer_name:     (l.customer_name || '').trim() || 'Unknown',
            customer_group:    l.customer_group || 'N/A',
            loan_number:       l.loan_number,
            product_name:      l.product_name  || 'Unknown',
            branch_name:       l.branch_name   || 'Unknown',
            officer_name:      (l.officer_name || '').trim() || 'Unknown',
            relationship_supervisor: (l.relationship_supervisor || '').trim() || 'N/A',
            loan_status:       l.loan_status,
            loan_date:         l.loan_date || null,
            loan_added_date:   l.loan_added_date || null,
            disbursement_date: l.disbursed_date || l.loan_date || null,
            interest_rate:     toF(l.loan_interest),
            period:            `${l.loan_period || ''} ${l.period_type || ''}`.trim(),
            loan_principal_amount: toF(l.loan_principal),
            total_loan_amount: toF(l.loan_amount_total),
            // computed (portfolio analysis aligned)
            _principal:    grossLoanPortfolio,
            _accruedCharges: accruedCharges,
            _outstanding:  outstanding,
            _arrears:      arrears,
            _daysArrears:  daysArrears,
            _collRate:     collRate,
            _totalExpected: totalExpected,
            _actualPayments: totalPaid,
            _installment:  toF(l.installment_amount || ps.installment_amount),
            _paymentsInArrears: toI(ps.payments_in_arrears),
            _parClassification: parClass,
            _rbmClassification: rbmClass,
            _lastPaymentDate: lastPayMap[l.loan_number] || null,
            _maturityDate: ps.maturity_date || null,
            _collateralValue: 0,
        });
    });

    // ── 4. KPI summary ────────────────────────────────────────────────────
    let totalPrincipal = 0, totalAccruedCharges = 0, totalOutstanding = 0, totalArrears = 0, loansInArrears = 0;
    let crSum = 0;
    processed.forEach(l => {
        totalPrincipal       += l._principal;
        totalAccruedCharges  += l._accruedCharges;
        totalOutstanding     += l._outstanding;
        totalArrears         += l._arrears;
        crSum                += l._collRate;
        if (l._daysArrears > 0) loansInArrears++;
    });
    const par30         = parRateByPrincipal(processed, 30);
    const par90         = parRateByPrincipal(processed, 90);
    const collectionRate = processed.length > 0 ? crSum / processed.length : 0;

    // ── 5. Branch aggregates ──────────────────────────────────────────────
    const branchMap = {};
    processed.forEach(l => {
        const bn = l.branch_name;
        if (!branchMap[bn]) branchMap[bn] = { loans: [], outstanding: 0, arrears: 0 };
        branchMap[bn].loans.push(l);
        branchMap[bn].outstanding += l._outstanding;
        branchMap[bn].arrears     += l._arrears;
    });
    const branches = Object.entries(branchMap).map(([name, d]) => ({
        branch_name:     name,
        loans_count:     d.loans.length,
        outstanding:     d.outstanding,
        arrears:         d.arrears,
        par30:           parRateByPrincipal(d.loans, 30),
        par90:           parRateByPrincipal(d.loans, 90),
        collection_rate: d.loans.length > 0
            ? d.loans.reduce((s, l) => s + l._collRate, 0) / d.loans.length
            : 0,
    }));

    // ── 6. Product aggregates ─────────────────────────────────────────────
    const productMap = {};
    processed.forEach(l => {
        const pn = l.product_name;
        if (!productMap[pn]) productMap[pn] = { loans: [], outstanding: 0, arrears: 0 };
        productMap[pn].loans.push(l);
        productMap[pn].outstanding += l._outstanding;
        productMap[pn].arrears     += l._arrears;
    });
    const products = Object.entries(productMap).map(([name, d]) => ({
        product_name:  name,
        loans_count:   d.loans.length,
        outstanding_km: km(d.outstanding),
        arrears_km:    km(d.arrears),
        par_rate:      parRateByPrincipal(d.loans, 30),
    }));

    // ── 7. Officer aggregates ─────────────────────────────────────────────
    const officerMap = {};
    processed.forEach(l => {
        const on = l.officer_name;
        if (!officerMap[on]) officerMap[on] = { loans: [], outstanding: 0, arrears: 0 };
        officerMap[on].loans.push(l);
        officerMap[on].outstanding += l._outstanding;
        officerMap[on].arrears     += l._arrears;
    });
    const officers = Object.entries(officerMap).map(([name, d]) => ({
        officer_name:    name,
        loans_count:     d.loans.length,
        outstanding:     d.outstanding,
        arrears:         d.arrears,
        par_rate:        parRateByPrincipal(d.loans, 30),
        collection_rate: d.loans.length > 0
            ? d.loans.reduce((s, l) => s + l._collRate, 0) / d.loans.length
            : 0,
    })).sort((a, b) => b.outstanding - a.outstanding);

    // ── 8. 18-month disbursement trend ────────────────────────────────────
    const trendSQL = `
        SELECT
            DATE_FORMAT(loan_date, '%Y-%m') AS month_year,
            COUNT(*)                         AS loan_count,
            SUM(loan_principal) / 1000000    AS principal_km
        FROM loan
        WHERE loan_date >= DATE_SUB(CURDATE(), INTERVAL 18 MONTH)
          AND loan_status NOT IN ('REJECTED','DELETED')
        GROUP BY month_year
        ORDER BY month_year
    `;
    const trendRows = await query(trendSQL);

    // ── 9. Aging buckets ──────────────────────────────────────────────────
    const BUCKETS = [
        { key: '1-30d',   lo:   1, hi:  30 },
        { key: '31-60d',  lo:  31, hi:  60 },
        { key: '61-90d',  lo:  61, hi:  90 },
        { key: '91-180d', lo:  91, hi: 180 },
        { key: '>180d',   lo: 181, hi: Infinity },
    ];
    const agingRows = BUCKETS.map(b => {
        const inBucket = processed.filter(l => l._daysArrears >= b.lo && l._daysArrears <= b.hi);
        return {
            bucket:             b.key,
            loan_count:         inBucket.length,
            arrears_km:         km(inBucket.reduce((s, l) => s + l._arrears, 0)),
            outstanding_balance: inBucket.reduce((s, l) => s + l._principal, 0),
            accrued_charges:    inBucket.reduce((s, l) => s + l._accruedCharges, 0),
        };
    });

    // ── 10. Persist to database ───────────────────────────────────────────
    const snapshotTime = moment().format('YYYY-MM-DD HH:mm:ss');

    // Insert snapshot header
    const snapResult = await query(
        `INSERT INTO pd_snapshots
           (snapshot_time, as_of_date, active_loans, total_principal,
            total_outstanding, total_arrears, loans_in_arrears,
            par30, par90, collection_rate)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [
            snapshotTime, asOfDate,
            processed.length,
            totalPrincipal, totalOutstanding, totalArrears, loansInArrears,
            par30, par90, collectionRate,
        ]
    );
    const snapId = snapResult.insertId;

    // Helper: bulk insert with chunking (avoids huge packets)
    async function bulkInsert(table, cols, rows, chunkSize = 500) {
        if (!rows.length) return;
        const ph  = `(${cols.map(() => '?').join(',')})`;
        for (let i = 0; i < rows.length; i += chunkSize) {
            const chunk  = rows.slice(i, i + chunkSize);
            const values = chunk.map(r => cols.map(c => r[c])).flat();
            const sql    = `INSERT INTO ${table} (${cols.join(',')}) VALUES ${chunk.map(() => ph).join(',')}`;
            await query(sql, values);
        }
    }

    // Branch rows
    await bulkInsert('pd_branches',
        ['snapshot_id','branch_name','loans_count','outstanding','arrears','par30','par90','collection_rate'],
        branches.map(r => ({ snapshot_id: snapId, ...r }))
    );

    // Product rows
    await bulkInsert('pd_products',
        ['snapshot_id','product_name','loans_count','outstanding_km','arrears_km','par_rate'],
        products.map(r => ({ snapshot_id: snapId, ...r }))
    );

    // Officer rows
    await bulkInsert('pd_officers',
        ['snapshot_id','officer_name','loans_count','outstanding','arrears','par_rate','collection_rate'],
        officers.map(r => ({ snapshot_id: snapId, ...r }))
    );

    // Trend rows
    await bulkInsert('pd_trend',
        ['snapshot_id','month_year','loan_count','principal_km'],
        trendRows.map(r => ({
            snapshot_id:  snapId,
            month_year:   r.month_year,
            loan_count:   toI(r.loan_count),
            principal_km: toF(r.principal_km),
        }))
    );

    // Aging rows
    await bulkInsert('pd_aging',
        ['snapshot_id','bucket','loan_count','arrears_km','outstanding_balance'],
        agingRows.map(r => ({ snapshot_id: snapId, ...r }))
    );

    // Loan rows (individual)
    await bulkInsert('pd_loans',
        ['snapshot_id','loan_id','customer_name','customer_group','loan_number','product_name',
         'branch_name','officer_name','relationship_supervisor','principal','outstanding','arrears',
         'days_arrears','collection_rate','loan_status','loan_date','loan_added_date','disbursement_date',
         'interest_rate','period','loan_principal_amount','total_loan_amount','installment_amount',
         'accrued_charges','payments_in_arrears','actual_payments','par_classification',
         'rbm_classification','last_payment_date','collateral_value','maturity_date','total_expected'],
        processed.map(l => ({
            snapshot_id:      snapId,
            loan_id:          l.loan_id,
            customer_name:    l.customer_name,
            customer_group:   l.customer_group,
            loan_number:      l.loan_number,
            product_name:     l.product_name,
            branch_name:      l.branch_name,
            officer_name:     l.officer_name,
            relationship_supervisor: l.relationship_supervisor,
            principal:        l._principal,
            outstanding:      l._outstanding,
            arrears:          l._arrears,
            days_arrears:     l._daysArrears,
            collection_rate:  l._collRate,
            loan_status:      l.loan_status,
            loan_date:        l.loan_date ? moment(l.loan_date).format('YYYY-MM-DD') : null,
            loan_added_date:  l.loan_added_date ? moment(l.loan_added_date).format('YYYY-MM-DD') : null,
            disbursement_date: l.disbursement_date ? moment(l.disbursement_date).format('YYYY-MM-DD') : null,
            interest_rate:    l.interest_rate,
            period:           l.period,
            loan_principal_amount: l.loan_principal_amount,
            total_loan_amount: l.total_loan_amount,
            installment_amount: l._installment,
            accrued_charges:  l._accruedCharges,
            payments_in_arrears: l._paymentsInArrears,
            actual_payments:  l._actualPayments,
            par_classification: l._parClassification,
            rbm_classification: l._rbmClassification,
            last_payment_date: l._lastPaymentDate ? moment(l._lastPaymentDate).format('YYYY-MM-DD') : null,
            collateral_value: l._collateralValue,
            maturity_date:    l._maturityDate ? moment(l._maturityDate).format('YYYY-MM-DD') : null,
            total_expected:   l._totalExpected,
        }))
    );

    // ── 11. Purge snapshots older than 72 hours to keep the tables lean ───
    // Remove child rows first to avoid orphaned aggregates when foreign keys are absent.
    const staleRows = await query(
        `SELECT id FROM pd_snapshots WHERE snapshot_time < DATE_SUB(NOW(), INTERVAL 72 HOUR)`
    );
    const staleIds = staleRows.map(r => r.id).filter(Boolean);
    if (staleIds.length) {
        const inClause = staleIds.join(',');
        await query(`DELETE FROM pd_branches WHERE snapshot_id IN (${inClause})`);
        await query(`DELETE FROM pd_products WHERE snapshot_id IN (${inClause})`);
        await query(`DELETE FROM pd_officers WHERE snapshot_id IN (${inClause})`);
        await query(`DELETE FROM pd_trend WHERE snapshot_id IN (${inClause})`);
        await query(`DELETE FROM pd_aging WHERE snapshot_id IN (${inClause})`);
        await query(`DELETE FROM pd_loans WHERE snapshot_id IN (${inClause})`);
        await query(`DELETE FROM pd_snapshots WHERE id IN (${inClause})`);
    }

    const elapsed = ((Date.now() - startedAt) / 1000).toFixed(1);
    console.log(
        `[Portfolio Dashboard] Snapshot #${snapId} complete — ` +
        `${processed.length} loans, PAR30=${par30.toFixed(2)}%, PAR90=${par90.toFixed(2)}% — ` +
        `${elapsed}s`
    );
    return snapId;
}

function mapPdLoanRow(r) {
    const gross = toF(r.principal);
    const outstanding = toF(r.outstanding);
    const accrued = r.accrued_charges != null
        ? toF(r.accrued_charges)
        : Math.max(outstanding - gross, 0);
    return {
        loan_id: r.loan_id,
        customer_name: r.customer_name,
        customer_group: r.customer_group || 'N/A',
        loan_number: r.loan_number,
        product_name: r.product_name,
        branch_name: r.branch_name,
        officer_name: r.officer_name,
        relationship_supervisor: r.relationship_supervisor || 'N/A',
        loan_date: r.loan_date || r.disbursement_date,
        loan_principal_amount: toF(r.loan_principal_amount),
        gross_loan_portfolio: gross,
        principal: gross,
        period: r.period,
        interest_rate: toF(r.interest_rate),
        total_loan_amount: toF(r.total_loan_amount),
        installment_amount: toF(r.installment_amount),
        accrued_charges: accrued,
        outstanding,
        arrears: toF(r.arrears),
        days_arrears: toI(r.days_arrears),
        par_classification: r.par_classification || parClassification(r.days_arrears),
        rbm_classification: r.rbm_classification || determineRBMClassification(r.days_arrears),
        payments_in_arrears: toI(r.payments_in_arrears),
        collection_rate: toF(r.collection_rate),
        last_payment_date: r.last_payment_date,
        collateral_value: toF(r.collateral_value),
        maturity_date: r.maturity_date,
        total_expected: toF(r.total_expected),
        actual_payments: toF(r.actual_payments),
        loan_status: r.loan_status,
        loan_added_date: r.loan_added_date,
        disbursement_date: r.disbursement_date,
    };
}

/**
 * Returns the latest snapshot as a fully-formed JSON payload ready for the frontend.
 * If no snapshot exists, triggers a live computation first.
 */
async function getLatestPortfolioDashboard() {
    // Find the most recent snapshot
    let snapRows = await query(
        `SELECT * FROM pd_snapshots ORDER BY snapshot_time DESC LIMIT 1`
    );

    if (!snapRows.length) {
        await computeAndStorePortfolioDashboard();
        snapRows = await query(
            `SELECT * FROM pd_snapshots ORDER BY snapshot_time DESC LIMIT 1`
        );
        if (!snapRows.length) {
            return null;
        }
    }

    const snap = snapRows[0];
    const sid  = snap.id;

    // Load all related tables in parallel
    const [branches, products, officers, trend, aging, loans] = await Promise.all([
        query(`SELECT * FROM pd_branches WHERE snapshot_id = ? ORDER BY outstanding DESC`, [sid]),
        query(`SELECT * FROM pd_products WHERE snapshot_id = ? ORDER BY outstanding_km DESC`, [sid]),
        query(`SELECT * FROM pd_officers WHERE snapshot_id = ? ORDER BY outstanding DESC`, [sid]),
        query(`SELECT * FROM pd_trend    WHERE snapshot_id = ? ORDER BY month_year ASC`,  [sid]),
        query(`SELECT * FROM pd_aging    WHERE snapshot_id = ? ORDER BY FIELD(bucket,'1-30d','31-60d','61-90d','91-180d','>180d')`, [sid]),
        query(`SELECT * FROM pd_loans    WHERE snapshot_id = ?`, [sid]),
    ]);

    return {
        snapshot: {
            id:               snap.id,
            snapshot_time:    snap.snapshot_time,
            as_of_date:       snap.as_of_date,
            active_loans:     snap.active_loans,
            total_principal:  toF(snap.total_principal),
            total_accrued_charges: Math.max(toF(snap.total_outstanding) - toF(snap.total_principal), 0),
            total_outstanding: toF(snap.total_outstanding),
            total_arrears:    toF(snap.total_arrears),
            loans_in_arrears: snap.loans_in_arrears,
            par30:            toF(snap.par30),
            par90:            toF(snap.par90),
            portfolio_par:    toF(snap.total_principal) > 0
                ? clamp((toF(snap.total_arrears) / toF(snap.total_principal)) * 100, 0, 100)
                : 0,
            collection_rate:  toF(snap.collection_rate),
        },
        branches: branches.map(r => ({
            branch_name:     r.branch_name,
            loans_count:     r.loans_count,
            outstanding:     toF(r.outstanding),
            arrears:         toF(r.arrears),
            par30:           toF(r.par30),
            par90:           toF(r.par90),
            collection_rate: toF(r.collection_rate),
        })),
        products: products.map(r => ({
            product_name:   r.product_name,
            loans_count:    r.loans_count,
            outstanding_km: toF(r.outstanding_km),
            arrears_km:     toF(r.arrears_km),
            par_rate:       toF(r.par_rate),
        })),
        officers: officers.map(r => ({
            officer_name:    r.officer_name,
            loans_count:     r.loans_count,
            outstanding:     toF(r.outstanding),
            arrears:         toF(r.arrears),
            par_rate:        toF(r.par_rate),
            collection_rate: toF(r.collection_rate),
        })),
        trend: trend.map(r => ({
            month_year:   r.month_year,
            loan_count:   toI(r.loan_count),
            principal_km: toF(r.principal_km),
        })),
        aging: aging.map(r => ({
            bucket:              r.bucket,
            loan_count:          toI(r.loan_count),
            arrears_km:          toF(r.arrears_km),
            outstanding_balance: toF(r.outstanding_balance),
        })),
        loans: loans.map(r => mapPdLoanRow(r)),
    };
}

module.exports = { computeAndStorePortfolioDashboard, getLatestPortfolioDashboard };
