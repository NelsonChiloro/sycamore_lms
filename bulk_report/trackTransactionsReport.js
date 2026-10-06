const moment = require('moment');
const util = require('util');

function normalizeFilterOptions(filterOptions = {}) {
    return {
        from: typeof filterOptions.from === 'string' ? filterOptions.from.trim() : '',
        to: typeof filterOptions.to === 'string' ? filterOptions.to.trim() : '',
        customer_name: typeof filterOptions.customer_name === 'string' ? filterOptions.customer_name.trim() : '',
        loan_number: typeof filterOptions.loan_number === 'string' ? filterOptions.loan_number.trim() : '',
        transaction_type: typeof filterOptions.transaction_type === 'string' ? filterOptions.transaction_type.trim() : '',
    };
}

function hasActiveFilters(filterOptions) {
    return Object.values(filterOptions).some((value) => value !== '');
}

async function generateTrackTransactionsReport(filterOptions, reportId, reportTrackers, db) {
    reportTrackers[reportId].percentage = 5;

    const normalizedFilterOptions = normalizeFilterOptions(filterOptions);

    const result = await getTrackTransactionsData(normalizedFilterOptions, reportId, reportTrackers, db);
    const html = generateHtml(result.transactions, normalizedFilterOptions);

    reportTrackers[reportId].percentage = 100;
    return html;
}

async function getTrackTransactionsData(filterOptions, reportId, reportTrackers, db) {
    const queryAsync = util.promisify(db.query).bind(db);
    const normalizedFilterOptions = normalizeFilterOptions(filterOptions);
    const applyFilters = hasActiveFilters(normalizedFilterOptions);
    const fields = await queryAsync('SHOW COLUMNS FROM transactions');
    const fieldNames = new Set(fields.map((field) => field.Field));

    const referenceColumn = fieldNames.has('payment_reference') ? 'payment_reference' : (fieldNames.has('reference') ? 'reference' : '');
    const paymentTypeColumn = fieldNames.has('payment_type') ? 'payment_type' : (fieldNames.has('method') ? 'method' : '');

    const paymentReferenceSelect = referenceColumn !== ''
        ? `CASE WHEN t.${referenceColumn} IS NOT NULL AND TRIM(t.${referenceColumn}) <> '' THEN t.${referenceColumn} ELSE '' END AS payment_reference_value`
        : `'' AS payment_reference_value`;

    let paymentTypeSelect = `'' AS payment_type_value`;
    if (paymentTypeColumn === 'method') {
        paymentTypeSelect = `CASE
            WHEN t.method = 1 THEN 'Bank'
            WHEN t.method = 0 THEN ''
            ELSE ''
        END AS payment_type_value`;
    } else if (paymentTypeColumn !== '') {
        paymentTypeSelect = `CASE
            WHEN t.${paymentTypeColumn} IS NULL OR TRIM(t.${paymentTypeColumn}) = '' THEN ''
            ELSE CONCAT(UCASE(LEFT(TRIM(t.${paymentTypeColumn}), 1)), LCASE(SUBSTRING(TRIM(t.${paymentTypeColumn}), 2)))
        END AS payment_type_value`;
    }

    let sql = `
        SELECT
            t.transaction_id,
            t.transaction_type,
            t.ref,
            t.loan_id,
            t.amount,
            t.payment_number,
            CASE
                WHEN t.date_stamp IS NULL OR t.date_stamp = '0000-00-00 00:00:00' OR t.date_stamp = '0000-00-00' THEN NULL
                ELSE t.date_stamp
            END AS display_date_stamp,
            IFNULL(tt.name, CONCAT('Type ', t.transaction_type)) AS transaction_type_name,
            ${paymentReferenceSelect},
            ${paymentTypeSelect},
            COALESCE(
                l.loan_number,
                (
                    SELECT l2.loan_number
                    FROM loan l2
                    WHERE l2.loan_customer = t.loan_id OR l2.group_id = t.loan_id
                    ORDER BY l2.loan_id DESC
                    LIMIT 1
                ),
                CAST(t.loan_id AS CHAR)
            ) AS loan_number,
            CASE
                WHEN g.group_id IS NOT NULL THEN CONCAT(g.group_name, ' (', g.group_code, ')')
                WHEN ic.id IS NOT NULL THEN CONCAT(ic.Firstname, ' ', ic.Lastname)
                WHEN lic.id IS NOT NULL THEN CONCAT(lic.Firstname, ' ', lic.Lastname)
                WHEN lg.group_id IS NOT NULL THEN CONCAT(lg.group_name, ' (', lg.group_code, ')')
                ELSE ''
            END AS customer_name,
            CONCAT_WS(' ', e.Firstname, e.Lastname) AS added_by_name
        FROM transactions t
        LEFT JOIN loan l ON l.loan_id = t.loan_id
        LEFT JOIN transaction_type tt ON tt.transaction_type_id = t.transaction_type
        LEFT JOIN \`groups\` g ON g.group_id = l.group_id
        LEFT JOIN individual_customers ic ON ic.id = l.loan_customer
        LEFT JOIN individual_customers lic ON lic.id = t.loan_id
        LEFT JOIN \`groups\` lg ON lg.group_id = t.loan_id
        LEFT JOIN employees e ON e.id = t.added_by
        WHERE 1 = 1
    `;

    const params = [];

    if (applyFilters && normalizedFilterOptions.from) {
        sql += ' AND DATE(t.date_stamp) >= ?';
        params.push(normalizedFilterOptions.from);
    }

    if (applyFilters && normalizedFilterOptions.to) {
        sql += ' AND DATE(t.date_stamp) <= ?';
        params.push(normalizedFilterOptions.to);
    }

    if (applyFilters && normalizedFilterOptions.loan_number) {
        sql += ' AND (l.loan_number LIKE ? OR CAST(t.loan_id AS CHAR) LIKE ?)';
        params.push(`%${normalizedFilterOptions.loan_number}%`, `%${normalizedFilterOptions.loan_number}%`);
    }

    if (applyFilters && normalizedFilterOptions.transaction_type) {
        sql += ' AND t.transaction_type = ?';
        params.push(normalizedFilterOptions.transaction_type);
    }

    if (applyFilters && normalizedFilterOptions.customer_name) {
        sql += `
            AND (
                g.group_name LIKE ? OR
                g.group_code LIKE ? OR
                ic.Firstname LIKE ? OR
                ic.Lastname LIKE ? OR
                CONCAT(ic.Firstname, ' ', ic.Lastname) LIKE ? OR
                lg.group_name LIKE ? OR
                lg.group_code LIKE ? OR
                lic.Firstname LIKE ? OR
                lic.Lastname LIKE ? OR
                CONCAT(lic.Firstname, ' ', lic.Lastname) LIKE ?
            )
        `;
        const likeValue = `%${normalizedFilterOptions.customer_name}%`;
        params.push(likeValue, likeValue, likeValue, likeValue, likeValue, likeValue, likeValue, likeValue, likeValue, likeValue);
    }

    sql += ' ORDER BY t.date_stamp DESC, t.transaction_id DESC';

    reportTrackers[reportId].percentage = 20;
    const transactions = await queryAsync(sql, params);
    reportTrackers[reportId].percentage = 90;

    return { transactions };
}

