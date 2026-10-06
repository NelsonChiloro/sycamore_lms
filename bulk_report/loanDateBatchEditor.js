const EPSILON = 0.0001;
const MS_PER_DAY = 24 * 60 * 60 * 1000;

function normalizeNumber(value) {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
    }

    if (value === null || value === undefined) {
        return 0;
    }

    const sanitized = String(value).trim().replace(/,/g, '').replace(/\s+/g, '');
    if (!sanitized) {
        return 0;
    }

    const numeric = Number(sanitized.replace(/[^0-9.-]/g, ''));
    return Number.isFinite(numeric) ? numeric : 0;
}

function roundCurrency(value) {
    return Math.round((normalizeNumber(value) + Number.EPSILON) * 100) / 100;
}

function normalizeFrequencyLabel(frequency) {
    const raw = String(frequency || '').trim();
    const normalized = raw.toLowerCase().replace(/\s+/g, ' ');

    if (
        normalized === 'bi weekly' ||
        normalized === 'bi-weekly' ||
        normalized === 'biweekly' ||
        normalized === '2 weeks' ||
        normalized === '2 week' ||
        normalized === 'fortnight' ||
        normalized === 'fortnightly'
    ) {
        return 'Bi weekly';
    }
    if (normalized === 'weekly') {
        return 'Weekly';
    }
    if (normalized === 'monthly') {
        return 'Monthly';
    }

    return raw;
}

function parseLocalDateParts(year, month, day) {
    const parsedYear = Number(year);
    const parsedMonth = Number(month);
    const parsedDay = Number(day);
    const date = new Date(parsedYear, parsedMonth - 1, parsedDay);

    if (
        Number.isNaN(date.getTime()) ||
        date.getFullYear() !== parsedYear ||
        date.getMonth() !== parsedMonth - 1 ||
        date.getDate() !== parsedDay
    ) {
        throw new Error('Invalid calendar date provided.');
    }

    date.setHours(0, 0, 0, 0);
    return date;
}

function parseDate(value) {
    if (value instanceof Date) {
        const normalizedDate = new Date(value.getTime());
        normalizedDate.setHours(0, 0, 0, 0);
        if (Number.isNaN(normalizedDate.getTime())) {
            throw new Error('Invalid calendar date provided.');
        }
        return normalizedDate;
    }

    const raw = String(value || '').trim();
    if (!raw) {
        throw new Error('Loan date is required.');
    }

    let match = raw.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (match) {
        return parseLocalDateParts(match[1], match[2], match[3]);
    }

    match = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (match) {
        const [first, second, year] = [Number(match[1]), Number(match[2]), Number(match[3])];
        if (first > 12) {
            return parseLocalDateParts(year, second, first);
        }
        return parseLocalDateParts(year, first, second);
    }

    match = raw.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
    if (match) {
        const [first, second, year] = [Number(match[1]), Number(match[2]), Number(match[3])];
        if (first > 12) {
            return parseLocalDateParts(year, second, first);
        }
        return parseLocalDateParts(year, first, second);
    }

    throw new Error(`Unsupported loan date format: ${raw}`);
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function addDays(date, days) {
    const updated = new Date(date.getTime());
    updated.setDate(updated.getDate() + days);
    return updated;
}

function addMonths(date, months) {
    const updated = new Date(date.getTime());
    updated.setMonth(updated.getMonth() + months);
    return updated;
}

function getMonthEndDate(date) {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0);
}

function buildMonthEndScheduleDate(baseDateString, offsetMonths = 0) {
    const baseDate = parseDate(baseDateString);
    const baseDay = baseDate.getDate();
    let effectiveOffset = Number(offsetMonths) || 0;

    if (baseDay > 15) {
        effectiveOffset += 1;
    }

    return formatDate(getMonthEndDate(new Date(baseDate.getFullYear(), baseDate.getMonth() + effectiveOffset, 1)));
}

function daysBetween(earlierDate, laterDate) {
    return Math.round((laterDate.getTime() - earlierDate.getTime()) / MS_PER_DAY);
}

function computeEqualInstallment(principal, totalDeduction, periods) {
    if (periods <= 0) {
        throw new Error('Loan period must be greater than zero.');
    }

    const periodicRate = totalDeduction / 12;
    if (Math.abs(periodicRate) < Number.EPSILON) {
        return principal / periods;
    }

    const factor = Math.pow(1 + periodicRate, periods);
    return principal * periodicRate * factor / (factor - 1);
}

function resolveEffectiveFrequency(existingLoan, product) {
    const stored = normalizeFrequencyLabel(existingLoan.period_type);
    if (stored === 'Monthly' || stored === 'Weekly' || stored === 'Bi weekly') {
        return stored;
    }
    return normalizeFrequencyLabel(product.frequency);
}

