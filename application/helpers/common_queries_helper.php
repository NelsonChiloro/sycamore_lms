<?php
if (!function_exists('ensure_db_loaded')) {
    function ensure_db_loaded()
    {
        $ci =& get_instance();
        if (!isset($ci->db) || !is_object($ci->db)) {
            $ci->load->database();
        }
        return $ci;
    }
}

if (!function_exists('safe_db_query')) {
    function safe_db_query($sql, $binds = array(), $retryOnDisconnect = true)
    {
        $ci = ensure_db_loaded();

        $original_debug = isset($ci->db->db_debug) ? $ci->db->db_debug : false;
        $ci->db->db_debug = false;

        $query = $ci->db->query($sql, $binds);
        if ($query !== false) {
            $ci->db->db_debug = $original_debug;
            return $query;
        }

        $error = $ci->db->error();
        $code = isset($error['code']) ? (int)$error['code'] : 0;

        // Retry once when the server dropped the connection.
        if ($retryOnDisconnect && ($code === 2006 || $code === 2013)) {
            if (method_exists($ci->db, 'reconnect')) {
                $ci->db->reconnect();
            } else {
                $ci->db->close();
                $ci->load->database();
            }

            $query = $ci->db->query($sql, $binds);
        }

        $ci->db->db_debug = $original_debug;
        return $query;
    }
}

function get_all($id){


    $ci = ensure_db_loaded();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM $id ";
    $query = safe_db_query($sql);
    return $query ? $query->result() : array();


}
function get_loan_cycle($loan_id, $customer_id, $customer_type) {
    // Get CI instance
    $CI =& get_instance();

    // Load database if not already loaded
    if (!isset($CI->db)) {
        $CI->load->database();
    }

    // Get all loans for this customer, ordered by ID (assuming ID is incremental and sequential)
    $query = $CI->db->select('loan_id')
        ->from('loan')  // Replace with your actual loan table name
        ->where('loan_customer', $customer_id)
        ->where('customer_type', $customer_type)
        ->order_by('loan_id', 'ASC')
        ->get();

    if ($query->num_rows() == 0) {
        return 0; // No loans found for this customer
    }

    // Get all loan IDs for this customer
    $loans = array_column($query->result_array(), 'loan_id');

    // Find position of current loan in the array (add 1 because array is 0-indexed)
    $cycle = array_search($loan_id, $loans);

    // Return cycle number (starting from 1)
    return ($cycle !== false) ? $cycle + 1 : 0;
}

function get_unpaid_principal_balance($loan_id){
    $ci = ensure_db_loaded();
    
    $sql="SELECT SUM(principal) AS total_principal
        FROM payement_schedules
        WHERE loan_id = '$loan_id'
        AND status = 'NOT PAID'";
    return $query = $ci->db->query($sql)->row();
}

function get_oldest_unpaid_schedule($loan_id){
    $ci = ensure_db_loaded();
    
    $sql="SELECT payment_schedule
        FROM payement_schedules
        WHERE loan_id = '$loan_id'
        AND status = 'NOT PAID'
        AND payment_schedule < CURDATE()
        ORDER BY payment_schedule ASC
        LIMIT 1";
    return $query = $ci->db->query($sql)->row();
}

function get_all_customer($id, $searchTerm = '') {
    $ci =& get_instance();
    $ci->load->database();

    // Query for individual customers with search term
    if ($id == 'individual_customers') {
        $sql = "SELECT * FROM individual_customers WHERE Firstname LIKE ? OR Lastname LIKE ?";
        $query = $ci->db->query($sql, array('%' . $searchTerm . '%', '%' . $searchTerm . '%'));
    }
    // Query for groups with search term
    else if ($id == 'groups') {
        $sql = "SELECT * FROM groups WHERE group_name LIKE ? OR group_code LIKE ?";
        $query = $ci->db->query($sql, array('%' . $searchTerm . '%', '%' . $searchTerm . '%'));
    }
    // Default query for other tables
    else {
        $sql = "SELECT * FROM $id";
        $query = $ci->db->query($sql);
    }

    return $query->result();
}





