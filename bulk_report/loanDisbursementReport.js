const moment = require('moment');
const {
    query,
    sqlBranchJoin,
    buildReportSupervisorContext,
    appendOfficerOrSupervisorLoanFilter,
} = require('./databaseHelpers');

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function stripHtml(value) {
    return String(value ?? '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

function formatDate(value) {
    return value ? moment(value).format('YYYY-MM-DD') : '';
}

function formatNumber(value) {
    return Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function normalizeDisbursementMethod(remarks) {
    const text = stripHtml(remarks);
    return /(^|\b)(bt|bank|transfer)(\b|$)/i.test(text) ? 'Bank Transfer' : 'Cash';
}

async function generateLoanDisbursementReport(options, reportId, reportTrackers) {
    const { user, branch, product, status, from, to, supervisor } = options;
    const supCtx = await buildReportSupervisorContext({ supervisor, user });
    const params = [];

    let sql = `
        SELECT
            loan.loan_id,
            loan.loan_number,
            loan.disbursed_date,
            loan.loan_principal AS approved_amount,
            loan.loan_principal AS disbursed_amount,
            loan.loan_interest,
            loan.loan_period,
            loan.period_type,
            loan.loan_status,
            loan.narration AS remarks,
            b.id AS branch_id,
            loan_products.product_name,
            CONCAT(COALESCE(e.Firstname, ''), ' ', COALESCE(e.Lastname, '')) AS loan_officer,
            COALESCE(b.BranchName, 'Unknown Branch') AS branch_name,
            CASE
                WHEN g.group_id IS NOT NULL THEN CONCAT(g.group_name, ' (', g.group_code, ')')
                WHEN ic.id IS NOT NULL THEN CONCAT(ic.Firstname, ' ', ic.Lastname)
                ELSE 'Unknown Customer'
            END AS client_name,
            schedules.repayment_start,
            schedules.maturity_date
        FROM loan
        LEFT JOIN loan_products ON loan_products.loan_product_id = loan.loan_product
        LEFT JOIN employees e ON e.id = loan.loan_added_by
        LEFT JOIN individual_customers ic
            ON loan.loan_customer = ic.id AND loan.customer_type = 'individual'
        LEFT JOIN \`groups\` g
            ON loan.loan_customer = g.group_id AND loan.customer_type = 'group'
        ${sqlBranchJoin('loan', 'b')}
        LEFT JOIN (
            SELECT
                loan_id,
                MIN(payment_schedule) AS repayment_start,
                MAX(payment_schedule) AS maturity_date
            FROM payement_schedules
            GROUP BY loan_id
        ) schedules ON schedules.loan_id = loan.loan_id
        WHERE loan.disbursed = 'Yes'
    `;

    if (branch !== 'All') {
        sql += ` AND (
            loan.branch = ?
            OR loan.branch IN (SELECT Code FROM branches WHERE id = ?)
            OR loan.branch IN (SELECT BranchCode FROM branches WHERE id = ?)
        )`;
        params.push(branch, branch, branch);
    }

    if (status !== 'All') {
        sql += ' AND loan.loan_status = ?';
        params.push(status);
    }

    sql = appendOfficerOrSupervisorLoanFilter(sql, { ...supCtx, user }, 'loan', 'user');

    if (product !== 'All') {
        sql += ' AND loan.loan_product = ?';
        params.push(product);
    }

    // Period cards use the same branch/product/officer/status scope, but are
    // independent of the detail report's optional date range.
    const cumulativeSql = sql;
    const cumulativeParams = [...params];

    if (from && to) {
        sql += ' AND DATE(loan.disbursed_date) BETWEEN ? AND ?';
        params.push(formatDate(from), formatDate(to));
    } else if (from) {
        sql += ' AND DATE(loan.disbursed_date) >= ?';
        params.push(formatDate(from));
    } else if (to) {
        sql += ' AND DATE(loan.disbursed_date) <= ?';
        params.push(formatDate(to));
    }

    sql += ' ORDER BY loan.disbursed_date DESC, loan.loan_id DESC';

    reportTrackers[reportId].percentage = 25;
    const rows = await query(sql, params);
    const cumulativeRows = await query(cumulativeSql, cumulativeParams);
    const availableBranches = await query(
        'SELECT id, BranchName FROM branches ORDER BY BranchName ASC, id ASC'
    );
    reportTrackers[reportId].percentage = 80;

    rows.forEach((row) => {
        row.disbursement_method = normalizeDisbursementMethod(row.remarks);
    });

    const html = generateLoanDisbursementHTML(rows, options, cumulativeRows, availableBranches);
    reportTrackers[reportId].percentage = 100;
    return html;
}

function generateLoanDisbursementHTML(rows, options = {}, cumulativeRows = rows, availableBranches = []) {
    const summary = rows.reduce((totals, row) => {
        const approved = Number(row.approved_amount || 0);
        const disbursed = Number(row.disbursed_amount || 0);
        totals.approved += approved;
        totals.disbursed += disbursed;
        if (row.disbursement_method === 'Bank Transfer') {
            totals.bankTransfers += disbursed;
        } else {
            totals.cashDisbursed += disbursed;
        }
        return totals;
    }, { approved: 0, disbursed: 0, cashDisbursed: 0, bankTransfers: 0 });

    const averageLoanSize = rows.length ? summary.disbursed / rows.length : 0;
    const generatedAt = moment();
    const branchDisbursements = availableBranches.map((branchRow) => {
        const branchId = Number(branchRow.id);
        const matchingRows = rows.filter((row) => Number(row.branch_id) === branchId);
        const matchingCumulativeRows = cumulativeRows.filter((row) => Number(row.branch_id) === branchId);
        const branchCumulative = matchingCumulativeRows.reduce((totals, row) => {
            const disbursedDate = moment(row.disbursed_date);
            const amount = Number(row.disbursed_amount || 0);
            if (disbursedDate.isSame(generatedAt, 'day')) totals.day += amount;
            if (disbursedDate.isSame(generatedAt, 'month')) totals.month += amount;
            return totals;
        }, { day: 0, month: 0 });
        return {
            id: branchId,
            name: branchRow.BranchName || `Branch ${branchId}`,
            count: matchingRows.length,
            total: matchingRows.reduce((sum, row) => sum + Number(row.disbursed_amount || 0), 0),
            today: branchCumulative.day,
            month: branchCumulative.month,
        };
    });
    const cumulative = cumulativeRows.reduce((totals, row) => {
        const disbursedDate = moment(row.disbursed_date);
        const amount = Number(row.disbursed_amount || 0);
        if (disbursedDate.isSame(generatedAt, 'day')) totals.day += amount;
        if (disbursedDate.isSame(generatedAt, 'month')) totals.month += amount;
        if (disbursedDate.isSame(generatedAt, 'year')) totals.year += amount;
        return totals;
    }, { day: 0, month: 0, year: 0 });
    const reportPeriod = options.from || options.to
        ? `${options.from ? formatDate(options.from) : 'Beginning'} to ${options.to ? formatDate(options.to) : 'Present'}`
        : 'All dates';
    const filtersUsed = [];
    if (options.branch && options.branch !== 'All') {
        filtersUsed.push(['Branch', rows[0]?.branch_name || options.branch]);
    }
    if (options.product && options.product !== 'All') {
        filtersUsed.push(['Loan Product', rows[0]?.product_name || options.product]);
    }
    if (options.user && options.user !== 'All') {
        filtersUsed.push(['Loan Officer', String(rows[0]?.loan_officer || options.user).trim()]);
    }
    if (options.supervisor && options.supervisor !== 'All') {
        filtersUsed.push(['Relationship Supervisor', options.supervisor_name || options.supervisor]);
    }
    if (options.status && options.status !== 'All') {
        filtersUsed.push(['Loan Status', options.status]);
    }
    if (options.from || options.to) {
        filtersUsed.push([
            'Disbursement Date Range',
            `${options.from ? formatDate(options.from) : 'Beginning'} to ${options.to ? formatDate(options.to) : 'Present'}`,
        ]);
    }
    if (!filtersUsed.length) {
        filtersUsed.push(['Filters', 'All disbursed loans']);
    }

    const exportSummaryRows = [
        ['Loan Disbursement Report'],
        [],
        ['Summary'],
        ['Total Number of Loans', rows.length],
        ['Total Approved Amount', summary.approved],
        ['Total Disbursed Amount', summary.disbursed],
        ['Average Loan Size', averageLoanSize],
        ['Disbursements Today', cumulative.day],
        ['Disbursements This Month', cumulative.month],
        ['Disbursements This Year', cumulative.year],
        [],
        ['Disbursements by Branch'],
        ['Branch', 'Selected Period', 'Cumulative Today', 'Cumulative This Month'],
        ...branchDisbursements.map((branchRow) => [branchRow.name, branchRow.total, branchRow.today, branchRow.month]),
        ['Report Period', reportPeriod],
        ['Generated Date', generatedAt.format('YYYY-MM-DD HH:mm:ss')],
        [],
        ['Filters Used'],
        ...filtersUsed,
        [],
    ];
    const detailStartRow = exportSummaryRows.length;
    const filtersHtml = filtersUsed.map(([label, value]) => `
        <div class="filter-item"><strong>${escapeHtml(label)}:</strong> ${escapeHtml(value)}</div>
    `).join('');
    const branchCardsHtml = branchDisbursements.map((branchRow) => `
        <div class="summary-card branch-summary-card">
            <div class="label">${escapeHtml(branchRow.name)} Branch</div>
            <div class="value">MWK ${formatNumber(branchRow.total)}</div>
            <div class="sub-value">${branchRow.count.toLocaleString()} disbursement record${branchRow.count === 1 ? '' : 's'}</div>
            <div class="branch-card-sections">
                <div class="branch-card-section">
                    <div class="section-label">Cumulative Today</div>
                    <div class="section-value">MWK ${formatNumber(branchRow.today)}</div>
                </div>
                <div class="branch-card-section">
                    <div class="section-label">Cumulative This Month</div>
                    <div class="section-value">MWK ${formatNumber(branchRow.month)}</div>
                </div>
            </div>
        </div>
    `).join('');
    const bodyRows = rows.map((row) => `
        <tr>
            <td>${escapeHtml(formatDate(row.disbursed_date))}</td>
            <td>${escapeHtml(row.branch_name)}</td>
            <td>${escapeHtml(row.loan_number)}</td>
            <td>${escapeHtml(row.client_name)}</td>
            <td>${escapeHtml(row.product_name)}</td>
            <td>${escapeHtml(String(row.loan_officer || '').trim())}</td>
            <td class="text-right">${formatNumber(row.approved_amount)}</td>
            <td class="text-right">${formatNumber(row.disbursed_amount)}</td>
            <td class="text-right">${formatNumber(row.loan_interest)}%</td>
            <td class="text-center">${escapeHtml(row.loan_period)}</td>
            <td>${escapeHtml(formatDate(row.repayment_start))}</td>
            <td>${escapeHtml(formatDate(row.maturity_date))}</td>
            <td>${escapeHtml(row.loan_status)}</td>
            <td>${escapeHtml(stripHtml(row.remarks))}</td>
        </tr>
    `).join('');

    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Disbursement Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 10px; color: #222; }
        h1 { color: #153505; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; white-space: nowrap; }
        th { background-color: #153505; color: white; position: sticky; top: 0; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-section { background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #153505; }
        .summary-section h3 { margin: 0 0 12px; color: #153505; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .summary-card { background: white; padding: 12px; border-radius: 4px; text-align: center; border: 1px solid #c8e6c9; }
        .summary-card .label { font-size: 11px; color: #555; margin-bottom: 4px; }
        .summary-card .value { font-size: 15px; font-weight: bold; color: #153505; }
        .branch-summary { margin-top: 14px; padding-top: 12px; border-top: 1px solid #c8e6c9; }
        .branch-summary h4 { margin: 0 0 10px; color: #153505; }
        .branch-summary-card { border-left: 4px solid #153505; }
        .summary-card .sub-value { margin-top: 4px; font-size: 10px; color: #777; }
        .branch-card-sections { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e0e0e0; }
        .branch-card-section { background: #f5faf5; border: 1px solid #dcebdc; border-radius: 4px; padding: 8px 5px; }
        .branch-card-section .section-label { font-size: 10px; color: #666; margin-bottom: 3px; }
        .branch-card-section .section-value { font-size: 12px; font-weight: bold; color: #153505; }
        .filters-used { background: #fff; border: 1px solid #c8e6c9; border-radius: 4px; padding: 12px; margin-top: 12px; }
        .filters-used h4 { margin: 0 0 8px; color: #153505; }
        .filter-grid { display: flex; flex-wrap: wrap; gap: 8px 24px; }
        .filter-item { font-size: 12px; color: #444; }
        .action { float: right; margin-bottom: 20px; }
        button { padding: 6px 20px; cursor: pointer; background-color: #153505; color: white; border: 0; border-radius: 4px; margin-left: 5px; }
        .table-wrap { clear: both; overflow-x: auto; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportData(type) {
            const table = document.getElementById('data-table');
            const summaryRows = ${JSON.stringify(exportSummaryRows)};
            const worksheet = XLSX.utils.aoa_to_sheet(summaryRows);
            XLSX.utils.sheet_add_dom(worksheet, table, { origin: 'A${detailStartRow + 1}' });
            worksheet['!cols'] = Array(14).fill({ wch: 18 });
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Loan Disbursements');
            XLSX.writeFile(workbook, 'loan_disbursement_report.' + type);
        }
    </script>
</head>
<body>
    <h1>Loan Disbursement Report</h1>
    <div style="margin-bottom:12px;color:#555;font-size:12px;">
        <strong>Report Period:</strong> ${escapeHtml(reportPeriod)}
        &nbsp;|&nbsp; <strong>Generated Date:</strong> ${escapeHtml(generatedAt.format('YYYY-MM-DD HH:mm:ss'))}
    </div>
    <div class="summary-section">
        <h3>Summary Section</h3>
        <div class="summary-grid">
            <div class="summary-card"><div class="label">Total Disbursement Records</div><div class="value">${rows.length.toLocaleString()}</div></div>
            <div class="summary-card"><div class="label">Total Approved Amount</div><div class="value">MWK ${formatNumber(summary.approved)}</div></div>
            <div class="summary-card"><div class="label">Total Disbursed Amount</div><div class="value">MWK ${formatNumber(summary.disbursed)}</div></div>
            <div class="summary-card"><div class="label">Average Loan Size</div><div class="value">MWK ${formatNumber(averageLoanSize)}</div></div>
            <div class="summary-card"><div class="label">Cumulative Disbursements Today</div><div class="value">MWK ${formatNumber(cumulative.day)}</div></div>
            <div class="summary-card"><div class="label">Cumulative Disbursements This Month</div><div class="value">MWK ${formatNumber(cumulative.month)}</div></div>
            <div class="summary-card"><div class="label">Cumulative Disbursements This Year</div><div class="value">MWK ${formatNumber(cumulative.year)}</div></div>
        </div>
        <div class="branch-summary">
            <h4>Disbursements by Branch</h4>
            <div class="summary-grid">${branchCardsHtml || '<div class="summary-card"><div class="label">No branches configured</div><div class="value">MWK 0.00</div></div>'}</div>
        </div>
        <div style="font-size:11px;color:#666;margin-top:8px;">Disbursement records include historical disbursed loans in the selected report period; Loan Book and PAR reports include active loan accounts only.</div>
        <div class="filters-used">
            <h4>Filters Used</h4>
            <div class="filter-grid">${filtersHtml}</div>
        </div>
    </div>
    <div class="action">
        <span>Export table to:</span>
        <button onclick="exportData('xlsx')">Excel (xlsx)</button>
        <button onclick="exportData('xls')">Excel (xls)</button>
        <button onclick="exportData('csv')">CSV</button>
    </div>
    <div class="table-wrap">
        <table id="data-table">
            <thead>
                <tr>
                    <th>Disbursement Date</th>
                    <th>Branch</th>
                    <th>Loan No.</th>
                    <th>Client Name</th>
                    <th>Product</th>
                    <th>Loan Officer</th>
                    <th>Approved Amount</th>
                    <th>Disbursed Amount</th>
                    <th>Interest Rate</th>
                    <th>Term (Months)</th>
                    <th>Repayment Start</th>
                    <th>Maturity Date</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>${bodyRows || '<tr><td colspan="14" class="text-center">No disbursed loans found for the selected filters.</td></tr>'}</tbody>
        </table>
    </div>
</body>
</html>`;
}

module.exports = {
    generateLoanDisbursementReport,
    generateLoanDisbursementHTML,
    normalizeDisbursementMethod,
};