function normalizeSchedulePlan(schedulePlan) {
    return String(schedulePlan || '').trim().toLowerCase();
}

async function captureSchedulePaymentState(connection, loanId) {
    const [rows] = await connection.query(
        `SELECT payment_number, paid_amount, paid_date, status, partial_paid
         FROM payement_schedules
         WHERE loan_id = ?
         ORDER BY payment_number ASC`,
        [loanId]
    );

    const stateByPaymentNumber = new Map();
    for (const row of rows) {
        stateByPaymentNumber.set(Number(row.payment_number), {
            paid_amount: normalizeNumber(row.paid_amount),
            paid_date: row.paid_date,
            status: String(row.status || 'NOT PAID').trim().toUpperCase(),
            partial_paid: String(row.partial_paid || 'NO').trim().toUpperCase(),
        });
    }

    return stateByPaymentNumber;
}

async function loadExistingSchedules(connection, loanId) {
    const [rows] = await connection.query(
        `SELECT payment_number, payment_schedule, amount, principal, interest, padmin_fee, ploan_cover, loan_balance, loan_date
         FROM payement_schedules
         WHERE loan_id = ?
         ORDER BY payment_number ASC`,
        [loanId]
    );

    return rows.map((row) => ({
        payment_number: Number(row.payment_number),
        payment_schedule: row.payment_schedule,
        amount: roundCurrency(row.amount),
        principal: roundCurrency(row.principal),
        interest: roundCurrency(row.interest),
        padmin_fee: roundCurrency(row.padmin_fee),
        ploan_cover: roundCurrency(row.ploan_cover),
        loan_balance: roundCurrency(row.loan_balance),
        loan_date: formatDate(parseDate(row.loan_date)),
    }));
}

function normalizeGeneratedSchedules(schedules) {
    return schedules.map((schedule) => ({
        payment_number: Number(schedule.payment_number),
        payment_schedule: schedule.payment_schedule,
        amount: roundCurrency(schedule.amount),
        principal: roundCurrency(schedule.principal),
        interest: roundCurrency(schedule.interest),
        padmin_fee: roundCurrency(schedule.padmin_fee),
        ploan_cover: roundCurrency(schedule.ploan_cover),
        loan_balance: roundCurrency(schedule.loan_balance),
        loan_date: schedule.loan_date,
    }));
}

function applyDisbursedDateShiftToSchedules(schedules, loanDate, disbursedDate) {
    if (!disbursedDate) {
        return schedules;
    }

    const loanDateValue = parseDate(loanDate);
    const disbursedDateValue = parseDate(disbursedDate);
    const daysDiff = daysBetween(loanDateValue, disbursedDateValue);

    if (Math.abs(daysDiff) < 1) {
        return schedules;
    }

    return schedules.map((schedule) => {
        const shiftedDate = addDays(parseDate(schedule.payment_schedule), daysDiff);
        if (shiftedDate.getDay() === 0) {
            shiftedDate.setDate(shiftedDate.getDate() + 1);
        }

        return {
            ...schedule,
            payment_schedule: formatDate(shiftedDate),
        };
    });
}

function schedulesMatch(existingSchedules, generatedSchedules) {
    if (existingSchedules.length !== generatedSchedules.length) {
        return false;
    }

    for (let index = 0; index < existingSchedules.length; index += 1) {
        const existing = existingSchedules[index];
        const generated = generatedSchedules[index];

        if (
            existing.payment_number !== generated.payment_number ||
            String(existing.payment_schedule || '') !== String(generated.payment_schedule || '') ||
            roundCurrency(existing.amount) !== roundCurrency(generated.amount) ||
            roundCurrency(existing.principal) !== roundCurrency(generated.principal) ||
            roundCurrency(existing.interest) !== roundCurrency(generated.interest) ||
            roundCurrency(existing.padmin_fee) !== roundCurrency(generated.padmin_fee) ||
            roundCurrency(existing.ploan_cover) !== roundCurrency(generated.ploan_cover) ||
            roundCurrency(existing.loan_balance) !== roundCurrency(generated.loan_balance) ||
            String(existing.loan_date || '') !== String(generated.loan_date || '')
        ) {
            return false;
        }
    }

    return true;
}

