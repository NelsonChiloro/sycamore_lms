const fs = require('fs');
const path = require('path');
const { getConnection } = require('./databaseHelpers');

const logFile = path.join(__dirname, `repair_loan_schedules_${new Date().toISOString().replace(/[:.]/g, '-')}.log`);
const statusFile = path.join(__dirname, 'loan_payment_repair_status.json');

function log(msg) {
    const timestamp = new Date().toISOString();
    const logMsg = `[${timestamp}] ${msg}`;
    console.log(logMsg);
    fs.appendFileSync(logFile, logMsg + '\n');
}

function writeStatus(status) {
    fs.writeFileSync(statusFile, JSON.stringify({
        updated_at: new Date().toISOString(),
        log_file: logFile,
        ...status,
    }, null, 2));
}

function readStatus() {
    if (!fs.existsSync(statusFile)) {
        return {
            status: 'idle',
            message: 'No repair job has been started yet.',
        };
    }

    return JSON.parse(fs.readFileSync(statusFile, 'utf8'));
}

async function repairLoanPaymentState(connection, loanId) {
    try {
        // Get all schedules for this loan
        const [schedules] = await connection.query(
            `SELECT id, amount, payment_number 
             FROM payement_schedules 
             WHERE loan_id = ? 
             ORDER BY payment_number ASC`,
            [loanId]
        );

        if (schedules.length === 0) {
            return { error: 'No schedules found', loan_id: loanId };
        }

        let totalScheduleAmount = 0;
        schedules.forEach(s => {
            totalScheduleAmount += parseFloat(s.amount) || 0;
        });

        // Get transaction total (MAX per ref to avoid cumulative duplicates)
        const [[txnRow]] = await connection.query(
            `SELECT COALESCE(SUM(max_per_ref), 0) AS total_paid,
                    MAX(date_stamp) AS last_date
             FROM (
                SELECT ref, MAX(amount) AS max_per_ref, MAX(date_stamp) AS date_stamp
                FROM transactions
                WHERE loan_id = ? AND transaction_type = 3 AND amount > 0
                GROUP BY ref
             ) AS unique_refs`,
            [loanId]
        );

        const rawTotal = parseFloat(txnRow.total_paid) || 0;
        const toApply = Math.min(rawTotal, totalScheduleAmount);
        
        const fallbackDate = txnRow.last_date && txnRow.last_date !== '0000-00-00' 
            ? new Date(txnRow.last_date).toISOString().split('T')[0]
            : new Date().toISOString().split('T')[0];

        // Step 1: Reset all schedules to NOT PAID
        await connection.query(
            `UPDATE payement_schedules 
             SET paid_amount = 0, paid_date = NULL, status = 'NOT PAID', partial_paid = 'NO' 
             WHERE loan_id = ?`,
            [loanId]
        );

        // Step 2: Apply sequentially from earliest installment
        let remaining = toApply;
        for (const schedule of schedules) {
            if (remaining <= 0.001) break;

            const scheduleAmount = parseFloat(schedule.amount) || 0;
            if (scheduleAmount <= 0) continue;

            const applyNow = Math.min(scheduleAmount, remaining);
            const isFullyPaid = (applyNow + 0.0001) >= scheduleAmount;

            await connection.query(
                `UPDATE payement_schedules 
                 SET paid_amount = ?, paid_date = ?, status = ?, partial_paid = ? 
                 WHERE id = ?`,
                [
                    applyNow,
                    fallbackDate,
                    isFullyPaid ? 'PAID' : 'PARTIAL PAID',
                    isFullyPaid ? 'NO' : 'YES',
                    schedule.id
                ]
            );

            remaining -= applyNow;
        }

        // Step 3: Update next_payment_id
        const [[nextPayment]] = await connection.query(
            `SELECT payment_number FROM payement_schedules 
             WHERE loan_id = ? AND status != 'PAID' 
             ORDER BY payment_number ASC LIMIT 1`,
            [loanId]
        );

        let nextPaymentId;
        if (nextPayment) {
            nextPaymentId = nextPayment.payment_number;
        } else {
            const [[lastSchedule]] = await connection.query(
                `SELECT MAX(payment_number) AS max_payment FROM payement_schedules WHERE loan_id = ?`,
                [loanId]
            );
            nextPaymentId = (lastSchedule && lastSchedule.max_payment) ? (lastSchedule.max_payment + 1) : 1;
        }

        await connection.query(
            `UPDATE loan SET next_payment_id = ? WHERE loan_id = ?`,
            [nextPaymentId, loanId]
        );

        return {
            loan_id: loanId,
            total_txn_paid: rawTotal,
            total_schedule_amount: totalScheduleAmount,
            applied: toApply,
            status: 'REPAIRED'
        };
    } catch (error) {
        log(`ERROR repairing loan ${loanId}: ${error.message}`);
        return { error: error.message, loan_id: loanId };
    }
}

