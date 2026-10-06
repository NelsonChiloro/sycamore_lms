const fs = require('fs');
const moment = require('moment');
const { query, getOfficerIdsUnderSupervisor, sqlUnpaidPrincipalExpr, sqlUnpaidChargesExpr } = require('./databaseHelpers');

const BATCH_SIZE = 5000;

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatNumber(value) {
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
}

function buildQuery(filterOptions) {
    const unpaidPrincipal = sqlUnpaidPrincipalExpr('ps');
    const unpaidCharges = sqlUnpaidChargesExpr('ps');
    const accruedCharges = `CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule <= LAST_DAY(CURDATE()) THEN ${unpaidCharges} ELSE 0 END`;

    let sql = `
        SELECT
            ps.customer_type,
            ic.Firstname AS ifname,
            ic.Lastname AS ilname,
            g.group_name,
            l.loan_id,
            l.loan_number,
            loan_products.product_name,
            ps.payment_number,
            ps.amount AS pamount,
            ps.principal AS pprincipal,
            ps.interest AS pinterest,
            ps.ploan_cover,
            ps.padmin_fee,
            ps.paid_amount,
            ps.amount,
            ps.payment_schedule,
            ps.paid_date,
            e.Firstname AS efname,
            e.Lastname AS elname,
            ${unpaidPrincipal} AS unpaid_principal,
            ${accruedCharges} AS unpaid_charges,
            (${unpaidPrincipal}) + (${accruedCharges}) AS portfolio_outstanding
        FROM payement_schedules ps
        JOIN loan l ON l.loan_id = ps.loan_id
        LEFT JOIN individual_customers ic ON ic.id = ps.customer AND ps.customer_type = 'individual'
        LEFT JOIN \`groups\` g ON g.group_id = ps.customer AND ps.customer_type = 'group'
        LEFT JOIN loan_products ON loan_products.loan_product_id = l.loan_product
        LEFT JOIN employees e ON l.loan_added_by = e.id
        WHERE l.loan_status = 'ACTIVE'
    `;
    const params = [];

    if (filterOptions.supervisor && filterOptions.supervisor !== 'All') {
        // supervisor filter applied via officer id list in caller
    }

    if (filterOptions.officer_id) {
        sql += ' AND l.loan_added_by = ?';
        params.push(filterOptions.officer_id);
    } else if (filterOptions.officer_ids && filterOptions.officer_ids.length) {
        sql += ` AND l.loan_added_by IN (${filterOptions.officer_ids.map(() => '?').join(',')})`;
        params.push(...filterOptions.officer_ids);
    }

    if (filterOptions.product_id) {
        sql += ' AND l.loan_product = ?';
        params.push(filterOptions.product_id);
    }

    if (filterOptions.loan_id) {
        sql += ' AND l.loan_id = ?';
        params.push(filterOptions.loan_id);
    }

    if (filterOptions.from && filterOptions.to) {
        sql += ' AND ps.payment_schedule BETWEEN ? AND ?';
        params.push(filterOptions.from, filterOptions.to);
    } else if (filterOptions.from) {
        sql += ' AND ps.payment_schedule >= ?';
        params.push(filterOptions.from);
    } else if (filterOptions.to) {
        sql += ' AND ps.payment_schedule <= ?';
        params.push(filterOptions.to);
    }

    sql += ' ORDER BY l.loan_number ASC, ps.payment_number ASC';
    return { sql, params };
}

function normalizeOfficerId(value) {
    if (value === null || value === undefined || value === '' || value === 'All') {
        return null;
    }
    return value;
}

function writeReportHeader(stream, filterOptions) {
    const filterLines = [];
    filterLines.push(`<p><strong>Loan Officer:</strong> ${escapeHtml(filterOptions.officer_name || 'All Officers')}</p>`);
    filterLines.push(`<p><strong>Loan Product:</strong> ${escapeHtml(filterOptions.product_name || 'All Products')}</p>`);
    filterLines.push(`<p><strong>Loan:</strong> ${escapeHtml(filterOptions.loan_label || 'All Loans')}</p>`);
    if (filterOptions.from || filterOptions.to) {
        filterLines.push(`<p><strong>Scheduled Date Range:</strong> ${escapeHtml(filterOptions.from || 'Any')} to ${escapeHtml(filterOptions.to || 'Any')}</p>`);
    } else {
        filterLines.push('<p><strong>Scheduled Date Range:</strong> All dates</p>');
    }

    stream.write(`<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Loan Payment Outstanding Balances Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #222; }
        h1 { color: #153505; margin-bottom: 4px; }
        .meta { margin-bottom: 16px; color: #555; }
        .filter-info { background: #f5f5f5; border: 1px solid #ddd; padding: 12px; margin-bottom: 16px; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #153505; color: #fff; position: sticky; top: 0; }
        tfoot td { font-weight: bold; background: #eef3ea; }
        .export-buttons { margin: 12px 0; text-align: right; }
        .export-buttons button { padding: 6px 12px; background: #153505; color: #fff; border: none; border-radius: 3px; margin-left: 5px; cursor: pointer; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportData(type) {
            const fileName = 'Outstanding_Balances_Report.' + type;
            const table = document.getElementById('balances-table');
            const wb = XLSX.utils.table_to_book(table);
            XLSX.writeFile(wb, fileName);
        }
    </script>
</head>
<body>
    <h1>All Loan Payment Outstanding Balances Report</h1>
    <div class="meta">Generated on ${moment().format('YYYY-MM-DD HH:mm:ss')}</div>
    <div class="filter-info">${filterLines.join('')}</div>
    <div class="export-buttons">
        <span>Export as:</span>
        <button onclick="exportData('xlsx')">Excel (xlsx)</button>
        <button onclick="exportData('csv')">CSV</button>
    </div>
    <div style="overflow-x:auto;">
    <table id="balances-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer Type</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Group Name</th>
                <th>Loan Number</th>
                <th>Loan Product</th>
                <th>Payment Number</th>
                <th>Scheduled Amount</th>
                <th>Principal</th>
                <th>Interest</th>
                <th>Loan Cover</th>
                <th>Admin Fee</th>
                <th>Paid Amount</th>
                <th>Outstanding Balance</th>
                <th>Scheduled Date</th>
                <th>Paid Date</th>
                <th>Officer</th>
            </tr>
        </thead>
        <tbody>
`);
}