function loanStateMatches(existingLoan, updatedLoan) {
    return roundCurrency(existingLoan.loan_amount_term) === roundCurrency(updatedLoan.loan_amount_term)
        && roundCurrency(existingLoan.loan_interest_amount) === roundCurrency(updatedLoan.loan_interest_amount)
        && roundCurrency(existingLoan.admin_fees_amount) === roundCurrency(updatedLoan.admin_fees_amount)
        && roundCurrency(existingLoan.loan_cover_amount) === roundCurrency(updatedLoan.loan_cover_amount)
        && roundCurrency(existingLoan.loan_amount_total) === roundCurrency(updatedLoan.loan_amount_total)
        && String(existingLoan.period_type || '').trim() === String(updatedLoan.period_type || '').trim()
        && formatDate(parseDate(existingLoan.loan_date)) === formatDate(parseDate(updatedLoan.loan_date));
}

async function restoreSchedulePaymentState(connection, loanId, stateByPaymentNumber) {
    const [schedules] = await connection.query(
        `SELECT id, payment_number, amount
         FROM payement_schedules
         WHERE loan_id = ?
         ORDER BY payment_number ASC`,
        [loanId]
    );

    for (const schedule of schedules) {
        const snapshot = stateByPaymentNumber.get(Number(schedule.payment_number));
        const updateData = {
            paid_amount: 0,
            paid_date: null,
            status: 'NOT PAID',
            partial_paid: 'NO',
        };

        if (snapshot) {
            const paidAmount = normalizeNumber(snapshot.paid_amount);
            const previousStatus = snapshot.status || 'NOT PAID';
            const isPaid = previousStatus === 'PAID';
            const isPartial = previousStatus === 'PARTIAL PAID' || (!isPaid && paidAmount > 0);

            if (isPaid || isPartial) {
                updateData.paid_amount = paidAmount;

                if (snapshot.paid_date && snapshot.paid_date !== '0000-00-00' && snapshot.paid_date !== '0000-00-00 00:00:00') {
                    updateData.paid_date = snapshot.paid_date;
                }

                if (isPaid || paidAmount + EPSILON >= normalizeNumber(schedule.amount)) {
                    updateData.status = 'PAID';
                    updateData.partial_paid = 'NO';
                } else {
                    updateData.status = 'PARTIAL PAID';
                    updateData.partial_paid = 'YES';
                }
            }
        }

        await connection.query(
            `UPDATE payement_schedules
             SET paid_amount = ?, paid_date = ?, status = ?, partial_paid = ?
             WHERE id = ?`,
            [updateData.paid_amount, updateData.paid_date, updateData.status, updateData.partial_paid, schedule.id]
        );
    }

    const [nextPaymentRows] = await connection.query(
        `SELECT payment_number
         FROM payement_schedules
         WHERE loan_id = ? AND status != 'PAID'
         ORDER BY payment_number ASC
         LIMIT 1`,
        [loanId]
    );

    let nextPaymentId = 1;
    if (nextPaymentRows.length > 0) {
        nextPaymentId = Number(nextPaymentRows[0].payment_number);
    } else {
        const [lastScheduleRows] = await connection.query(
            `SELECT MAX(payment_number) AS payment_number
             FROM payement_schedules
             WHERE loan_id = ?`,
            [loanId]
        );
        nextPaymentId = Number(lastScheduleRows[0]?.payment_number || 0) + 1;
    }

    await connection.query('UPDATE loan SET next_payment_id = ? WHERE loan_id = ?', [nextPaymentId, loanId]);
    return nextPaymentId;
}

async function shiftSchedulesToDisbursedDate(connection, loanId, loanDate, disbursedDate) {
    if (!disbursedDate) {
        return;
    }

    const loanDateValue = parseDate(loanDate);
    const disbursedDateValue = parseDate(disbursedDate);
    const daysDiff = daysBetween(loanDateValue, disbursedDateValue);

    if (Math.abs(daysDiff) < 1) {
        return;
    }

    const [schedules] = await connection.query(
        `SELECT id, payment_schedule
         FROM payement_schedules
         WHERE loan_id = ?
         ORDER BY payment_number ASC`,
        [loanId]
    );

    for (const schedule of schedules) {
        const shiftedDate = addDays(parseDate(schedule.payment_schedule), daysDiff);
        if (shiftedDate.getDay() === 0) {
            shiftedDate.setDate(shiftedDate.getDate() + 1);
        }

        await connection.query(
            'UPDATE payement_schedules SET payment_schedule = ? WHERE id = ?',
            [formatDate(shiftedDate), schedule.id]
        );
    }
}

function getProcessingFeePercent(product) {
    return normalizeNumber(product.processing_fees || product.processing_fee);
}

function getProductRates(product) {
    return {
        interest: normalizeNumber(product.interest),
        adminFees: normalizeNumber(product.admin_fees),
        loanCover: normalizeNumber(product.loan_cover),
        processingFees: getProcessingFeePercent(product),
    };
}