function get_one_where($table, $where) {
    $ci =& get_instance();
    $ci->load->database();

    $sql = "SELECT * FROM $table WHERE $where";



    $query = safe_db_query($sql);
    return $query ? $query->row() : null;
}
function get_all_loans($id){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM $id  WHERE loan_status <>'DELETED' ";
    return $query = $ci->db->query($sql)->result();


}
function get_last($id){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM  transaction  WHERE account_number = '$id' ORDER BY id DESC LIMIT 1";
    $query = $ci->db->query($sql)->row();
    if(!empty($query)){
        $sql2="SELECT * FROM  transaction  WHERE account_number != '$id' AND transaction_id = '$query->transaction_id'";
        return $ci->db->query($sql2)->row();
    }
    else{
        return  array();
    }

}
function get_witness(){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM `newgroup` GROUP BY Firstname_witness, Lastname_witness ";
    return $query = $ci->db->query($sql)->result();


}
function get_relative(){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM `newgroup` GROUP BY Firstname_relative, Lastname_relative ";
    return $query = $ci->db->query($sql)->result();


}function get_groups(){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM `newgroup` GROUP BY groupname ORDER BY disburseddate ASC ";
    return $query = $ci->db->query($sql)->result();


}
function get_by_id($table,$key,$value){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM $table  WHERE $key = '$value'";
    $query = safe_db_query($sql);
    return $query ? $query->row() : null;


}

<<<<<<< HEAD
/**
 * Resolve a branches row when loan.branch may store branches.id, Code, or BranchCode.
 *
 * @param mixed $branch_value Value from loan.branch
 * @return object|null branches row
 */
/**
 * RBM Loan Classification from days in arrears (RBM Classification Status Report rules).
 */
function determine_rbm_classification($days_in_arrears)
{
    $days = (int) $days_in_arrears;
    if ($days < 30) {
        return 'Standard';
    }
    if ($days < 60) {
        return 'Special Mention';
    }
    if ($days < 90) {
        return 'Substandard';
    }
    if ($days < 180) {
        return 'Doubtful';
    }
    return 'Loss';
}

/**
 * SQL: max days in arrears from unpaid past-due installments.
 */
function sql_loan_days_in_arrears_expr($loan_alias = 'loan')
{
    return "COALESCE((
        SELECT MAX(DATEDIFF(CURDATE(), ps.payment_schedule))
        FROM payement_schedules ps
        WHERE ps.loan_id = {$loan_alias}.loan_id
          AND ps.status = 'NOT PAID'
          AND ps.payment_schedule < CURDATE()
    ), 0)";
}

/**
 * SQL: RBM classification label for a loan row.
 */
function sql_loan_rbm_classification_expr($loan_alias = 'loan')
{
    $days_expr = sql_loan_days_in_arrears_expr($loan_alias);
    return "CASE
        WHEN ({$days_expr}) < 30 THEN 'Standard'
        WHEN ({$days_expr}) < 60 THEN 'Special Mention'
        WHEN ({$days_expr}) < 90 THEN 'Substandard'
        WHEN ({$days_expr}) < 180 THEN 'Doubtful'
        ELSE 'Loss'
    END";
}

function get_branch_for_loan_value($branch_value)
{
    if ($branch_value === null || $branch_value === '') {
        return null;
    }

    $ci =& get_instance();
    $ci->load->database();
    $escaped = $ci->db->escape($branch_value);
    $sql = "SELECT * FROM branches WHERE id = {$escaped} OR Code = {$escaped} OR BranchCode = {$escaped} LIMIT 1";
    $query = safe_db_query($sql);
    return $query ? $query->row() : null;
}

=======
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
function get_paid_date($table,$key1,$key2){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM $table  WHERE loan_id = '$key1'   && payment_number = '$key2' ";
    return $query = $ci->db->query($sql)->row();


}
function get_by_id2($table,$where){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM $table  WHERE $where";
    $query = safe_db_query($sql);
    return $query ? $query->row() : null;


}
function get_all_by_id($table,$key,$value){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM $table  WHERE $key = '$value'";
    $query = safe_db_query($sql);
    return $query ? $query->result() : array();


}
function get_all_loan_balances_by_product($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM payement_schedules LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id LEFT JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID' AND loan.loan_product= '$product' AND loan.loan_status = 'ACTIVE'";
    return $query = $ci->db->query($sql)->result();
}
function get_collected_interest_product($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(interest) as total FROM `payement_schedules` LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id WHERE status = 'PAID' AND  loan.loan_product= '$product' ";
    return $query = $ci->db->query($sql)->row();
}
//uncollected
function get_all_interest_product($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(interest) as total FROM `payement_schedules` LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id WHERE status = 'PAID' AND  loan.loan_product= '$product' ";
    return $query = $ci->db->query($sql)->row();
}
function get_collected_admin_fee_product($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(padmin_fee) as total FROM `payement_schedules` LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id WHERE status = 'PAID' AND   loan.loan_product= '$product' ";
    return $query = $ci->db->query($sql)->row();
}
function get_all_over_due_payments($loanid){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

            $sql="SELECT SUM(principal) AS total_arrears
        FROM payement_schedules
        WHERE loan_id = '$loanid'
        AND status = 'NOT PAID'
        AND payment_schedule <= DATE(NOW());
        ";
    return $query = $ci->db->query($sql)->row();
}