function generateHtml(transactions, filterOptions) {
    const formatDate = (dateValue) => {
        if (!dateValue) {
            return '';
        }
        return moment(dateValue).format('YYYY-MM-DD HH:mm:ss');
    };

    const formatAmount = (amount) => {
        const numericAmount = parseFloat(amount || 0);
        return Number.isFinite(numericAmount) ? numericAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : amount;
    };

    const rows = transactions.map((transaction, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${formatDate(transaction.display_date_stamp) || '-'}</td>
            <td>${escapeHtml(transaction.transaction_type_name || '-')}</td>
            <td>${escapeHtml(formatAmount(transaction.amount))}</td>
            <td>${escapeHtml(transaction.ref || '-')}</td>
            <td>${escapeHtml(transaction.payment_type_value || '-')}</td>
            <td>${escapeHtml(transaction.payment_reference_value || '-')}</td>
            <td>${escapeHtml(transaction.customer_name || '-')}</td>
            <td>${escapeHtml(transaction.loan_number || '-')}</td>
            <td>${escapeHtml(transaction.added_by_name || '-')}</td>
        </tr>
    `).join('');

    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tracked Transactions Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
            .header h1 { color: #153505; margin-bottom: 4px; }
            .header p { margin: 4px 0; color: #666; }
            .card { border: 2px solid #153505; border-radius: 10px; padding: 18px; margin-bottom: 20px; }
            .filter-grid { display: grid; grid-template-columns: repeat(2, minmax(220px, 1fr)); gap: 8px 16px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
            th { background: #153505; color: white; }
            tr:nth-child(even) { background: #f7f7f7; }
            .no-records { padding: 16px; background: #eef6ee; border-radius: 8px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Tracked Transactions Report</h1>
            <p>Generated on ${moment().format('YYYY-MM-DD HH:mm:ss')}</p>
        </div>
        <div class="card">
            <h3>Filters</h3>
            <div class="filter-grid">
                <div><strong>From:</strong> ${escapeHtml(filterOptions.from || 'All')}</div>
                <div><strong>To:</strong> ${escapeHtml(filterOptions.to || 'All')}</div>
                <div><strong>Customer Name:</strong> ${escapeHtml(filterOptions.customer_name || 'All')}</div>
                <div><strong>Loan Number:</strong> ${escapeHtml(filterOptions.loan_number || 'All')}</div>
                <div><strong>Transaction Type:</strong> ${escapeHtml(filterOptions.transaction_type || 'All')}</div>
                <div><strong>Total Rows:</strong> ${transactions.length}</div>
            </div>
        </div>
        ${transactions.length === 0 ? '<div class="no-records">No transactions found for the selected filters.</div>' : `
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Transaction Type</th>
                        <th>Amount</th>
                        <th>Transaction Reference</th>
                        <th>Payment Type</th>
                        <th>Payment Reference</th>
                        <th>Customer</th>
                        <th>Loan Number</th>
                        <th>Added By</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `}
    </body>
    </html>`;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

module.exports = {
    generateTrackTransactionsReport,
};