async function recreateSchedules(connection, loanId, schedules) {
    await connection.query('DELETE FROM payement_schedules WHERE loan_id = ?', [loanId]);

    for (const schedule of schedules) {
        await connection.query(
            `INSERT INTO payement_schedules
            (customer, customer_type, loan_id, payment_schedule, payment_number, amount, principal, interest, padmin_fee, ploan_cover, paid_amount, loan_balance, loan_date, status, partial_paid)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'NOT PAID', 'NO')`,
            [
                schedule.customer,
                schedule.customer_type,
                loanId,
                schedule.payment_schedule,
                schedule.payment_number,
                roundCurrency(schedule.amount),
                roundCurrency(schedule.principal),
                roundCurrency(schedule.interest),
                roundCurrency(schedule.padmin_fee),
                roundCurrency(schedule.ploan_cover),
                0,
                roundCurrency(schedule.loan_balance),
                schedule.loan_date,
            ]
        );
    }
}

function calculateCutoffAdjustment(originalLoanDate, frequency, schedulePlan, amount, months, interestRate, loanCoverRate) {
    const inputDate = parseDate(originalLoanDate);
    const day = inputDate.getDate();
    const plan = normalizeSchedulePlan(schedulePlan);

    let effectiveLoanDate = formatDate(inputDate);
    let extraInterest = 0;
    let totalExtraInterest = 0;

    if (frequency !== 'Monthly' || plan !== 'cut off') {
        return {
            inputDay: day,
            effectiveLoanDate,
            extraInterest,
            totalExtraInterest,
        };
    }

    if (day > 15) {
        const nextMonthStart = addDays(getMonthEndDate(inputDate), 1);
        effectiveLoanDate = formatDate(nextMonthStart);
        const extraDays = daysBetween(inputDate, nextMonthStart);
        totalExtraInterest = (extraDays / 30) * amount * ((interestRate + loanCoverRate) / 100);
        extraInterest = totalExtraInterest / months;
    } else if (day >= 1 && day < 15) {
        const monthStart = new Date(inputDate.getFullYear(), inputDate.getMonth(), 1);
        effectiveLoanDate = formatDate(monthStart);
        const extraDays = daysBetween(monthStart, inputDate);
        totalExtraInterest = (extraDays / 30) * amount * ((interestRate + loanCoverRate) / 100);
        extraInterest = totalExtraInterest / months;
    }

    return {
        inputDay: day,
        effectiveLoanDate,
        extraInterest,
        totalExtraInterest,
    };
}

