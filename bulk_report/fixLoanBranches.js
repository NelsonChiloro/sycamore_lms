const { query } = require('./databaseHelpers');

// Helper function to maintain callback compatibility
const db = {
    query: async (sql, params, callback) => {
        if (typeof params === 'function') {
            callback = params;
            params = [];
        }
        try {
            const results = await query(sql, params);
            callback(null, results);
        } catch (err) {
            callback(err);
        }
    },
    end: () => {
        console.log('\n🔚 Database connection handled by pool.');
    }
};

// Start fixing immediately (no need to connect)
console.log('Using centralized database connection');
startFixing();

async function startFixing() {
    console.log('🚀 Starting loan branch fixing process...\n');
    
    try {
        // Step 1: Get all loans where branch = 0
        console.log('📊 Finding loans with branch = 0...');
        const loansToFix = await queryDatabase('SELECT loan_id, customer_type, loan_customer FROM loan WHERE branch = 0');
        
        if (loansToFix.length === 0) {
            console.log('✅ No loans found with branch = 0. All loans already have correct branches!');
            db.end();
            return;
        }
        
        console.log(`📋 Found ${loansToFix.length} loans with branch = 0\n`);
        
        let individualCount = 0;
        let groupCount = 0;
        let individualUpdated = 0;
        let groupUpdated = 0;
        let errors = 0;
        
        // Step 2: Process each loan
        for (const loan of loansToFix) {
            console.log(`🔄 Processing Loan ID: ${loan.loan_id}, Customer Type: ${loan.customer_type}, Customer ID: ${loan.loan_customer}`);
            
            try {
                if (loan.customer_type === 'individual') {
                    individualCount++;
                    console.log(`   👤 Processing individual customer...`);
                    
                    // Get individual customer's Branch (Code)
                    const individual = await queryDatabase(
                        'SELECT Branch FROM individual_customers WHERE id = ?', 
                        [loan.loan_customer]
                    );
                    
                    if (individual.length === 0) {
                        console.log(`   ❌ Individual customer not found for ID: ${loan.loan_customer}`);
                        errors++;
                        continue;
                    }
                    
                    const branchCode = individual[0].Branch;
                    console.log(`   📍 Individual's branch code: ${branchCode}`);
                    
                    if (!branchCode) {
                        console.log(`   ⚠️  Individual has no branch code`);
                        errors++;
                        continue;
                    }
                    
                    // Get branch ID from branches table using Code
                    const branch = await queryDatabase(
                        'SELECT id FROM branches WHERE Code = ?', 
                        [branchCode]
                    );
                    
                    if (branch.length === 0) {
                        console.log(`   ❌ Branch not found for code: ${branchCode}`);
                        errors++;
                        continue;
                    }
                    
                    const branchId = branch[0].id;
                    console.log(`   🏢 Branch ID: ${branchId}`);
                    
                    // Update loan's branch
                    await queryDatabase(
                        'UPDATE loan SET branch = ? WHERE loan_id = ?', 
                        [branchId, loan.loan_id]
                    );
                    
                    console.log(`   ✅ Updated loan ${loan.loan_id} with branch ${branchId}\n`);
                    individualUpdated++;
                    
                } else if (loan.customer_type === 'group') {
                    groupCount++;
                    console.log(`   👥 Processing group customer...`);
                    
                    // Get group's branch (already the branch ID)
                    const group = await queryDatabase(
                        'SELECT branch FROM `groups` WHERE group_id = ?', 
                        [loan.loan_customer]
                    );
                    
                    if (group.length === 0) {
                        console.log(`   ❌ Group not found for ID: ${loan.loan_customer}`);
                        errors++;
                        continue;
                    }
                    
                    const branchId = group[0].branch;
                    console.log(`   🏢 Group's branch ID: ${branchId}`);
                    
                    if (!branchId) {
                        console.log(`   ⚠️  Group has no branch ID`);
                        errors++;
                        continue;
                    }
                    
                    // Update loan's branch
                    await queryDatabase(
                        'UPDATE loan SET branch = ? WHERE loan_id = ?', 
                        [branchId, loan.loan_id]
                    );
                    
                    console.log(`   ✅ Updated loan ${loan.loan_id} with branch ${branchId}\n`);
                    groupUpdated++;
                    
                } else {
                    console.log(`   ⚠️  Unknown customer type: ${loan.customer_type}`);
                    errors++;
                }
                
            } catch (loanError) {
                console.log(`   ❌ Error processing loan ${loan.loan_id}:`, loanError.message);
                errors++;
            }
        }
        
        // Step 3: Final summary
        console.log('\n🎉 FIXING PROCESS COMPLETED!');
        console.log('='.repeat(50));
        console.log(`📊 SUMMARY:`);
        console.log(`   Total loans processed: ${loansToFix.length}`);
        console.log(`   Individual customers: ${individualCount} (${individualUpdated} updated)`);
        console.log(`   Group customers: ${groupCount} (${groupUpdated} updated)`);
        console.log(`   Total updated: ${individualUpdated + groupUpdated}`);
        console.log(`   Errors: ${errors}`);
        console.log('='.repeat(50));
        
        if (individualUpdated + groupUpdated > 0) {
            console.log('✅ Branch fixing completed successfully!');
        } else {
            console.log('⚠️  No loans were updated. Please check the data.');
        }
        
    } catch (error) {
        console.error('❌ Fatal error during fixing process:', error);
    } finally {
        db.end();
        console.log('\n🔚 Database connection closed.');
    }
}

// Helper function to promisify database queries
function queryDatabase(query, params = []) {
    return new Promise((resolve, reject) => {
        db.query(query, params, (err, results) => {
            if (err) {
                reject(err);
            } else {
                resolve(results);
            }
        });
    });
}

// Handle script termination
process.on('SIGINT', () => {
    console.log('\n🛑 Script interrupted by user');
    db.end();
    process.exit(0);
});

process.on('uncaughtException', (error) => {
    console.error('❌ Uncaught Exception:', error);
    db.end();
    process.exit(1);
});