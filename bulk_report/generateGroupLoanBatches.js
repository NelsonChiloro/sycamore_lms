const mysql = require('mysql2/promise');
const moment = require('moment');

/**
 * Script to generate batch numbers for group member loans
 *
 * Algorithm:
 * 1. Select all loans where customer_type = 'individual'
 * 2. Join with customer_groups to get group membership
 * 3. Filter loans where:
 *    - Customer is part of a group
 *    - Loan product is 6, 17, 27, 28, 29, or 30
 *    - Created on or after September 1, 2025
 * 4. Group loans by: group_id, creation date, product
 * 5. Generate batch numbers in format: BATCH{YYYYMMDD}{GROUP_ID}
 * 6. Update loan table with batch, from_group='Yes', group_id
 */

// Database configuration
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'sycamore'
};

async function generateGroupLoanBatches() {
    let connection;

    try {
        console.log('====== GROUP LOAN BATCH GENERATION STARTED ======');
        console.log(`Start Time: ${moment().format('YYYY-MM-DD HH:mm:ss')}\n`);

        // Create database connection
        connection = await mysql.createConnection(dbConfig);
        console.log('✓ Database connection established');

        // Start transaction
        await connection.beginTransaction();
        console.log('✓ Transaction started\n');

        // Query to get individual customer loans that are part of groups.
        // Safety: if loan.group_id was set during creation, only accept that same group.
        // This prevents cross-group members from being mixed into a selected group's batch.
        const query = `
            SELECT
                l.loan_id,
                l.loan_number,
                l.loan_customer,
                l.loan_product,
                l.loan_added_date,
                l.group_id AS selected_group_id,
                cg.group_id,
                g.group_name,
                (
                    SELECT COUNT(DISTINCT cg2.group_id)
                    FROM customer_groups cg2
                    WHERE cg2.customer = l.loan_customer
                ) AS customer_group_count,
                DATE(l.loan_added_date) as creation_date
            FROM loan l
            INNER JOIN customer_groups cg ON l.loan_customer = cg.customer
            INNER JOIN \`groups\` g ON cg.group_id = g.group_id
            WHERE l.customer_type = 'individual'
              AND l.loan_product IN (6, 17, 27, 28, 29, 30)
              AND DATE(l.loan_added_date) >= '2025-09-01'
              AND (l.batch IS NULL OR l.batch = '')
              AND (l.group_id IS NULL OR l.group_id = cg.group_id)
            ORDER BY cg.group_id, DATE(l.loan_added_date), l.loan_product
        `;

        console.log('Fetching loans that meet criteria...');
        const [loans] = await connection.query(query);
        console.log(`✓ Found ${loans.length} loans to process\n`);

        if (loans.length === 0) {
            console.log('No loans found that need batch numbers.');
            await connection.commit();
            return;
        }

        // Validate membership and group loans by: group_id + creation_date + product
        const batches = {};
        const rejectedLoans = [];

        loans.forEach(loan => {
            // Hard validation:
            // 1) If a selected group exists on the loan, it MUST match membership.
            // 2) If no selected group exists and customer belongs to multiple groups, reject as ambiguous.
            if (loan.selected_group_id && Number(loan.selected_group_id) !== Number(loan.group_id)) {
                rejectedLoans.push({
                    loan_id: loan.loan_id,
                    loan_number: loan.loan_number,
                    reason: `selected_group_id=${loan.selected_group_id} mismatches membership group_id=${loan.group_id}`
                });
                return;
            }

            if (!loan.selected_group_id && Number(loan.customer_group_count) > 1) {
                rejectedLoans.push({
                    loan_id: loan.loan_id,
                    loan_number: loan.loan_number,
                    reason: `customer belongs to ${loan.customer_group_count} groups and loan has no selected group`
                });
                return;
            }

            const resolvedGroupId = Number(loan.selected_group_id || loan.group_id);
            const creationDate = moment(loan.creation_date).format('YYYYMMDD');
            const batchKey = `${resolvedGroupId}_${creationDate}_${loan.loan_product}`;

            if (!batches[batchKey]) {
                batches[batchKey] = {
                    group_id: resolvedGroupId,
                    group_name: loan.group_name,
                    creation_date: creationDate,
                    loan_product: loan.loan_product,
                    loans: []
                };
            }

            batches[batchKey].loans.push(loan);
        });

        console.log(`Identified ${Object.keys(batches).length} unique batches`);
        console.log(`Rejected ${rejectedLoans.length} loans due to group validation\n`);

        if (rejectedLoans.length > 0) {
            console.log('Rejected loans (validation failures):');
            rejectedLoans.forEach((item) => {
                console.log(`  - Loan ${item.loan_number} (ID: ${item.loan_id}): ${item.reason}`);
            });
            console.log('');
        }
        console.log('='.repeat(80));

        let totalUpdated = 0;
        let batchNumber = 0;

        // Process each batch
        for (const batchKey in batches) {
            const batch = batches[batchKey];
            batchNumber++;

            // Generate batch number: BATCH{YYYYMMDD}{GROUP_ID}
            const generatedBatchNumber = `BATCH${batch.creation_date}${batch.group_id}`;

            console.log(`\nBatch #${batchNumber}:`);
            console.log(`  Batch Number: ${generatedBatchNumber}`);
            console.log(`  Group ID: ${batch.group_id}`);
            console.log(`  Group Name: ${batch.group_name}`);
            console.log(`  Creation Date: ${batch.creation_date}`);
            console.log(`  Loan Product: ${batch.loan_product}`);
            console.log(`  Loans in Batch: ${batch.loans.length}`);

            // Update each loan in this batch
            for (const loan of batch.loans) {
                const updateQuery = `
                    UPDATE loan
                    SET batch = ?,
                        from_group = 'Yes',
                        group_id = ?
                    WHERE loan_id = ?
                `;

                await connection.query(updateQuery, [
                    generatedBatchNumber,
                    batch.group_id,
                    loan.loan_id
                ]);

                console.log(`    ✓ Updated Loan: ${loan.loan_number} (ID: ${loan.loan_id})`);
                totalUpdated++;
            }
        }

        console.log('\n' + '='.repeat(80));
        console.log(`\n✓ Successfully updated ${totalUpdated} loans across ${batchNumber} batches`);

        // Commit transaction
        await connection.commit();
        console.log('✓ Transaction committed\n');

        // Display summary
        console.log('====== SUMMARY ======');
        console.log(`Total Loans Updated: ${totalUpdated}`);
        console.log(`Total Batches Created: ${batchNumber}`);
        console.log(`End Time: ${moment().format('YYYY-MM-DD HH:mm:ss')}`);
        console.log('====== BATCH GENERATION COMPLETED SUCCESSFULLY ======');

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        console.error('Stack trace:', error.stack);

        if (connection) {
            try {
                await connection.rollback();
                console.log('✓ Transaction rolled back');
            } catch (rollbackError) {
                console.error('❌ Error during rollback:', rollbackError.message);
            }
        }

        process.exit(1);

    } finally {
        if (connection) {
            await connection.end();
            console.log('\n✓ Database connection closed');
        }
    }
}

// Run the script
generateGroupLoanBatches()
    .then(() => {
        console.log('\nScript execution completed.');
        process.exit(0);
    })
    .catch((error) => {
        console.error('\nFatal error:', error);
        process.exit(1);
    });
