const mysql = require('mysql2');

// Create MySQL connection pool
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'financerealm_sycamore_demo',
    waitForConnections: true,
    connectionLimit: parseInt(process.env.DB_POOL_LIMIT || '15', 10),
    queueLimit: parseInt(process.env.DB_POOL_QUEUE_LIMIT || '0', 10),
    enableKeepAlive: true,
    keepAliveInitialDelay: 10000
});

const promisePool = pool.promise();

// Helper function to execute queries
const query = (sql, params) => {
    return new Promise((resolve, reject) => {
        pool.query(sql, params, (err, results) => {
            if (err) return reject(err);
            resolve(results);
        });
    });
};

const getConnection = () => promisePool.getConnection();

// Function to get amount of arrears
const getAmountOfArrears = async (loanID) => {
    const sql = `
        SELECT SUM(ps.amount) AS amount_arrears
        FROM loan l
        JOIN payement_schedules ps ON l.loan_id = ps.loan_id
        WHERE l.loan_id = ${loanID}
          AND l.loan_status = 'Active'
          AND ps.payment_schedule < CURDATE()
          AND ps.status = 'NOT PAID'
    `;
    const results = await query(sql);
    
    // Check if results are returned and if amount_arrears is not null
    return results[0]?.amount_arrears || 0;
};

const getAmountOfArrearsPaid = async (loanID) => {
    const sql = `
        SELECT SUM(ps.paid_amount) AS amount_arrears
        FROM loan l
        JOIN payement_schedules ps ON l.loan_id = ps.loan_id
        WHERE l.loan_id = ${loanID}
          AND l.loan_status = 'Active'
          AND ps.payment_schedule < CURDATE()
          AND ps.status = 'NOT PAID'
    `;
    const results = await query(sql);
    return results[0]?.amount_arrears || 0;
};
/**
 * RBM Loan Classification from days in arrears (same rules as RBM Classification Status Report).
 * Standard: 0-29, Special Mention: 30-59, Substandard: 60-89, Doubtful: 90-179, Loss: 180+
 */
function determineRBMClassification(daysInArrears) {
    const days = Number(daysInArrears) || 0;
    if (days < 30) {
        return 'Standard';
    }
    if (days < 60) {
        return 'Special Mention';
    }
    if (days < 90) {
        return 'Substandard';
    }
    if (days < 180) {
        return 'Doubtful';
    }
    return 'Loss';
}

/** SQL subquery: max days in arrears for unpaid installments past due (per loan). */
function sqlLoanMaxDaysInArrearsExpr(loanAlias = 'l') {
    return `COALESCE((
        SELECT MAX(DATEDIFF(CURDATE(), ps.payment_schedule))
        FROM payement_schedules ps
        WHERE ps.loan_id = ${loanAlias}.loan_id
          AND ps.status = 'NOT PAID'
          AND ps.payment_schedule < CURDATE()
    ), 0)`;
}

const getDaysOfArrears = async (loanID) => {
    const sql = `
        SELECT ${sqlLoanMaxDaysInArrearsExpr('l')} AS days_in_arrears
        FROM loan l
        WHERE l.loan_id = ?
    `;

    const results = await query(sql, [loanID]);
    return results[0]?.days_in_arrears || 0;
};

const getNumberOfArrears = async (loanID) => {
    const sql = `
        SELECT COUNT(*) AS num_arrears
        FROM payement_schedules
        WHERE loan_id = ?
          AND payment_schedule < CURDATE()
          AND status = 'NOT PAID'
    `;

    const results = await query(sql, [loanID]);
    return results[0]?.num_arrears || 0;
};

const getLastPaidPaymentByLoanId = async (loanID) => {
    const sql = `
        SELECT *
        FROM payement_schedules
        WHERE loan_id = ?
          AND (status = 'PAID' OR paid_amount > 0)
                ORDER BY COALESCE(paid_date, payment_schedule) DESC, id DESC
        LIMIT 1
    `;

    const results = await query(sql, [loanID]);
    return results[0] || null;
};