function get_all_not_payments($loanid){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(principal) AS total_arrears
        FROM payement_schedules
        WHERE loan_id = '$loanid'
        AND status = 'NOT PAID';
        ";
    return $query = $ci->db->query($sql)->row();
}
function get_all_total_unpayments() {
    $ci =& get_instance();
    $ci->load->database();

    $sql = "SELECT 
                SUM(ps.amount) AS total_amount,
                SUM(ps.paid_amount) AS total_paid,
                SUM(ps.amount) - SUM(ps.paid_amount) AS total_unpaid
            FROM 
                payement_schedules ps
            JOIN 
                loan l ON l.loan_id = ps.loan_id
            WHERE 
                l.loan_status = 'ACTIVE'";

    return $ci->db->query($sql)->row();
}
function get_all_total_unpayments_by_product($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT 
    SUM(principal) AS total_not_paid
FROM 
    payement_schedules PS
JOIN 
    loan L ON PS.loan_id = L.loan_id
WHERE 
    PS.status = 'NOT PAID'
    AND L.loan_product= '$product'";
    return $query = $ci->db->query($sql)->row();
}
//uncollected admin
function get_all_total_unpayments_by_officer($officer){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT 
    SUM(principal) AS total_not_paid
FROM 
    payement_schedules PS
JOIN 
    loan L ON PS.loan_id = L.loan_id
WHERE 
    PS.status = 'NOT PAID'
    AND L.loan_added_by= '$officer';
        ";
    return $query = $ci->db->query($sql)->row();
}
function get_all_admin_fee_product($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(padmin_fee) as total FROM `payement_schedules` LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id WHERE    loan.loan_product= '$product' ";
    return $query = $ci->db->query($sql)->row();
}
function get_collected_lc_fee_product($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ploan_cover) as total FROM `payement_schedules` LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id WHERE status = 'PAID' AND   loan.loan_product= '$product' ";
    return $query = $ci->db->query($sql)->row();
}
function get_all_lc_fee_product($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ploan_cover) as total FROM `payement_schedules` LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id WHERE   loan.loan_product= '$product' ";
    return $query = $ci->db->query($sql)->row();
}
function institutional_portfolio(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE'";
    return $query = $ci->db->query($sql)->result();
}
function outstanding_sb_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.amount) as totals FROM payement_schedules ps LEFT JOIN loan l ON l.loan_id = ps.loan_id WHERE l.loan_status = 'ACTIVE' AND ps.status = 'NOT PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function outstanding_interest_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.interest) as totals FROM payement_schedules ps LEFT JOIN loan l ON l.loan_id = ps.loan_id WHERE l.loan_status = 'ACTIVE' AND ps.status = 'NOT PAID' ";
    return $query = $ci->db->query($sql)->row();
}function outstanding_p_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.principal) as totals FROM payement_schedules ps LEFT JOIN loan l ON l.loan_id = ps.loan_id WHERE l.loan_status = 'ACTIVE' AND ps.status = 'NOT PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function paid_ps_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.principal) as totals FROM payement_schedules ps LEFT JOIN loan l ON l.loan_id = ps.loan_id WHERE  ps.status = 'PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function paid_sm_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.amount) as totals FROM payement_schedules ps JOIN loan l ON l.loan_id = ps.loan_id WHERE  ps.status = 'PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function paid_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.paid_amount) as totals FROM payement_schedules ps JOIN loan l ON l.loan_id = ps.loan_id WHERE  ps.status = 'PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function paid_interest_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.interest) as totals FROM payement_schedules ps JOIN loan l ON l.loan_id = ps.loan_id WHERE  ps.status = 'PAID' ";
    return $query = $ci->db->query($sql)->row();
}function paid_lc_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.ploan_cover) as totals FROM payement_schedules ps JOIN loan l ON l.loan_id = ps.loan_id WHERE  ps.status = 'PAID' ";
    return $query = $ci->db->query($sql)->row();
}function paid_af_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.padmin_fee) as totals FROM payement_schedules ps JOIN loan l ON l.loan_id = ps.loan_id WHERE  ps.status = 'PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function outstanding_lc_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.ploan_cover) as totals FROM payement_schedules ps JOIN loan l ON l.loan_id = ps.loan_id WHERE l.loan_status = 'ACTIVE' AND ps.status = 'NOT PAID' ";
    return $query = $ci->db->query($sql)->row();
}function outstanding_af_balances(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.padmin_fee) as totals FROM payement_schedules ps JOIN loan l ON l.loan_id = ps.loan_id WHERE l.loan_status = 'ACTIVE' AND ps.status = 'NOT PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function institutional_arrears(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT l.loan_id, SUM(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < CURDATE() THEN (ps.amount - COALESCE(ps.paid_amount, 0)) ELSE 0 END) as amount FROM loan l LEFT JOIN payement_schedules ps ON ps.loan_id = l.loan_id WHERE l.loan_status IN ('APPROVED', 'ACTIVE') AND l.disbursed = 'Yes' GROUP BY l.loan_id HAVING SUM(CASE WHEN ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule < CURDATE() THEN 1 ELSE 0 END) > 0";
    return $query = $ci->db->query($sql)->result();
}
function institutional_arrears_today(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE' AND payement_schedules.payment_schedule = SUBDATE(CURDATE(),1)";
    return $query = $ci->db->query($sql)->result();
}
function institutional_arrears_threedays(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE' AND payement_schedules.payment_schedule BETWEEN SUBDATE(CURDATE(),3) AND SUBDATE(CURDATE(),1)";
    return $query = $ci->db->query($sql)->result();
}
function institutional_arrears_week(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT *, payement_schedules.id as psid FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE' AND payment_schedule < CURDATE() AND  payement_schedules.payment_schedule BETWEEN SUBDATE(CURDATE(),7) AND SUBDATE(CURDATE(),1)";
    return $query = $ci->db->query($sql)->result();
}
function institutional_arrears_month(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT *, payement_schedules.id as psid FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE' AND payment_schedule < CURDATE() AND  payement_schedules.payment_schedule BETWEEN SUBDATE(CURDATE(),30) AND SUBDATE(CURDATE(),1)";
    return $query = $ci->db->query($sql)->result();
}
function institutional_arrears_2month(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT *, payement_schedules.id as psid FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE' AND payment_schedule < CURDATE() AND  payement_schedules.payment_schedule BETWEEN SUBDATE(CURDATE(),60) AND SUBDATE(CURDATE(),1)";
    return $query = $ci->db->query($sql)->result();
}
function institutional_arrears_3month(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT *, payement_schedules.id as psid FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE' AND payment_schedule < CURDATE() AND  payement_schedules.payment_schedule BETWEEN SUBDATE(CURDATE(),90) AND SUBDATE(CURDATE(),1)";
    return $query = $ci->db->query($sql)->result();
}