function buildGenericLoanUpdate(existingLoan, product, frequency, originalLoanDate) {
    const rates = getProductRates(product);
    const amount = normalizeNumber(existingLoan.loan_principal);
    const months = Number(existingLoan.loan_period);
    const schedulePlan = product.schedule_plan;
    const cutoff = calculateCutoffAdjustment(originalLoanDate, frequency, schedulePlan, amount, months, rates.interest, rates.loanCover);

    const annualInterest = (rates.interest / 100) * 12;
    const annualAdminFees = (rates.adminFees / 100) * 12;
    const annualLoanCover = (rates.loanCover / 100) * 12;
    const totalDeduction = annualInterest + annualAdminFees + annualLoanCover;

    let monthlyPayment = computeEqualInstallment(amount, totalDeduction, months);
    let monthlyPayment1 = monthlyPayment;
    const monthlyPaymentConfig = monthlyPayment;
    let currentBalance = amount;
    let currentBalance1 = amount;
    let totalInterest = 0;
    let totalInterest1 = 0;
    let totalAdminFees = 0;
    let totalAdminFees1 = 0;
    let totalLoanCover = 0;
    let totalLoanCover1 = 0;
    let towardsBalance1 = 0;

    while (currentBalance1 > EPSILON) {
        const towardsInterest1 = (annualInterest / 12) * currentBalance1;
        const towardsFees = (annualAdminFees / 12) * currentBalance1;
        const towardsLoanCover = (annualLoanCover / 12) * currentBalance1;

        if (monthlyPayment1 > currentBalance1) {
            monthlyPayment1 = currentBalance1 + towardsInterest1 + towardsFees + towardsLoanCover;
        }

        towardsBalance1 = monthlyPayment1 - (towardsInterest1 + towardsFees + towardsLoanCover);
        totalInterest1 += towardsInterest1;
        totalAdminFees += towardsFees;
        totalLoanCover += towardsLoanCover;
        currentBalance1 -= towardsBalance1;
    }

    let loanAmountTerm = monthlyPaymentConfig;
    let loanAmountTotal = totalInterest1 + amount + totalAdminFees + totalLoanCover;
    let storedInterestAmount = totalInterest1 + cutoff.totalExtraInterest;

    if (cutoff.inputDay > 15 && frequency === 'Monthly' && normalizeSchedulePlan(schedulePlan) === 'cut off') {
        loanAmountTerm = monthlyPaymentConfig + cutoff.extraInterest;
        loanAmountTotal += cutoff.totalExtraInterest;
        storedInterestAmount = totalInterest1 + cutoff.totalExtraInterest;
    } else if (cutoff.inputDay >= 1 && cutoff.inputDay < 15 && frequency === 'Monthly' && normalizeSchedulePlan(schedulePlan) === 'cut off') {
        loanAmountTerm = monthlyPaymentConfig - cutoff.extraInterest;
        loanAmountTotal -= cutoff.totalExtraInterest;
        storedInterestAmount = totalInterest1 - cutoff.totalExtraInterest;
    }

    const schedules = [];
    const baseDateForMonthly = cutoff.effectiveLoanDate;
    const isCutoff = frequency === 'Monthly' && normalizeSchedulePlan(schedulePlan) === 'cut off';
    const originalInputDay = parseDate(originalLoanDate).getDate();

    for (let paymentNumber = 1; paymentNumber <= months && currentBalance > EPSILON; paymentNumber += 1) {
        const towardsInterest = (annualInterest / 12) * currentBalance;
        const towardsFees = (annualAdminFees / 12) * currentBalance;
        const towardsLoanCover = (annualLoanCover / 12) * currentBalance;

        if (monthlyPayment > currentBalance) {
            monthlyPayment = currentBalance + towardsInterest + towardsFees + towardsLoanCover;
        }

        const towardsBalance = monthlyPayment - (towardsInterest + towardsFees + towardsLoanCover);
        totalInterest += towardsInterest;
        totalAdminFees1 += towardsFees;
        totalLoanCover1 += towardsLoanCover;
        currentBalance -= towardsBalance;

        let paymentSchedule;
        let amountDue = monthlyPayment + cutoff.extraInterest;
        let interestDue = towardsInterest + cutoff.extraInterest;

        if (frequency === 'Weekly') {
            const paymentDate = addDays(parseDate(cutoff.effectiveLoanDate), 7 * paymentNumber);
            if (paymentDate.getDay() === 0) {
                paymentDate.setDate(paymentDate.getDate() + 1);
            }
            paymentSchedule = formatDate(paymentDate);
        } else if (frequency === 'Bi weekly') {
            throw new Error('Bi-weekly schedules are generated in a dedicated branch.');
        } else if (isCutoff && cutoff.inputDay >= 1 && cutoff.inputDay < 15) {
            paymentSchedule = buildMonthEndScheduleDate(baseDateForMonthly, paymentNumber - 1);
            amountDue = monthlyPayment - cutoff.extraInterest;
            interestDue = towardsInterest - cutoff.extraInterest;
        } else if (!isCutoff && originalInputDay <= 15) {
            const paymentDate = addMonths(parseDate(originalLoanDate), paymentNumber);
            if (paymentDate.getDay() === 0) {
                paymentDate.setDate(paymentDate.getDate() + 1);
            }
            paymentSchedule = formatDate(paymentDate);
        } else {
            paymentSchedule = buildMonthEndScheduleDate(baseDateForMonthly, paymentNumber - 1);
        }

        schedules.push({
            customer: existingLoan.loan_customer,
            customer_type: existingLoan.customer_type,
            payment_schedule: paymentSchedule,
            payment_number: paymentNumber,
            amount: amountDue,
            principal: towardsBalance,
            interest: interestDue,
            padmin_fee: towardsFees,
            ploan_cover: towardsLoanCover,
            loan_balance: currentBalance < EPSILON ? 0 : currentBalance,
            loan_date: cutoff.effectiveLoanDate,
        });
    }

    const loanUpdate = {
        loan_date: cutoff.effectiveLoanDate,
        loan_principal: amount,
        loan_period: months,
        period_type: frequency,
        loan_amount_term: loanAmountTerm,
        loan_interest: rates.interest,
        loan_interest_amount: storedInterestAmount,
        admin_fee: rates.adminFees,
        admin_fees_amount: totalAdminFees,
        loan_cover: rates.loanCover,
        loan_cover_amount: totalLoanCover,
        loan_amount_total: loanAmountTotal,
    };

    return { loanUpdate, schedules, effectiveLoanDate: cutoff.effectiveLoanDate };
}