// Function to get loan details
const getLoanDetails = async (loanID) => {
    const sql = `
        SELECT * FROM loan
        WHERE loan_id = ?
    `;
    const results = await query(sql, [loanID]);
    return results[0];
};

// Function to get user profile
const getUserProfile = async (userID) => {
    const sql = `
        SELECT * FROM users
        WHERE user_id = ?
    `;
    const results = await query(sql, [userID]);
    return results[0];
};

// Function to get loan repayments
const getLoanRepayments = async (loanID) => {
    const sql = `
        SELECT * FROM loan_repayments
        WHERE loan_id = ?
    `;
    const results = await query(sql, [loanID]);
    return results;
};

// Expected-vs-paid totals up to an as-of date (default today).
const getExpectedAndPaidToDate = async (loanID, asOfDate = null) => {
    const sql = `
        SELECT
            COALESCE(SUM(CASE WHEN payment_schedule <= DATE(COALESCE(?, CURDATE())) THEN amount ELSE 0 END), 0) AS total_expected,
            COALESCE(SUM(CASE WHEN payment_schedule <= DATE(COALESCE(?, CURDATE())) THEN paid_amount ELSE 0 END), 0) AS total_paid
        FROM payement_schedules
        WHERE loan_id = ?
    `;
    const results = await query(sql, [asOfDate, asOfDate, loanID]);
    return {
        total_expected: parseFloat(results[0]?.total_expected || 0),
        total_paid: parseFloat(results[0]?.total_paid || 0),
    };
};
const getPARsummaryu = async (user, loanproduct) => {
    // Initialize the query
    let q = '';

    // Concatenate filters based on the provided values
    if (user && user !== 'All') {
        // If user is provided and not 'All', add filter for user
        q += `AND AA.loan_added_by = '${user}' `;
    }

    if (loanproduct && loanproduct !== 'All') {
        // If loanproduct is provided and not 'All', add filter for loan product
        q += `AND AA.loan_product = '${loanproduct}' `;
    }

    // Default filter for non-deleted loans, if no user or product filters were applied
    if (q === '') {
        q = `AND AA.loan_status != 'deleted'`;
    }

    // Define the query string
    const sql = `
    SELECT 
        AA.loan_id as loan_id,
        AA.loan_added_by as loan_added_by,
        AA.loan_customer as loan_customer,
        AA.customer_type as customer_type,
        AA.loan_number as lnumber,
        AA.loan_principal as lm,
        AA.loan_product,
        EP.Firstname,
        EP.Lastname,
        SUM(PS.amount) AS total_amount,
        (SELECT MIN(payment_schedule) 
            FROM payement_schedules 
            WHERE customer = AA.loan_customer 
            AND status = 'NOT PAID' 
            AND payment_schedule <= DATE(NOW())) AS max_d 
    FROM payement_schedules PS 
    JOIN loan AA ON PS.loan_id = AA.loan_id 
    JOIN employees EP ON AA.loan_added_by = EP.id 
    WHERE PS.status = 'NOT PAID' 
    AND PS.payment_schedule <= DATE(NOW()) 
    AND AA.loan_status != 'deleted' 
    ${q}
    GROUP BY AA.loan_id
    `;

    // Execute the query and return results
    const results = await query(sql);
    return results;
};