function writeReportFooter(stream, totals, rowCount) {
    stream.write(`        </tbody>
        <tfoot>
            <tr>
                <td colspan="8">Totals (${rowCount} rows)</td>
                <td>${formatNumber(totals.scheduled)}</td>
                <td>${formatNumber(totals.principal)}</td>
                <td>${formatNumber(totals.interest)}</td>
                <td>${formatNumber(totals.loanCover)}</td>
                <td>${formatNumber(totals.adminFee)}</td>
                <td>${formatNumber(totals.paid)}</td>
                <td>${formatNumber(totals.outstanding)}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
    </div>
</body>
</html>`);
}

function writeRow(stream, row, rowNum) {
    const outstanding = parseFloat(row.portfolio_outstanding) || 0;
    const officer = [row.efname, row.elname].filter(Boolean).join(' ');

    stream.write(`            <tr>
                <td>${rowNum}</td>
                <td>${escapeHtml(row.customer_type)}</td>
                <td>${escapeHtml(row.ifname)}</td>
                <td>${escapeHtml(row.ilname)}</td>
                <td>${escapeHtml(row.group_name)}</td>
                <td>${escapeHtml(row.loan_number)}</td>
                <td>${escapeHtml(row.product_name)}</td>
                <td>${escapeHtml(row.payment_number)}</td>
                <td>${escapeHtml(row.pamount)}</td>
                <td>${escapeHtml(row.pprincipal)}</td>
                <td>${escapeHtml(row.pinterest)}</td>
                <td>${escapeHtml(row.ploan_cover)}</td>
                <td>${escapeHtml(row.padmin_fee)}</td>
                <td>${escapeHtml(row.paid_amount)}</td>
                <td>${formatNumber(outstanding)}</td>
                <td>${escapeHtml(row.payment_schedule)}</td>
                <td>${escapeHtml(row.paid_date)}</td>
                <td>${escapeHtml(officer)}</td>
            </tr>
`);
}

async function resolveSupervisorOfficerIds(filterOptions) {
    if (!filterOptions.supervisor || filterOptions.supervisor === 'All') {
        return null;
    }
    const ids = await getOfficerIdsUnderSupervisor(filterOptions.supervisor);
    return ids.length ? ids : [];
}

async function resolveFilterOptions(filterOptions) {
    const resolved = { ...filterOptions };
    resolved.officer_id = normalizeOfficerId(resolved.officer_id ?? resolved.officer);

    if (resolved.supervisor && resolved.supervisor !== 'All' && !resolved.officer_id) {
        const officerIds = await resolveSupervisorOfficerIds(resolved);
        if (officerIds && officerIds.length === 0) {
            resolved.officer_ids = [-1];
        } else if (officerIds) {
            resolved.officer_ids = officerIds;
        }
    }

    return resolved;
}

function formatDateValue(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('YYYY-MM-DD') : String(value);
}

function mapBalanceRow(row) {
    return {
        loan_id: row.loan_id,
        customer_type: row.customer_type,
        ifname: row.ifname,
        ilname: row.ilname,
        group_name: row.group_name,
        loan_number: row.loan_number,
        product_name: row.product_name,
        payment_number: row.payment_number,
        pamount: parseFloat(row.pamount) || 0,
        pprincipal: parseFloat(row.pprincipal) || 0,
        pinterest: parseFloat(row.pinterest) || 0,
        ploan_cover: parseFloat(row.ploan_cover) || 0,
        padmin_fee: parseFloat(row.padmin_fee) || 0,
        paid_amount: parseFloat(row.paid_amount) || 0,
        portfolio_outstanding: parseFloat(row.portfolio_outstanding) || 0,
        payment_schedule: formatDateValue(row.payment_schedule),
        paid_date: formatDateValue(row.paid_date),
        officer_name: [row.efname, row.elname].filter(Boolean).join(' '),
    };
}