function payments_today(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');
    $ci->load->model('Payement_schedules_model');
    $query = $ci->Payement_schedules_model->payment_today();

//    $sql="SELECT *, payement_schedules.id as psid FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE' AND payment_schedule = CURDATE()";
    return $query ;
}
function payments_week(){
    $ci =& get_instance();
    $ci->load->database();
    $ci->load->model('Payement_schedules_model');
    $query = $ci->Payement_schedules_model->payment_week();

//    $sql="SELECT *, payement_schedules.id as psid FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE' AND payement_schedules.payment_schedule BETWEEN adddate(curdate(), 7) AND adddate(curdate(), 1)";
    return $query ;
}
function payments_month(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');
    $ci->load->model('Payement_schedules_model');
    $query = $ci->Payement_schedules_model->payment_month();
//    $sql="SELECT *, payement_schedules.id as psid FROM payement_schedules  JOIN loan ON loan.loan_id = payement_schedules.loan_id JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE payement_schedules.status = 'NOT PAID'  AND loan.loan_status = 'ACTIVE' AND payement_schedules.payment_schedule BETWEEN subdate(curdate(), 30) AND subdate(curdate(), 1)";
    return $query ;
}
function check_paid_fees($loan_id){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM transactions WHERE loan_id = $loan_id AND transaction_type= '1'";
    return $query = $ci->db->query($sql)->result();


}
function get_active_loan(){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM loan WHERE loan_status = 'ACTIVE'";
    return $query = $ci->db->query($sql)->result();


}
function get_logs($table,$key,$value){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM $table  WHERE $key = $value LIMIT 5";
    return $query = $ci->db->query($sql)->result();


}
function get_all_features($value){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM listing_features JOIN features ON features.feature_id = listing_features.feature_id WHERE listing_features.listing_id = '$value'";
    return $query = $ci->db->query($sql)->result();


}
function get_features(){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM features ";
    return $query = $ci->db->query($sql)->result();


}
function get_listing_images($id){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM listing_images WHERE listing_images.listing_id = '$id'";

    return $query = $ci->db->query($sql)->result();


}
function get_recent(){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM  listings  JOIN districts ON districts.district_id = listings.listing_location  JOIN release_type ON release_type.rtype_id = listings.rtype JOIN categories ON categories.category_id = listings.listing_category LIMIT 5";

    return $query = $ci->db->query($sql)->result();


}
function get_similar(){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM  listings  JOIN districts ON districts.district_id = listings.listing_location  JOIN release_type ON release_type.rtype_id = listings.rtype JOIN categories ON categories.category_id = listings.listing_category LIMIT 5";
    return $query = $ci->db->query($sql)->result();


}
function get_featured(){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM  listings  JOIN districts ON districts.district_id = listings.listing_location  JOIN categories ON categories.category_id= listings.listing_category  JOIN release_type ON release_type.rtype_id = listings .rtype WHERE  is_featured= 'YES' LIMIT 5";
    return $query = $ci->db->query($sql)->result();


}
function check_exist_in_table($table,$field,$key){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM $table WHERE $field='$key'";
    return $query = $ci->db->query($sql)->row();

}
function delete_record($table,$field,$key){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="DELETE FROM $table WHERE $field='$key'";
    return $query = $ci->db->query($sql);

}
function delete_wrong_loans(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM `loan` JOIN all_indi ON loan.loan_customer = all_indi.customer_id WHERE all_indi.rerun = 'Yes' ";
    return $query = $ci->db->query($sql)->result();


}
function monthly_interest(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(payement_schedules.interest) as totals
FROM `payement_schedules`
LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id
WHERE status = 'PAID'
AND payement_schedules.paid_date IS NOT NULL
AND YEAR(payement_schedules.paid_date) = YEAR(CURDATE())
AND MONTH(payement_schedules.paid_date) = MONTH(CURDATE()) ";
    return $query = $ci->db->query($sql)->row();

}function monthly_af(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(payement_schedules.padmin_fee) as totals
FROM `payement_schedules`
LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id
WHERE status = 'PAID'
AND payement_schedules.paid_date IS NOT NULL
AND YEAR(payement_schedules.paid_date) = YEAR(CURDATE())
AND MONTH(payement_schedules.paid_date) = MONTH(CURDATE()) ";
    return $query = $ci->db->query($sql)->row();

}function monthly_lc(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(payement_schedules.ploan_cover) as totals
FROM `payement_schedules`
LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id
WHERE status = 'PAID'
AND payement_schedules.paid_date IS NOT NULL
AND YEAR(payement_schedules.paid_date) = YEAR(CURDATE())
AND MONTH(payement_schedules.paid_date) = MONTH(CURDATE()) ";
    return $query = $ci->db->query($sql)->row();

}
function yearly_interest(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(payement_schedules.interest) as totals
FROM `payement_schedules`
LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id
WHERE status = 'PAID'
AND payement_schedules.paid_date IS NOT NULL
AND YEAR(payement_schedules.paid_date) = YEAR(CURDATE())
 ";
    return $query = $ci->db->query($sql)->row();

}
function yearly_af(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(payement_schedules.padmin_fee) as totals
FROM `payement_schedules`
LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id
WHERE status = 'PAID'
AND payement_schedules.paid_date IS NOT NULL
AND YEAR(payement_schedules.paid_date) = YEAR(CURDATE())
 ";
    return $query = $ci->db->query($sql)->row();

}
function yearly_lc(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(payement_schedules.ploan_cover) as totals
FROM `payement_schedules`
LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id
WHERE status = 'PAID'
AND payement_schedules.paid_date IS NOT NULL
AND YEAR(payement_schedules.paid_date) = YEAR(CURDATE())
 ";
    return $query = $ci->db->query($sql)->row();

}
function pyearly_interest(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(payement_schedules.interest) as totals
FROM `payement_schedules`
LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id
WHERE status = 'PAID'
AND payement_schedules.paid_date IS NOT NULL
AND YEAR(payement_schedules.paid_date) = YEAR(CURDATE()) - 1
 ";
    return $query = $ci->db->query($sql)->row();

}
function pyearly_af(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(payement_schedules.padmin_fee) as totals
FROM `payement_schedules`
LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id
WHERE status = 'PAID'
AND payement_schedules.paid_date IS NOT NULL
AND YEAR(payement_schedules.paid_date) = YEAR(CURDATE()) - 1
 ";
    return $query = $ci->db->query($sql)->row();

}function pyearly_lc(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(payement_schedules.ploan_cover) as totals
FROM `payement_schedules`
LEFT JOIN loan ON loan.loan_id = payement_schedules.loan_id
WHERE status = 'PAID'
AND payement_schedules.paid_date IS NOT NULL
AND YEAR(payement_schedules.paid_date) = YEAR(CURDATE()) - 1
 ";
    return $query = $ci->db->query($sql)->row();

}
function processing_fees($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM((loan.loan_principal)*(loan_products.processing_fees/100)) as total_fees FROM `loan` JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE loan.disbursed = 'Yes' AND loan.loan_product !=6  AND   loan.loan_product= '$product'
 ";
    return $query = $ci->db->query($sql)->row();

}
function all_processing_fees($product){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM((loan.loan_principal)*(loan_products.processing_fees/100)) as total_fees FROM `loan` JOIN loan_products ON loan_products.loan_product_id = loan.loan_product WHERE loan.disbursed = 'Yes' AND loan.loan_product !=6  AND   loan.loan_product= '$product'";
    return $query = $ci->db->query($sql)->row();

}
function processing_fees_month(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM((loan.loan_principal) * (loan_products.processing_fees / 100)) as total_fees
FROM `loan`
JOIN loan_products ON loan_products.loan_product_id = loan.loan_product
WHERE loan.disbursed = 'Yes'
  AND loan.loan_product != 6
  AND YEAR(loan.disbursed_date) = YEAR(CURDATE())
  AND MONTH(loan.disbursed_date) = MONTH(CURDATE())
 ";
    return $query = $ci->db->query($sql)->row();

}
function processing_fees_year(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM((loan.loan_principal) * (loan_products.processing_fees / 100)) as total_fees
FROM `loan`
JOIN loan_products ON loan_products.loan_product_id = loan.loan_product
WHERE loan.disbursed = 'Yes'
  AND loan.loan_product != 6
  AND YEAR(loan.disbursed_date) = YEAR(CURDATE())
  
 ";
    return $query = $ci->db->query($sql)->row();

}
function processing_fees_pyear(){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM((loan.loan_principal) * (loan_products.processing_fees / 100)) as total_fees
FROM `loan`
JOIN loan_products ON loan_products.loan_product_id = loan.loan_product
WHERE loan.disbursed = 'Yes'
  AND loan.loan_product != 6
  AND YEAR(loan.disbursed_date) = YEAR(CURDATE())-1
  
 ";
    return $query = $ci->db->query($sql)->row();

}
function get_all_where($table, $where, $order_by = null, $order_direction = 'ASC') {
    $ci =& get_instance();
    $ci->load->database();

    $sql = "SELECT * FROM $table WHERE $where";

    // If order by parameters are provided, add them to the SQL query
    if ($order_by !== null) {
        $sql .= " ORDER BY $order_by $order_direction";
    }

    // Execute the SQL query and return the results
    return $ci->db->query($sql)->result();
}
function rbm_report($page_number = 1, $rows_per_page = 10) {
    $ci =& get_instance();
    $ci->load->database();

    $offset = ($page_number - 1) * $rows_per_page;

    $sql = "SELECT * FROM individual_customers 
            INNER JOIN proofofidentity ON proofofidentity.ClientID = individual_customers.ClientID 
            INNER JOIN loan ON loan.loan_customer = individual_customers.id 
            ORDER BY loan.loan_id DESC 
            LIMIT ?, ?";

    $query = $ci->db->query($sql, array($offset, $rows_per_page));
    $results = $query->result();

    // Get the total number of rows
    $total_rows_query = $ci->db->query("SELECT COUNT(*) as total_rows FROM individual_customers 
                                        INNER JOIN proofofidentity ON proofofidentity.ClientID = individual_customers.ClientID 
                                        INNER JOIN loan ON loan.loan_customer = individual_customers.id");
    $total_rows = $total_rows_query->row()->total_rows;

    return ['results' => $results, 'total_rows' => $total_rows];
}


function get_previous_loan($customerID){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM loan WHERE loan_customer = $customerID AND loan_id < (SELECT MAX(loan_id) FROM loan WHERE loan_customer = $customerID) ORDER BY loan_id DESC LIMIT 1 ";
    return $query = $ci->db->query($sql)->row();


}
function get_allLoanPayRBM_by_id($loanid,$paymentnumber){


    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT * FROM payement_schedules WHERE loan_id=$loanid AND payment_number=$paymentnumber ";
    return $query = $ci->db->query($sql)->row();


}
function get_amount_of_arreas($loadID){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.amount)  AS amount_arrears
                FROM loan l
                JOIN payement_schedules ps ON l.loan_id = ps.loan_id
                WHERE l.loan_id = '$loadID'
                  AND l.loan_status = 'Active'
                  AND ps.payment_schedule < CURDATE()
                  AND ps.status = 'NOT PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function get_amount_of_arreas_paid($loadID){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(ps.paid_amount)  AS amount_arrears
                FROM loan l
                JOIN payement_schedules ps ON l.loan_id = ps.loan_id
                WHERE l.loan_id = '$loadID'
                  AND l.loan_status = 'Active'
                  AND ps.payment_schedule < CURDATE()
                  AND ps.status = 'NOT PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function get_days_of_arreas($loadID){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT DATEDIFF(CURRENT_DATE(), MAX(ps.payment_schedule)) AS days_in_arrears
            FROM loan l
            JOIN payement_schedules ps ON l.loan_id = ps.loan_id
            WHERE l.loan_id = '$loadID'
            AND payment_schedule <  CURDATE()
          AND l.loan_status = 'Active'
         AND ps.status = 'NOT PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function get_number_of_arreas($loadID){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT COUNT(*) AS num_arrears
                FROM loan l
                JOIN payement_schedules ps ON l.loan_id = ps.loan_id
                WHERE l.loan_id = '$loadID'
                  AND l.loan_status = 'Active'
                  AND ps.payment_schedule < CURDATE()
                  AND ps.status = 'NOT PAID' ";
    return $query = $ci->db->query($sql)->row();
}
function loan_collection($loan){
    $ci =& get_instance();
    $ci->load->database();
//	$ci->load->model('Dbc_users_model');

    $sql="SELECT SUM(paid_amount) as total FROM `payement_schedules` WHERE loan_id = '$loan' ";
    return $query = $ci->db->query($sql)->row();
}