function buildBiWeeklySchedules(existingLoan, product, frequency, originalLoanDate) {
    const rates = getProductRates(product);
    const amount = normalizeNumber(existingLoan.loan_principal);
    const months = Number(existingLoan.loan_period);
    const cutoff = calculateCutoffAdjustment(originalLoanDate, frequency, product.schedule_plan, amount, months, rates.interest, rates.loanCover);
    const annualInterest = (rates.interest / 100) * 12;
    const annualAdminFees = (rates.adminFees / 100) * 12;
    const annualLoanCover = (rates.loanCover / 100) * 12;
    const totalDeduction = annualInterest + annualAdminFees + annualLoanCover;

    const monthlyPayment = computeEqualInstallment(amount, totalDeduction, months);
    let monthlyPayment1 = monthlyPayment;
    let currentBalance1 = amount;
    let totalInterest1 = 0;
    let totalAdminFees = 0;
    let totalLoanCover = 0;
    let towardsBalance1 = 0;

    while (currentBalance1 > EPSILON) {
        const towardsInterest1 = (annualInterest / 12) * currentBalance1;
        const towardsFees = (annualAdminFees / 12) * currentBalance1;
        const towardsLoanCover = (annualLoanCover / 12) * currentBalance1;

        if (monthlyPayment1 > currentBalance1) {
            monthlyPayment1 = currentBalance1 + towardsInterest1 + towardsFees + towardsLoanCover;
        }

        towardsBalance1 = monthlyPayment1 - (towardsInterest1 + towardsFees + towardsLoanCover);
        totalInterest1 += towardsInterest1;
        totalAdminFees += towardsFees;
        totalLoanCover += towardsLoanCover;
        currentBalance1 -= towardsBalance1;
    }

    let loanAmountTerm = monthlyPayment;
    let loanAmountTotal = totalInterest1 + amount + totalAdminFees + totalLoanCover;
    let storedInterestAmount = totalInterest1 + cutoff.totalExtraInterest;

    if (cutoff.inputDay > 15 && normalizeSchedulePlan(product.schedule_plan) === 'cut off') {
        loanAmountTerm += cutoff.extraInterest;
        loanAmountTotal += cutoff.totalExtraInterest;
        storedInterestAmount = totalInterest1 + cutoff.totalExtraInterest;
    } else if (cutoff.inputDay >= 1 && cutoff.inputDay < 15 && normalizeSchedulePlan(product.schedule_plan) === 'cut off') {
        loanAmountTerm -= cutoff.extraInterest;
        loanAmountTotal -= cutoff.totalExtraInterest;
        storedInterestAmount = totalInterest1 - cutoff.totalExtraInterest;
    }

    const baseDate = parseDate(cutoff.effectiveLoanDate);
    const loanMonth = baseDate.getMonth() + 1;
    const loanDay = baseDate.getDate();
    let startDay = 15;
    if (loanDay >= 15) {
        startDay = loanMonth === 2 ? 28 : 30;
    }

    let paymentDate = new Date(baseDate.getFullYear(), baseDate.getMonth(), startDay);
    const schedules = [];

    for (let paymentNumber = 1; paymentNumber <= months * 2; paymentNumber += 1) {
        schedules.push({
            customer: existingLoan.loan_customer,
            customer_type: existingLoan.customer_type,
            payment_schedule: formatDate(paymentDate),
            payment_number: paymentNumber,
            amount: monthlyPayment1,
            principal: towardsBalance1,
            interest: totalInterest1,
            padmin_fee: 0,
            ploan_cover: 0,
            loan_balance: currentBalance1 < EPSILON ? 0 : currentBalance1,
            loan_date: cutoff.effectiveLoanDate,
        });

        const day = paymentDate.getDate();
        if (day === 15) {
            paymentDate = new Date(paymentDate.getFullYear(), paymentDate.getMonth(), paymentDate.getMonth() + 1 === 2 ? 28 : 30);
        } else {
            const nextMonth = new Date(paymentDate.getFullYear(), paymentDate.getMonth() + 1, 15);
            paymentDate = nextMonth;
        }
    }

    const loanUpdate = {
        loan_date: cutoff.effectiveLoanDate,
        loan_principal: amount,
        loan_period: months,
        period_type: frequency,
        loan_amount_term: loanAmountTerm,
        loan_interest: rates.interest,
        loan_interest_amount: storedInterestAmount,
        admin_fee: rates.adminFees,
        admin_fees_amount: totalAdminFees,
        loan_cover: rates.loanCover,
        loan_cover_amount: totalLoanCover,
        loan_amount_total: loanAmountTotal,
    };

    return { loanUpdate, schedules, effectiveLoanDate: cutoff.effectiveLoanDate };
}