const getPARsummary = async () => {
    const sql = `
SELECT AA.loan_id as loan_id,
       AA.loan_number as lnumber,
       AA.loan_principal as lm,
       BB.id as bid,
       BD.Firstname,
       BD.Lastname,
       ROUND(IFNULL((SELECT sum(amount) FROM payement_schedules WHERE customer = AA.loan_customer AND status = 'PAID' AND payment_schedule <= DATE(NOW()) ),0),2)
           AS t_payment,
    ROUND(IFNULL((SELECT sum(amount) FROM payement_schedules WHERE customer = AA.loan_customer AND status = 'NOT PAID' AND payment_schedule <= DATE(NOW()) ),0),2)
        AS u_payment,
    (SELECT MIN(payment_schedule) FROM payement_schedules WHERE customer = AA.loan_customer AND status = 'NOT PAID' AND payment_schedule <= DATE(NOW()) ) 
        AS max_d, 
    ROUND(IFNULL((SELECT sum(amount) FROM payement_schedules WHERE customer = AA.loan_customer AND status = 'NOT PAID' ),0),2) 
    AS t_balance,
    ROUND(IFNULL((SELECT sum(principal) FROM payement_schedules WHERE customer = AA.loan_customer AND status = 'NOT PAID' AND payment_schedule <= DATE(NOW()) ),0),2)
    AS t_principal,
    ROUND(IFNULL((SELECT sum(interest) FROM payement_schedules WHERE customer = AA.loan_customer AND status = 'NOT PAID' AND payment_schedule <= DATE(NOW()))  ,0),2) 
    AS t_interest, 
    CC.loan_id as is_due, 
    CC.loan_status as l_state 
    FROM loan AS AA 
    INNER JOIN payement_schedules AS BB ON AA.loan_id = BB.loan_id 
    INNER JOIN individual_customers AS BD ON BB.customer = BD.id 
    LEFT JOIN (SELECT a.* FROM loan a INNER JOIN payement_schedules b ON a.loan_id = b.loan_id	WHERE payment_schedule <= DATE(NOW()) AND a.loan_status = 'ACTIVE' AND b.status = 'NOT PAID' GROUP BY a.loan_id)
        as CC ON CC.loan_id = AA.loan_id 
    GROUP BY AA.loan_id ORDER BY AA.loan_id
    `;
    const results = await query(sql);
    return results;
};
const getParPrincipal = async () => {
    const sql = `
        SELECT SUM(loan_principal) AS total_principal
        FROM loan
        WHERE disbursed = "Yes" AND  loan_status = "ACTIVE" 
    `;
    const results = await query(sql);
    return results[0]?.total_principal || 0 ;
};
// Function to get loan status
const getLoanStatus = async (loanID) => {
    const sql = `
        SELECT loan_status FROM loan
        WHERE loan_id = ?
    `;
    const results = await query(sql, [loanID]);
    return results[0];
};

// Function to get total payments made
const getTotalPayments = async (loanID) => {
    const sql = `
        SELECT SUM(amount) AS total_payments
        FROM loan_payments
        WHERE loan_id = ?
    `;
    const results = await query(sql, [loanID]);
    return results[0];
};

// Function to get paginated report data
const rbmReport1 = async () => {
    const sql = `
        SELECT * FROM loan  
      
        LEFT JOIN individual_customers ON loan.loan_customer = individual_customers.id 
        LEFT JOIN proofofidentity ON proofofidentity.ClientID = individual_customers.ClientID 
        ORDER BY loan.loan_id DESC
    `;
    const results = await query(sql);
    return results;
};
const rbmReport = async () => {
    const sql = `
        SELECT * FROM individual_customers 
            INNER JOIN proofofidentity ON proofofidentity.ClientID = individual_customers.ClientID 
            INNER JOIN loan ON loan.loan_customer = individual_customers.id 
            ORDER BY loan.loan_id DESC
    `;
    const results = await query(sql);
    return results;
};
// Function to get all records by table name, key, and value
const getAllById = async (table, key, value, callback) => {
    const sql = `SELECT * FROM ?? WHERE ?? = ?`;
   await  query(sql, [table, key, value], (err, results) => {
        if (err) return callback(err);
        callback(null, results);
    });
};
const loanCollection =  (loanID) => {
    const sql = `
        SELECT SUM(paid_amount) AS total
        FROM payement_schedules
        WHERE loan_id = ${loanID}
    `;
   const result = query(sql)
   return result;
};
// Function to get a single record by table name, key, and value
// In databaseHelpers.js
const getById = async (table, key, value) => {
    // Escape table name with backtick
    const sql = `SELECT * FROM \`${table}\` WHERE \`${key}\` = ${value}`;
    const results = await query(sql);
    return results;
};

