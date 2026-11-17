'use strict';

function buildCrbFilters(input = {}) {
    const status = String(input.loan_status || 'ALL').toUpperCase();
    if (!['ALL', 'ACTIVE', 'PAID_UP', 'WRITTEN_OFF'].includes(status)) throw new Error('Invalid CRB loan status.');
    const conditions = ["loan.customer_type = 'individual'", "loan.disbursed = 'Yes'", "UPPER(loan.loan_status) NOT IN ('DELETED','REJECTED')"];
    const params = [];
    if (status === 'PAID_UP') {
        conditions.push("UPPER(loan.loan_status) IN ('ACTIVE','CLOSED','PAID_OFF','PAID UP','PAID-UP')");
        conditions.push('EXISTS (SELECT 1 FROM payement_schedules ps WHERE ps.loan_id=loan.loan_id AND ps.amount>0)');
        conditions.push(`NOT EXISTS (SELECT 1 FROM payement_schedules ps WHERE ps.loan_id=loan.loan_id
            AND (ROUND(GREATEST(COALESCE(ps.amount,0)-COALESCE(ps.paid_amount,0),0),2)>0
                 OR ROUND(GREATEST(COALESCE(ps.total_late_charge,0),0),2)>0))`);
    } else if (status !== 'ALL') {
        conditions.push('UPPER(loan.loan_status) = ?'); params.push(status);
    }
    for (const [key, column] of [['product', 'loan.loan_product'], ['branch', 'loan.branch']]) {
        const value = String(input[key] || '');
        if (!value) continue;
        if (!/^[1-9]\d*$/.test(value)) throw new Error(`Invalid CRB ${key}.`);
        conditions.push(`${column} = ?`); params.push(value);
    }
    for (const [key, operator] of [['date_from', '>='], ['date_to', '<=']]) {
        const value = String(input[key] || '');
        if (!value) continue;
        const date = new Date(`${value}T00:00:00Z`);
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value) || !Number.isFinite(date.getTime()) || date.toISOString().slice(0, 10) !== value) throw new Error('Invalid CRB loan date.');
        conditions.push(`loan.loan_date ${operator} ?`); params.push(value);
    }
    if (input.date_from && input.date_to && input.date_from > input.date_to) throw new Error('Loan date from must not exceed loan date to.');
    return { where: conditions.join(' AND '), params };
}

module.exports = { buildCrbFilters };