function buildStraightLineSchedules(existingLoan, product, frequency, originalLoanDate) {
    const rates = getProductRates(product);
    const originalPrincipal = normalizeNumber(existingLoan.loan_principal);
    const loanTerm = Number(existingLoan.loan_period);
    const financedAmount = originalPrincipal + ((rates.processingFees / 100) * originalPrincipal);
    const periodsPerYear = frequency === 'Monthly' ? 12 : (frequency === 'Bi weekly' ? 26 : 52);
    const intervalWeeks = frequency === 'Monthly' ? 0 : (frequency === 'Bi weekly' ? 2 : 1);
    const periodicInterestRate = (rates.interest / 100) / periodsPerYear;
    const periodicProcessingRate = (rates.processingFees / 100) / periodsPerYear;
    const paymentAmountProcessingFee = financedAmount * periodicProcessingRate;
    const weeklyAddend = paymentAmountProcessingFee / loanTerm;
    const paymentAmount = financedAmount / loanTerm;
    const totalInterestAmount = financedAmount * periodicInterestRate * loanTerm;
    const totalAmountDue = financedAmount + totalInterestAmount + paymentAmountProcessingFee;
    let accumulatedScheduledAmount = 0;

    let paymentDate = parseDate(originalLoanDate);
    if (frequency === 'Monthly') {
        paymentDate = parseDate(buildMonthEndScheduleDate(originalLoanDate, 0));
    } else {
        paymentDate = addDays(paymentDate, intervalWeeks * 7);
    }

    const schedules = [];
    for (let paymentNumber = 1; paymentNumber <= loanTerm; paymentNumber += 1) {
        if (frequency !== 'Monthly' && paymentDate.getDay() >= 6) {
            while (paymentDate.getDay() === 6 || paymentDate.getDay() === 0) {
                paymentDate = addDays(paymentDate, 1);
            }
        }

        const openingBalance = financedAmount - ((paymentNumber - 1) * paymentAmount);
        const interestPayment = openingBalance * periodicInterestRate;
        const principalPayment = paymentAmount - interestPayment;
        const loanBalance = Math.max(0, openingBalance - principalPayment);
        const scheduledAmount = paymentNumber === loanTerm
            ? Math.max(0, totalAmountDue - accumulatedScheduledAmount)
            : paymentAmount + weeklyAddend;
        accumulatedScheduledAmount += roundCurrency(scheduledAmount);

        schedules.push({
            customer: existingLoan.loan_customer,
            customer_type: existingLoan.customer_type,
            payment_schedule: formatDate(paymentDate),
            payment_number: paymentNumber,
            amount: scheduledAmount,
            principal: principalPayment,
            interest: interestPayment,
            padmin_fee: 0,
            ploan_cover: 0,
            loan_balance: loanBalance,
            loan_date: formatDate(parseDate(originalLoanDate)),
        });

        if (frequency === 'Monthly') {
            paymentDate = getMonthEndDate(new Date(paymentDate.getFullYear(), paymentDate.getMonth() + 1, 1));
        } else {
            paymentDate = addDays(paymentDate, intervalWeeks * 7);
        }
    }

    const loanUpdate = {
        loan_date: formatDate(parseDate(originalLoanDate)),
        loan_principal: originalPrincipal,
        loan_period: loanTerm,
        period_type: frequency,
        loan_amount_term: paymentAmount + weeklyAddend,
        loan_interest: rates.interest,
        loan_interest_amount: totalInterestAmount,
        admin_fee: rates.adminFees,
        admin_fees_amount: 0,
        loan_cover: rates.loanCover,
        loan_cover_amount: 0,
        loan_amount_total: financedAmount + totalInterestAmount + paymentAmountProcessingFee,
    };

    return { loanUpdate, schedules, effectiveLoanDate: loanUpdate.loan_date };
}

async function loadLoanForUpdate(connection, loanNumber) {
    const [rows] = await connection.query(
        `SELECT l.*, lp.loan_product_id, lp.method, lp.frequency, lp.schedule_plan, lp.interest, lp.admin_fees, lp.loan_cover, lp.processing_fees
         FROM loan l
         INNER JOIN loan_products lp ON lp.loan_product_id = l.loan_product
         WHERE l.loan_number = ?
         LIMIT 1`,
        [loanNumber]
    );

    if (rows.length === 0) {
        throw new Error('Loan was not found.');
    }

    return rows[0];
}