const findBranch = async (branchReference) => {
    if (branchReference === null || branchReference === undefined || branchReference === '') {
        return null;
    }

    const normalizedReference = String(branchReference).trim();
    const numericReference = Number(normalizedReference);
    const sql = `
        SELECT *
        FROM branches
        WHERE id = ?
           OR Code = ?
           OR BranchCode = ?
        LIMIT 1
    `;

    const results = await query(sql, [
        Number.isFinite(numericReference) ? numericReference : -1,
        normalizedReference,
        Number.isFinite(numericReference) ? numericReference : -1,
    ]);

    return results[0] || null;
};

/**
 * JOIN branches when loan.branch may store branches.id, Code, or BranchCode.
 */
function sqlBranchJoin(loanAlias = 'l', branchAlias = 'b') {
    return `LEFT JOIN branches ${branchAlias} ON (
        ${loanAlias}.branch = ${branchAlias}.id
        OR ${loanAlias}.branch = ${branchAlias}.Code
        OR ${loanAlias}.branch = ${branchAlias}.BranchCode
    )`;
}

/**
 * Append branch filter (UI passes branches.id) matching any stored loan.branch form.
 */
function appendBranchFilter(whereClause, queryParams, branch, loanAlias = 'l') {
    if (!branch || branch === 'All') {
        return whereClause;
    }
    queryParams.push(branch, branch, branch);
    return `${whereClause} AND (
        ${loanAlias}.branch = ?
        OR ${loanAlias}.branch IN (SELECT Code FROM branches WHERE id = ?)
        OR ${loanAlias}.branch IN (SELECT BranchCode FROM branches WHERE id = ?)
    )`;
}


// Function to get previous loan
const getPreviousLoan = async (customerID, callback) => {
    const sql = `
        SELECT * FROM loan
        WHERE loan_customer = ?
          AND loan_id < (SELECT MAX(loan_id) FROM loan WHERE loan_customer = ?)
        ORDER BY loan_id DESC
        LIMIT 1
    `;
   await query(sql, [customerID, customerID], (err, results) => {
        if (err) return callback(err);
        callback(null, results[0]);
    });
};
const getRBMLoanPaymentById = async (loanId, paymentNumber) => {
    const sql = `
        SELECT * 
        FROM payement_schedules 
        WHERE loan_id = ? 
          AND payment_number = ?
    `;
    const results = await query(sql, [loanId, paymentNumber]);
    return results[0]; // Return the first result (single record)
};
const getAllOverDuePayments = async (loanId) => {
    const sql = `
        SELECT SUM(principal) AS total_arrears
        FROM payement_schedules
        WHERE loan_id = ?
          AND status = 'NOT PAID'
          AND payment_schedule <= CURDATE()
    `;
    const results = await query(sql, [loanId]);
    return results[0]?.total_arrears || 0; // Return the first result (single record with total_arrears)
};
const sumTotalPar = async () => {
    const sql = `
        SELECT *, loan.loan_principal AS lm
        FROM loan
       LEFT JOIN payement_schedules ON payement_schedules.loan_id = loan.loan_id
        WHERE disbursed = 'Yes' AND  loan_status = "ACTIVE"
    `;
    const results = await query(sql);
    return results; // Return the result set
};

function roleNameIsRelationshipOfficer(roleName) {
    const name = String(roleName || '').toUpperCase().replace(/\s+/g, ' ');
    return name.includes('RELATIONSHIP') && name.includes('OFFICER') && !name.includes('SUPERVISOR');
}

