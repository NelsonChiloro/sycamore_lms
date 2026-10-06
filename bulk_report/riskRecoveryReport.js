const moment = require('moment');

function dbQuery(db, sql, params = []) {
    return new Promise((resolve, reject) => {
        db.query(sql, params, (error, rows) => error ? reject(error) : resolve(rows));
    });
}

function escapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function money(value) {
    return Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function classification(days) {
    const value = Number(days || 0);
    if (value < 30) return 'Standard';
    if (value < 60) return 'Special Mention';
    if (value < 90) return 'Substandard';
    if (value < 180) return 'Doubtful';
    return 'Loss';
}

function categoryRange(category) {
    switch (category) {
        case 'Standard': return [0, 29];
        case 'Special_Mention': return [30, 59];
        case 'Substandard': return [60, 89];
        case 'Doubtful': return [90, 179];
        case 'Loss': return [180, null];
        default: return null;
    }
}

async function generateRiskRecoveryReport(filters, reportId, reportTrackers, db) {
    reportTrackers[reportId].percentage = 5;

    const scheduleMetrics = `
        SELECT loan_id,
            COALESCE(MAX(CASE
                WHEN status IN ('NOT PAID', 'PARTIAL PAID') AND payment_schedule < CURRENT_DATE()
                THEN DATEDIFF(CURRENT_DATE(), payment_schedule) END), 0) AS days_in_arrears,
            COALESCE(SUM(CASE WHEN status IN ('NOT PAID', 'PARTIAL PAID') THEN
                GREATEST(amount - COALESCE(paid_amount, 0), 0) *
                CASE WHEN amount > 0 THEN principal / amount ELSE 0 END
                ELSE 0 END), 0) AS principal_balance,
            COALESCE(SUM(CASE WHEN status IN ('NOT PAID', 'PARTIAL PAID') THEN
                GREATEST(amount - COALESCE(paid_amount, 0), 0) *
                CASE WHEN amount > 0 THEN interest / amount ELSE 0 END
                ELSE 0 END), 0) AS interest_balance,
            MAX(CASE WHEN COALESCE(paid_amount, 0) > 0 THEN paid_date END) AS last_payment_date,
            COALESCE(SUM(COALESCE(paid_amount, 0)), 0) AS amount_paid
        FROM payement_schedules
        GROUP BY loan_id`;

    let sql = `
        SELECT l.loan_id, l.loan_number, l.loan_status,
            COALESCE(l.write_off_recommendation, 0) AS write_off_recommendation,
            lp.product_name,
            CONCAT_WS(' ', re.Firstname, re.Lastname) AS risk_officer,
            COALESCE(ps.days_in_arrears, 0) AS days_in_arrears,
            COALESCE(ps.principal_balance, 0) AS principal_balance,
            COALESCE(ps.interest_balance, 0) AS interest_balance,
            ps.last_payment_date,
            COALESCE(ps.amount_paid, 0) AS amount_paid,
            COALESCE(cm.collateral_total_value, 0) AS collateral_total_value,
            CASE
                WHEN LOWER(TRIM(COALESCE(l.customer_type, ''))) IN ('individual', 'member')
                    THEN TRIM(CONCAT(COALESCE(ic.Firstname, ''), ' ', COALESCE(ic.Lastname, '')))
                WHEN LOWER(TRIM(COALESCE(l.customer_type, ''))) IN ('group', 'groups')
                    THEN CONCAT(COALESCE(g.group_name, 'Unknown Group'),
                        CASE WHEN COALESCE(g.group_code, '') <> '' THEN CONCAT(' (', g.group_code, ')') ELSE '' END)
                ELSE 'Unknown'
            END AS customer_name,
            (SELECT b.BranchName FROM branches b
                WHERE b.id = l.branch OR b.Code = l.branch OR b.BranchCode = l.branch
                LIMIT 1) AS branch_name
        FROM loan l
        LEFT JOIN loan_products lp ON lp.loan_product_id = l.loan_product
        LEFT JOIN employees re ON re.id = l.risk_officer_id
        LEFT JOIN (${scheduleMetrics}) ps ON ps.loan_id = l.loan_id
        LEFT JOIN (
            SELECT loan_id, COALESCE(SUM(COALESCE(estimated_price, 0)), 0) AS collateral_total_value
            FROM collateral GROUP BY loan_id
        ) cm ON cm.loan_id = l.loan_id
        LEFT JOIN individual_customers ic ON ic.id = l.loan_customer
        LEFT JOIN groups g ON g.group_id = l.loan_customer
        WHERE l.loan_status = 'ACTIVE' AND l.disbursed = 'Yes'`;
    const params = [];

    if (filters.officer) {
        sql += ' AND l.risk_officer_id = ?';
        params.push(filters.officer);
    }
    if (filters.branch) {
        sql += ` AND (
            CAST(l.branch AS CHAR) = CAST(? AS CHAR)
            OR EXISTS (
                SELECT 1 FROM branches bf
                WHERE (CAST(bf.id AS CHAR) = CAST(? AS CHAR) OR bf.Code = ? OR bf.BranchCode = ?)
                  AND (CAST(l.branch AS CHAR) = CAST(bf.id AS CHAR)
                    OR l.branch = bf.Code OR l.branch = bf.BranchCode)
            )
        )`;
        params.push(filters.branch, filters.branch, filters.branch, filters.branch);
    }
    if (filters.writeoff !== '' && filters.writeoff != null) {
        sql += ' AND COALESCE(l.write_off_recommendation, 0) = ?';
        params.push(Number(filters.writeoff));
    }

    const range = categoryRange(filters.risk_category);
    if (range) {
        sql += ' AND COALESCE(ps.days_in_arrears, 0) >= ?';
        params.push(range[0]);
        if (range[1] !== null) {
            sql += ' AND COALESCE(ps.days_in_arrears, 0) <= ?';
            params.push(range[1]);
        }
    }
    sql += ' ORDER BY COALESCE(ps.days_in_arrears, 0) DESC, l.loan_number ASC';

    const loans = await dbQuery(db, sql, params);
    reportTrackers[reportId].percentage = 70;

    const totals = loans.reduce((result, loan) => {
        result.principal += Number(loan.principal_balance || 0);
        result.interest += Number(loan.interest_balance || 0);
        result.writeoffs += Number(loan.write_off_recommendation || 0) === 1 ? 1 : 0;
        return result;
    }, { principal: 0, interest: 0, writeoffs: 0 });

    const rows = loans.map((loan, index) => {
        const risk = classification(loan.days_in_arrears);
        const rowClass = 'risk-' + risk.toLowerCase().replace(/\s+/g, '-');
        return `<tr class="${rowClass}">
            <td>${index + 1}</td>
            <td>${escapeHtml(loan.loan_number)}</td>
            <td>${escapeHtml(loan.customer_name)}</td>
            <td>${escapeHtml(loan.product_name)}</td>
            <td>${escapeHtml(loan.branch_name || '')}</td>
            <td>${escapeHtml(loan.loan_status)}</td>
            <td>${escapeHtml(risk)}</td>
            <td>${escapeHtml(loan.risk_officer || 'Not assigned')}</td>
            <td class="number">${money(Number(loan.principal_balance) + Number(loan.interest_balance))}</td>
            <td class="number">${money(loan.principal_balance)}</td>
            <td class="number">${money(loan.interest_balance)}</td>
            <td>${escapeHtml(loan.last_payment_date ? moment(loan.last_payment_date).format('YYYY-MM-DD') : 'No payments')}</td>
            <td class="number">${money(loan.amount_paid)}</td>
            <td class="number">${Number(loan.days_in_arrears || 0)}</td>
            <td class="number">${money(loan.collateral_total_value)}</td>
            <td>${Number(loan.write_off_recommendation || 0) === 1 ? 'Yes' : 'No'}</td>
        </tr>`;
    }).join('');

    const filterLabels = [
        filters.risk_category ? `Risk: ${filters.risk_category.replace('_', ' ')}` : 'All risk categories',
        filters.officer ? `Risk officer ID: ${filters.officer}` : 'All risk officers',
        filters.branch ? `Branch ID: ${filters.branch}` : 'All branches',
        filters.writeoff === '1' ? 'Recommended for write-off' :
            filters.writeoff === '0' ? 'Not recommended for write-off' : 'All write-off statuses',
    ];

    reportTrackers[reportId].percentage = 95;
    return `<!doctype html>
<html><head><meta charset="utf-8"><title>Risk Recovery Report</title>
<style>
body{font-family:Arial,sans-serif;color:#222;margin:24px}.toolbar{margin-bottom:16px}
button{padding:8px 14px;background:#153505;color:#fff;border:0;border-radius:4px;cursor:pointer}
h1{color:#153505;margin-bottom:5px}.meta{color:#666;margin-bottom:16px}
.summary{display:flex;gap:12px;flex-wrap:wrap;margin:18px 0}.card{border:1px solid #ddd;border-radius:7px;padding:12px;min-width:190px}
.card strong{display:block;font-size:20px;margin-top:5px}table{width:100%;border-collapse:collapse;font-size:12px}
th,td{border:1px solid #ddd;padding:7px;vertical-align:top}th{background:#153505;color:#fff;position:sticky;top:0}
.number{text-align:right;white-space:nowrap}.risk-loss{background:#ffebee}.risk-doubtful{background:#fff3e0}
.risk-substandard{background:#e8f5e9}.risk-special-mention{background:#fffde7}
@media print{.toolbar{display:none}th{position:static}body{margin:5px}}
</style></head><body>
<div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>
<h1>Risk Recovery Report</h1>
<div class="meta">Generated: ${escapeHtml(moment().format('YYYY-MM-DD HH:mm:ss'))}<br>${escapeHtml(filterLabels.join(' | '))}</div>
<div class="summary">
 <div class="card">Total Loans<strong>${loans.length.toLocaleString()}</strong></div>
 <div class="card">Total Principal<strong>MWK ${money(totals.principal)}</strong></div>
 <div class="card">Total Interest<strong>MWK ${money(totals.interest)}</strong></div>
 <div class="card">Write-off Recommendations<strong>${totals.writeoffs.toLocaleString()}</strong></div>
</div>
<table><thead><tr><th>#</th><th>Loan #</th><th>Client Name</th><th>Product</th><th>Branch</th><th>Status</th>
<th>RBM Classification</th><th>Risk Officer</th><th>Loan Balance</th><th>Principal Balance</th>
<th>Interest Charges</th><th>Last Payment</th><th>Amount Paid</th><th>Days in Arrears</th>
<th>Collateral Value</th><th>Write-off Rec.</th></tr></thead>
<tbody>${rows || '<tr><td colspan="16" style="text-align:center">No loans matched the selected filters.</td></tr>'}</tbody></table>
</body></html>`;
}

module.exports = { generateRiskRecoveryReport };