async function updateLoanDate(connection, item) {
    const loanNumber = String(item.loanNumber || '').trim();
    if (!loanNumber) {
        throw new Error('Loan number is required.');
    }

    const existingLoan = await loadLoanForUpdate(connection, loanNumber);
    const storedLoanDate = formatDate(parseDate(existingLoan.loan_date));
    const frequency = resolveEffectiveFrequency(existingLoan, existingLoan);
    const method = String(existingLoan.method || '').trim().toLowerCase();
    const existingSchedules = await loadExistingSchedules(connection, existingLoan.loan_id);
    const paymentState = await captureSchedulePaymentState(connection, existingLoan.loan_id);

    let generated;
    if (method === 'straight line' && (frequency === 'Weekly' || frequency === 'Bi weekly' || frequency === 'Monthly')) {
        generated = buildStraightLineSchedules(existingLoan, existingLoan, frequency, storedLoanDate);
    } else if (frequency === 'Bi weekly') {
        generated = buildBiWeeklySchedules(existingLoan, existingLoan, frequency, storedLoanDate);
    } else {
        generated = buildGenericLoanUpdate(existingLoan, existingLoan, frequency, storedLoanDate);
    }

    await connection.query(
        `UPDATE loan
         SET loan_date = ?, loan_principal = ?, loan_period = ?, period_type = ?, loan_amount_term = ?,
             loan_interest = ?, loan_interest_amount = ?, admin_fee = ?, admin_fees_amount = ?,
             loan_cover = ?, loan_cover_amount = ?, loan_amount_total = ?
         WHERE loan_id = ?`,
        [
            generated.loanUpdate.loan_date,
            roundCurrency(generated.loanUpdate.loan_principal),
            generated.loanUpdate.loan_period,
            generated.loanUpdate.period_type,
            roundCurrency(generated.loanUpdate.loan_amount_term),
            roundCurrency(generated.loanUpdate.loan_interest),
            roundCurrency(generated.loanUpdate.loan_interest_amount),
            roundCurrency(generated.loanUpdate.admin_fee),
            roundCurrency(generated.loanUpdate.admin_fees_amount),
            roundCurrency(generated.loanUpdate.loan_cover),
            roundCurrency(generated.loanUpdate.loan_cover_amount),
            roundCurrency(generated.loanUpdate.loan_amount_total),
            existingLoan.loan_id,
        ]
    );

    await recreateSchedules(connection, existingLoan.loan_id, generated.schedules);
    if (String(existingLoan.disbursed || '').trim().toLowerCase() === 'yes') {
        await shiftSchedulesToDisbursedDate(connection, existingLoan.loan_id, generated.loanUpdate.loan_date, existingLoan.disbursed_date);
    }
    const nextPaymentId = await restoreSchedulePaymentState(connection, existingLoan.loan_id, paymentState);
    const updatedLoan = await loadLoanForUpdate(connection, loanNumber);
    const updatedSchedules = await loadExistingSchedules(connection, existingLoan.loan_id);
    const unchanged = loanStateMatches(existingLoan, updatedLoan) && schedulesMatch(existingSchedules, updatedSchedules);

    return {
        loanId: existingLoan.loan_id,
        loanNumber,
        recordedLoanDate: storedLoanDate,
        effectiveLoanDate: generated.effectiveLoanDate,
        scheduleCount: generated.schedules.length,
        nextPaymentId,
        unchanged,
    };
}

function parseBatchEntries(entries) {
    const rows = String(entries || '')
        .split(/\r?\n/)
        .map((row) => row.trim())
        .filter(Boolean);

    return rows.map((row, index) => {
        const parts = row.split(/[\t,|]/).map((part) => part.trim()).filter(Boolean);
        if (parts.length < 1) {
            throw new Error(`Line ${index + 1} must contain a loan number.`);
        }

        return {
            loanNumber: parts[0],
        };
    });
}

function normalizeBatchItems(payload) {
    if (Array.isArray(payload?.items)) {
        return payload.items.map((item, index) => {
            if (!item || typeof item !== 'object') {
                throw new Error(`Item ${index + 1} is invalid.`);
            }

            return {
                loanNumber: item.loanNumber,
            };
        });
    }

    if (typeof payload?.entries === 'string' && payload.entries.trim()) {
        return parseBatchEntries(payload.entries);
    }

    throw new Error('Provide either an items array or pasted entries.');
}

async function processBatchLoanDateUpdate(connectionFactory, payload) {
    const items = normalizeBatchItems(payload);
    if (items.length === 0) {
        throw new Error('At least one loan update row is required.');
    }

    const results = [];
    const errors = [];

    for (const item of items) {
        const connection = await connectionFactory();
        try {
            await connection.beginTransaction();
            const result = await updateLoanDate(connection, item);
            await connection.commit();
            results.push(result);
        } catch (error) {
            await connection.rollback();
            errors.push({
                loanNumber: String(item.loanNumber || '').trim(),
                error: error.message,
            });
        } finally {
            connection.release();
        }
    }

    return {
        processed: items.length,
        successful: results.length,
        unchanged: results.filter((result) => result.unchanged).length,
        failed: errors.length,
        results,
        errors,
    };
}

module.exports = {
    processBatchLoanDateUpdate,
    parseBatchEntries,
};