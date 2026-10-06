const mysql = require('mysql2/promise');
const moment = require('moment');

// Database configuration
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'sycamore'
};

async function debugGroupLoans() {
    let connection;

    try {
        console.log('====== DEBUGGING GROUP LOAN DATA ======\n');

        connection = await mysql.createConnection(dbConfig);
        console.log('✓ Database connection established\n');

        // Check 1: Count individual customer loans
        console.log('1. Checking individual customer loans...');
        const [individualLoans] = await connection.query(`
            SELECT COUNT(*) as count, MIN(loan_added_date) as earliest, MAX(loan_added_date) as latest
            FROM loan
            WHERE customer_type = 'individual'
        `);
        console.log(`   Total individual loans: ${individualLoans[0].count}`);
        console.log(`   Earliest loan: ${individualLoans[0].earliest}`);
        console.log(`   Latest loan: ${individualLoans[0].latest}\n`);

        // Check 2: Individual loans by product
        console.log('2. Individual loans by product (showing all products)...');
        const [productBreakdown] = await connection.query(`
            SELECT loan_product, COUNT(*) as count
            FROM loan
            WHERE customer_type = 'individual'
            GROUP BY loan_product
            ORDER BY loan_product
        `);
        productBreakdown.forEach(row => {
            console.log(`   Product ${row.loan_product}: ${row.count} loans`);
        });
        console.log();

        // Check 3: Individual loans with products 6, 17, 27, 28, 29, 30
        console.log('3. Individual loans with products 6, 17, 27, 28, 29, or 30...');
        const [targetProducts] = await connection.query(`
            SELECT loan_product, COUNT(*) as count, MIN(loan_added_date) as earliest, MAX(loan_added_date) as latest
            FROM loan
            WHERE customer_type = 'individual'
              AND loan_product IN (6, 17, 27, 28, 29, 30)
            GROUP BY loan_product
        `);
        if (targetProducts.length > 0) {
            targetProducts.forEach(row => {
                console.log(`   Product ${row.loan_product}: ${row.count} loans (${row.earliest} to ${row.latest})`);
            });
        } else {
            console.log('   No loans found with products 6, 17, 27, 28, 29, 30');
        }
        console.log();

        // Check 4: Customer groups membership
        console.log('4. Checking customer_groups table...');
        const [groupMembers] = await connection.query(`
            SELECT COUNT(DISTINCT customer) as customers, COUNT(DISTINCT group_id) as \`groups\`
            FROM customer_groups
        `);
        console.log(`   Total customers in groups: ${groupMembers[0].customers}`);
        console.log(`   Total groups: ${groupMembers[0].groups}\n`);

        // Check 5: Individual loans where customer is in a group
        console.log('5. Individual loans where customer is in a group...');
        const [loansInGroups] = await connection.query(`
            SELECT COUNT(DISTINCT l.loan_id) as loan_count
            FROM loan l
            INNER JOIN customer_groups cg ON l.loan_customer = cg.customer
            WHERE l.customer_type = 'individual'
        `);
        console.log(`   Total: ${loansInGroups[0].loan_count} loans\n`);

        // Check 6: Individual loans in groups with products 6, 17, 27, 28, 29, 30
        console.log('6. Individual loans in groups with products 6, 17, 27, 28, 29, or 30...');
        const [loansInGroupsProducts] = await connection.query(`
            SELECT
                l.loan_product,
                COUNT(DISTINCT l.loan_id) as loan_count,
                MIN(l.loan_added_date) as earliest,
                MAX(l.loan_added_date) as latest
            FROM loan l
            INNER JOIN customer_groups cg ON l.loan_customer = cg.customer
            WHERE l.customer_type = 'individual'
              AND l.loan_product IN (6, 17, 27, 28, 29, 30)
            GROUP BY l.loan_product
        `);
        if (loansInGroupsProducts.length > 0) {
            loansInGroupsProducts.forEach(row => {
                console.log(`   Product ${row.loan_product}: ${row.loan_count} loans (${row.earliest} to ${row.latest})`);
            });
        } else {
            console.log('   No loans found');
        }
        console.log();

        // Check 7: Loans created on or after Sept 1, 2025
        console.log('7. Individual loans in groups with products 6, 17, 27, 28, 29, 30 created on or after 2025-09-01...');
        const [recentLoans] = await connection.query(`
            SELECT
                l.loan_product,
                COUNT(DISTINCT l.loan_id) as loan_count,
                MIN(l.loan_added_date) as earliest,
                MAX(l.loan_added_date) as latest
            FROM loan l
            INNER JOIN customer_groups cg ON l.loan_customer = cg.customer
            WHERE l.customer_type = 'individual'
              AND l.loan_product IN (6, 17, 27, 28, 29, 30)
              AND DATE(l.loan_added_date) >= '2025-09-01'
            GROUP BY l.loan_product
        `);
        if (recentLoans.length > 0) {
            recentLoans.forEach(row => {
                console.log(`   Product ${row.loan_product}: ${row.loan_count} loans (${row.earliest} to ${row.latest})`);
            });
        } else {
            console.log('   No loans found created on or after 2025-09-01');
        }
        console.log();

        // Check 8: Sample of individual loans with all details
        console.log('8. Sample individual loans (first 5)...');
        const [sampleLoans] = await connection.query(`
            SELECT
                l.loan_id,
                l.loan_number,
                l.customer_type,
                l.loan_customer,
                l.loan_product,
                l.loan_added_date,
                l.batch,
                l.from_group,
                l.group_id
            FROM loan l
            WHERE l.customer_type = 'individual'
            LIMIT 5
        `);
        if (sampleLoans.length > 0) {
            sampleLoans.forEach(loan => {
                console.log(`   Loan ${loan.loan_number}:`);
                console.log(`     - ID: ${loan.loan_id}`);
                console.log(`     - Customer ID: ${loan.loan_customer}`);
                console.log(`     - Product: ${loan.loan_product}`);
                console.log(`     - Created: ${loan.loan_added_date}`);
                console.log(`     - Batch: ${loan.batch || 'NULL'}`);
                console.log(`     - From Group: ${loan.from_group}`);
                console.log(`     - Group ID: ${loan.group_id || 'NULL'}`);
            });
        }
        console.log();

        // Check 9: Check if any customers are linked to groups
        console.log('9. Sample customer-group links (first 5)...');
        const [sampleGroups] = await connection.query(`
            SELECT cg.customer, cg.group_id, g.group_name
            FROM customer_groups cg
            LEFT JOIN \`groups\` g ON cg.group_id = g.group_id
            LIMIT 5
        `);
        if (sampleGroups.length > 0) {
            sampleGroups.forEach(row => {
                console.log(`   Customer ${row.customer} -> Group ${row.group_id} (${row.group_name})`);
            });
        } else {
            console.log('   No customer-group links found');
        }
        console.log();

        console.log('====== DEBUG COMPLETE ======');

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        console.error('Stack trace:', error.stack);
    } finally {
        if (connection) {
            await connection.end();
            console.log('\n✓ Database connection closed');
        }
    }
}

// Run the debug script
debugGroupLoans()
    .then(() => {
        console.log('\nDebug script completed.');
        process.exit(0);
    })
    .catch((error) => {
        console.error('\nFatal error:', error);
        process.exit(1);
    });