function get_loan_outstanding_balance($loan_id) {
<<<<<<< HEAD
    $CI =& get_instance();

=======
    // Get CI instance
    $CI =& get_instance();

    // Load database if not already loaded
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
    if (!isset($CI->db)) {
        $CI->load->database();
    }

<<<<<<< HEAD
    $CI->load->model('Payement_schedules_model');
    $payments = $CI->Payement_schedules_model->get_all_by_id($loan_id);
    if (empty($payments)) {
        return 0;
    }

    $loan = $CI->db->select('loan_amount_total')->from('loan')->where('loan_id', $loan_id)->get()->row();
    $summary = $CI->Payement_schedules_model->summarize_loan_balances(
        $payments,
        $loan ? $loan->loan_amount_total : null
    );

    return $summary->remaining_balance;
=======
    // Get sum of all scheduled amounts and paid amounts for this loan
    $query = $CI->db->select('SUM(amount) as total_amount, SUM(paid_amount) as total_paid')
        ->from('payement_schedules')  // Replace with your actual payment schedules table name
        ->where('loan_id', $loan_id)
        ->get();

    if ($query->num_rows() == 0) {
        return 0; // No payment schedules found for this loan
    }

    $result = $query->row();

    // Calculate outstanding balance (total amount - total paid)
    $outstanding_balance = $result->total_amount - $result->total_paid;

    // Return the outstanding balance (ensure it's not negative)
    return max(0, $outstanding_balance);
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
}