async function main() {
    const connection = await getConnection();

    try {
        log('='.repeat(80));
        log('STARTING LOAN PAYMENT REPAIR PROCESS');
        log('='.repeat(80));
        const [[dbMeta]] = await connection.query('SELECT DATABASE() AS db_name, CURRENT_USER() AS current_user');
        log(`Database: ${dbMeta.current_user}@${dbMeta.db_name}`);
        writeStatus({
            status: 'running',
            started_at: new Date().toISOString(),
            progress: {
                total_mismatched: 0,
                repaired_count: 0,
                errors_count: 0,
                current_loan_number: null,
                current_index: 0,
            },
            message: 'Scanning for mismatched loans...',
        });

        // Find all mismatched loans
        log('\nScanning for mismatched loans...');
        
        const [mismatchedLoans] = await connection.query(
            `SELECT
                l.loan_id,
                l.loan_number,
                l.loan_status,
                COALESCE(SUM(ps.amount), 0) AS total_schedule_amount,
                COALESCE(SUM(ps.paid_amount), 0) AS total_schedule_paid,
                COALESCE(SUM(t_max.max_amount), 0) AS total_txn_paid
             FROM loan l
             LEFT JOIN payement_schedules ps ON ps.loan_id = l.loan_id
             LEFT JOIN (
                SELECT loan_id, SUM(max_per_ref) AS max_amount
                FROM (
                    SELECT loan_id, ref, MAX(amount) AS max_per_ref
                    FROM transactions
                    WHERE transaction_type = 3
                    GROUP BY loan_id, ref
                ) AS unique_refs
                GROUP BY loan_id
             ) t_max ON t_max.loan_id = l.loan_id
             WHERE l.loan_status IN ('ACTIVE', 'CLOSED')
             GROUP BY l.loan_id, l.loan_number, l.loan_status
             HAVING COALESCE(SUM(t_max.max_amount), 0) > COALESCE(SUM(ps.paid_amount), 0.01)
             ORDER BY l.loan_id`
        );

        log(`\nFound ${mismatchedLoans.length} mismatched loans. Starting repairs...\n`);
        writeStatus({
            status: 'running',
            started_at: new Date().toISOString(),
            progress: {
                total_mismatched: mismatchedLoans.length,
                repaired_count: 0,
                errors_count: 0,
                current_loan_number: null,
                current_index: 0,
            },
            message: `Found ${mismatchedLoans.length} mismatched loans. Starting repairs.`,
        });

        const summary = {
            timestamp: new Date().toISOString(),
            total_mismatched: mismatchedLoans.length,
            repaired_count: 0,
            errors_count: 0,
            total_txn_diff: 0,
            details: []
        };

        for (let i = 0; i < mismatchedLoans.length; i++) {
            const loan = mismatchedLoans[i];
            process.stdout.write(`\rRepairing loan ${i + 1}/${mismatchedLoans.length} (${loan.loan_number})...`);
            writeStatus({
                status: 'running',
                started_at: summary.timestamp,
                progress: {
                    total_mismatched: mismatchedLoans.length,
                    repaired_count: summary.repaired_count,
                    errors_count: summary.errors_count,
                    current_loan_number: loan.loan_number,
                    current_index: i + 1,
                },
                message: `Repairing loan ${i + 1}/${mismatchedLoans.length} (${loan.loan_number})`,
            });

            const result = await repairLoanPaymentState(connection, loan.loan_id);

            if (result.error) {
                summary.errors_count++;
                summary.details.push({
                    loan_id: loan.loan_id,
                    loan_number: loan.loan_number,
                    status: 'ERROR',
                    message: result.error
                });
            } else {
                summary.repaired_count++;
                const diff = result.total_txn_paid - loan.total_schedule_paid;
                summary.total_txn_diff += diff;

                summary.details.push({
                    loan_id: loan.loan_id,
                    loan_number: loan.loan_number,
                    loan_status: loan.loan_status,
                    status: 'REPAIRED',
                    txn_total: Math.round(result.total_txn_paid * 100) / 100,
                    schedule_amount: Math.round(result.total_schedule_amount * 100) / 100,
                    applied: Math.round(result.applied * 100) / 100,
                    diff: Math.round(diff * 100) / 100
                });
            }

            writeStatus({
                status: 'running',
                started_at: summary.timestamp,
                progress: {
                    total_mismatched: mismatchedLoans.length,
                    repaired_count: summary.repaired_count,
                    errors_count: summary.errors_count,
                    current_loan_number: loan.loan_number,
                    current_index: i + 1,
                },
                message: `Processed loan ${i + 1}/${mismatchedLoans.length} (${loan.loan_number})`,
            });
        }

        console.log('\n');
        log('\n' + '='.repeat(80));
        log('REPAIR SUMMARY');
        log('='.repeat(80));
        log(`Total Found: ${summary.total_mismatched}`);
        log(`Successfully Repaired: ${summary.repaired_count}`);
        log(`Errors: ${summary.errors_count}`);
        log(`Total Amount Adjusted: MWK ${(Math.round(summary.total_txn_diff * 100) / 100).toFixed(2)}`);

        // Write JSON report
        const reportFile = path.join(__dirname, `repair_loan_schedules_${new Date().toISOString().replace(/[:.]/g, '-')}.json`);
        fs.writeFileSync(reportFile, JSON.stringify(summary, null, 2));
        log(`\nDetailed report saved to: ${reportFile}`);

        // Log first 10 and last 10 repairs for verification
        log('\nFirst 10 Repairs:');
        summary.details.slice(0, 10).forEach((detail, idx) => {
            log(`  ${idx + 1}. ${detail.loan_number} - ${detail.status} - MWK ${detail.diff || 0}`);
        });

        if (summary.details.length > 20) {
            log('  ...');
            log('\nLast 10 Repairs:');
            summary.details.slice(-10).forEach((detail, idx) => {
                log(`  ${summary.details.length - 9 + idx}. ${detail.loan_number} - ${detail.status} - MWK ${detail.diff || 0}`);
            });
        }

        log('\n' + '='.repeat(80));
        log('REPAIR PROCESS COMPLETED');
        log('='.repeat(80));
        writeStatus({
            status: 'completed',
            started_at: summary.timestamp,
            completed_at: new Date().toISOString(),
            report_file: reportFile,
            summary: {
                total_mismatched: summary.total_mismatched,
                repaired_count: summary.repaired_count,
                errors_count: summary.errors_count,
                total_txn_diff: Math.round(summary.total_txn_diff * 100) / 100,
            },
            message: 'Loan payment repair completed successfully.',
        });

    } catch (error) {
        log(`FATAL ERROR: ${error.message}`);
        log(error.stack);
        writeStatus({
            status: 'failed',
            completed_at: new Date().toISOString(),
            message: error.message,
        });
    } finally {
        connection.release();
    }
}

async function runRepairJob() {
    const currentStatus = readStatus();
    if (currentStatus.status === 'running') {
        return {
            started: false,
            message: 'A repair job is already running.',
            status: currentStatus,
        };
    }

    main().catch((error) => {
        log(`UNHANDLED ERROR: ${error.message}`);
        writeStatus({
            status: 'failed',
            completed_at: new Date().toISOString(),
            message: error.message,
        });
    });

    return {
        started: true,
        message: 'Loan payment repair job started.',
        status: readStatus(),
    };
}

module.exports = {
    runRepairJob,
    readStatus,
    statusFile,
};

if (require.main === module) {
    runRepairJob().catch(console.error);
}