async function getOutstandingBalancesTotals(resolved) {
    const { sql, params } = buildQuery(resolved);
    const baseSql = sql.replace(/\s+ORDER BY[\s\S]*$/i, '');
    const totalsSql = `
        SELECT
            COUNT(*) AS row_count,
            COALESCE(SUM(src.pamount), 0) AS scheduled,
            COALESCE(SUM(src.pprincipal), 0) AS principal,
            COALESCE(SUM(src.pinterest), 0) AS interest,
            COALESCE(SUM(src.ploan_cover), 0) AS loan_cover,
            COALESCE(SUM(src.padmin_fee), 0) AS admin_fee,
            COALESCE(SUM(src.paid_amount), 0) AS paid,
            COALESCE(SUM(src.portfolio_outstanding), 0) AS outstanding
        FROM (${baseSql}) src
    `;
    const rows = await query(totalsSql, params);
    return rows[0] || {};
}

async function getOutstandingBalancesPage(resolved, page = 1, limit = 500) {
    const { sql, params } = buildQuery(resolved);
    const safePage = Math.max(1, page);
    const safeLimit = Math.min(2000, Math.max(1, limit));
    const offset = (safePage - 1) * safeLimit;
    const pageSql = `${sql} LIMIT ${safeLimit} OFFSET ${offset}`;
    const rows = await query(pageSql, params);
    return rows.map(mapBalanceRow);
}

async function queryOutstandingBalancesData(filterOptions, page = 1, limit = 500) {
    const resolved = await resolveFilterOptions(filterOptions);
    const safePage = Math.max(1, page);
    const safeLimit = Math.min(2000, Math.max(1, limit));

    const [totalsRow, rows] = await Promise.all([
        getOutstandingBalancesTotals(resolved),
        getOutstandingBalancesPage(resolved, safePage, safeLimit),
    ]);

    return {
        page: safePage,
        limit: safeLimit,
        total_rows: parseInt(totalsRow.row_count, 10) || 0,
        totals: {
            scheduled: parseFloat(totalsRow.scheduled) || 0,
            principal: parseFloat(totalsRow.principal) || 0,
            interest: parseFloat(totalsRow.interest) || 0,
            loan_cover: parseFloat(totalsRow.loan_cover) || 0,
            admin_fee: parseFloat(totalsRow.admin_fee) || 0,
            paid: parseFloat(totalsRow.paid) || 0,
            outstanding: parseFloat(totalsRow.outstanding) || 0,
        },
        rows,
    };
}

/**
 * Generate outstanding balances report directly to an HTML file (batched for memory efficiency).
 */
async function generateOutstandingBalancesReport(filterOptions, reportId, reportTrackers, filePath) {
    const resolved = await resolveFilterOptions(filterOptions);
    const { sql, params } = buildQuery(resolved);
    const stream = fs.createWriteStream(filePath, { encoding: 'utf8' });
    writeReportHeader(stream, resolved);

    const totals = {
        scheduled: 0,
        principal: 0,
        interest: 0,
        loanCover: 0,
        adminFee: 0,
        paid: 0,
        outstanding: 0,
    };

    let offset = 0;
    let rowCount = 0;

    reportTrackers[reportId].percentage = 10;

    while (true) {
        const batchSql = `${sql} LIMIT ${BATCH_SIZE} OFFSET ${offset}`;
        // eslint-disable-next-line no-await-in-loop
        const rows = await query(batchSql, params);
        if (!rows.length) {
            break;
        }

        for (const row of rows) {
            rowCount += 1;
            totals.scheduled += parseFloat(row.pamount) || 0;
            totals.principal += parseFloat(row.pprincipal) || 0;
            totals.interest += parseFloat(row.pinterest) || 0;
            totals.loanCover += parseFloat(row.ploan_cover) || 0;
            totals.adminFee += parseFloat(row.padmin_fee) || 0;
            totals.paid += parseFloat(row.paid_amount) || 0;
            totals.outstanding += parseFloat(row.portfolio_outstanding) || 0;
            writeRow(stream, row, rowCount);
        }

        offset += rows.length;
        reportTrackers[reportId].percentage = Math.min(95, 10 + Math.floor(offset / 500));

        if (rows.length < BATCH_SIZE) {
            break;
        }
    }

    writeReportFooter(stream, totals, rowCount);
    reportTrackers[reportId].percentage = 100;

    await new Promise((resolve, reject) => {
        stream.end(() => resolve());
        stream.on('error', reject);
    });

    console.log(`Outstanding Balances Report complete — ${rowCount} schedule rows`);
    return { rowCount, totals };
}

module.exports = {
    generateOutstandingBalancesReport,
    queryOutstandingBalancesData,
    buildQuery,
};