let cachedRelationshipOfficerRoleIds = null;
async function getRelationshipOfficerRoleIds() {
    if (cachedRelationshipOfficerRoleIds) {
        return cachedRelationshipOfficerRoleIds;
    }
    const rows = await query('SELECT id, RoleName FROM roles');
    cachedRelationshipOfficerRoleIds = rows
        .filter((r) => roleNameIsRelationshipOfficer(r.RoleName))
        .map((r) => r.id);
    return cachedRelationshipOfficerRoleIds;
}

async function getOfficerIdsUnderSupervisor(supervisorId) {
    const sid = parseInt(supervisorId, 10);
    if (!sid) {
        return [];
    }
    const roleIds = await getRelationshipOfficerRoleIds();
    if (!roleIds.length) {
        return [];
    }
    const sql = `SELECT id FROM employees WHERE Supervisor = ${sid} AND Role IN (${roleIds.join(',')})`;
    const rows = await query(sql);
    return rows.map((r) => r.id);
}

function sqlRelationshipSupervisorNameExpr(supervisorAlias = 'rel_sup') {
    return `TRIM(CONCAT(COALESCE(${supervisorAlias}.Firstname, ''), ' ', COALESCE(${supervisorAlias}.Lastname, '')))`;
}

function sqlRelationshipSupervisorJoin(loanAlias = 'loan', officerAlias = 'e', supervisorAlias = 'rel_sup') {
    return `LEFT JOIN employees ${officerAlias} ON ${officerAlias}.id = ${loanAlias}.loan_added_by
LEFT JOIN employees ${supervisorAlias} ON ${supervisorAlias}.id = ${officerAlias}.Supervisor`;
}

async function buildReportSupervisorContext(options = {}) {
    const supervisor = options.supervisor && options.supervisor !== 'All' ? options.supervisor : null;
    let supervisorOfficerIds = null;
    if (supervisor) {
        supervisorOfficerIds = await getOfficerIdsUnderSupervisor(supervisor);
    }
    return { supervisor, supervisorOfficerIds };
}

function appendOfficerOrSupervisorLoanFilter(sql, ctx, loanAlias = 'loan', officerField = 'user') {
    if (ctx.supervisorOfficerIds !== null) {
        if (!ctx.supervisorOfficerIds.length) {
            return `${sql} AND 1=0`;
        }
        return `${sql} AND ${loanAlias}.loan_added_by IN (${ctx.supervisorOfficerIds.join(',')})`;
    }
    const officer = ctx[officerField] || ctx.officer || ctx.user;
    if (officer && officer !== 'All') {
        const esc = String(officer).replace(/'/g, "''");
        return `${sql} AND ${loanAlias}.loan_added_by = '${esc}'`;
    }
    return sql;
}

// Export all functions
module.exports = {
    query, // Export the query function for other files to use
    getConnection,
    getAmountOfArrears,
    getAmountOfArrearsPaid,
    getNumberOfArrears,
    getLastPaidPaymentByLoanId,
    getLoanDetails,
    getUserProfile,
    getLoanRepayments,
    getExpectedAndPaidToDate,
    getLoanStatus,
    getTotalPayments,
    rbmReport,
    getAllById,
    loanCollection,
    getById,
    findBranch,
    sqlBranchJoin,
    appendBranchFilter,
    getPreviousLoan,
    getRBMLoanPaymentById,
    getDaysOfArrears,
    determineRBMClassification,
    sqlLoanMaxDaysInArrearsExpr,
    getParPrincipal,
    getPARsummary,
    getPARsummaryu,
    getAllOverDuePayments,
    sumTotalPar,
    getOfficerIdsUnderSupervisor,
    getRelationshipOfficerRoleIds,
    sqlRelationshipSupervisorNameExpr,
    sqlRelationshipSupervisorJoin,
    buildReportSupervisorContext,
    appendOfficerOrSupervisorLoanFilter
};
