<?php
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH',__DIR__.'/../system/');define('APPPATH',__DIR__.'/../application/');define('ENVIRONMENT','development');
function is_php($v){return version_compare(PHP_VERSION,$v,'>=');}
function log_message($level,$message){}
function show_error($message){throw new RuntimeException($message);}
require BASEPATH.'database/DB.php';require BASEPATH.'core/Model.php';
class CI_Controller extends CI_Model {}
require APPPATH.'models/Employees_model.php';require APPPATH.'models/Branches_model.php';
require APPPATH.'models/Payement_schedules_model.php';require APPPATH.'controllers/Branches.php';
$ci=new stdClass();function &get_instance(){global $ci;return $ci;}
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$ci->db=DB(array('hostname'=>'127.0.0.1','port'=>3407,'username'=>'root','password'=>'','database'=>'charge_framework_test',
    'dbdriver'=>'mysqli','db_debug'=>false,'pconnect'=>false,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_unicode_ci'),true);
check($ci->db->database==='charge_framework_test','Unsafe database');
$ddl=array(
    "CREATE TABLE IF NOT EXISTS branches (id INT AUTO_INCREMENT PRIMARY KEY,Code VARCHAR(200) NOT NULL,BranchCode INT,BranchName VARCHAR(255) NOT NULL,AddressLine1 VARCHAR(200) NOT NULL,AddressLine2 VARCHAR(200) NOT NULL,City VARCHAR(255) NOT NULL,Stamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)",
    'CREATE TABLE IF NOT EXISTS roles (id INT PRIMARY KEY,RoleName VARCHAR(200))',
    'CREATE TABLE IF NOT EXISTS employees (id INT PRIMARY KEY,Firstname VARCHAR(100),Lastname VARCHAR(100),Middlename VARCHAR(100),Role INT,Supervisor INT)'
);
foreach($ddl as $sql)check($ci->db->query($sql)!==false,'Fixture schema: '.json_encode($ci->db->error()));
$ci->db->trans_begin();
try {
    foreach(array(990050=>'Relationship Officer',990051=>'Branch Manager',990052=>'Loan Officer',990053=>'Cashier',990054=>'Relationship Supervisor') as $id=>$name) {
        $ci->db->insert('roles',array('id'=>$id,'RoleName'=>$name));
        $ci->db->insert('employees',array('id'=>$id+100,'Firstname'=>'Fixture '.$id,'Lastname'=>'Tester','Role'=>$id,'Middlename'=>''));
    }
    $employees=new Employees_model();$ids=array_map(function($row){return (int)$row->id;},$employees->get_report_loan_officers());
    foreach(array(990150,990152) as $id)check(in_array($id,$ids,true),'Officer missing / wrong employee ID');
    check(!in_array(990151,$ids,true),'Branch manager incorrectly included among loan officers');
    check(!in_array(990153,$ids,true),'Unrelated cashier included');
    $supervisors=array_map(function($row){return (int)$row->id;},$employees->get_report_supervisors());
    check(in_array(990151,$supervisors,true) && in_array(990154,$supervisors,true),'Supervisor or branch manager missing');
    check(!in_array(990150,$supervisors,true) && !in_array(990153,$supervisors,true),'Officer/cashier incorrectly included among supervisors');
    $branch=new Branches_model();
    check($branch->insert(array('BranchCode'=>990050,'BranchName'=>'Fixture Branch','City'=>'Test','AddressLine1'=>'','AddressLine2'=>'')),'Create failed');
    $id=$ci->db->insert_id();$original=$branch->get_by_id($id);
    check($original && $original->Stamp && $original->Code,'Created branch missing internal code/stamp');
    check($branch->value_exists('BranchCode','990050'),'Duplicate code not detected');
    check($branch->value_exists('BranchName',' fixture branch '),'Duplicate name not detected');
    check(!$branch->value_exists('BranchCode',990050,$id),'Unchanged branch code rejected');
    check($branch->update($id,array('BranchName'=>'Updated Fixture','City'=>'Changed','Stamp'=>null,'Code'=>'FORGED')),'Update failed');
    $updated=$branch->get_by_id($id);
    check($updated->Code===$original->Code && $updated->Stamp && $updated->City==='Changed','Update corrupted internal code or timestamp');
    check($branch->update($id,array('BranchName'=>'Updated Fixture')),'Unchanged update reported as failure');
    // Actual supplied totals: contract balance includes charges, principal does not.
    $payments=array((object)array('payment_number'=>5,'amount'=>4316896.15,'paid_amount'=>4316896.15,'loan_balance'=>767448.21),
        (object)array('payment_number'=>6,'amount'=>863379.23,'paid_amount'=>768003.85,'loan_balance'=>84778.12));
    $display=Payement_schedules_model::with_contract_balances($payments);
    check(abs($display[1]->contract_balance-95375.38)<0.001,'Wrong contract balance');
    check($payments[1]->loan_balance===84778.12 && !isset($payments[1]->contract_balance),'Stored principal source changed');
    $summary=(new Payement_schedules_model())->summarize_loan_balances($payments,5180275.38);
    check(abs($summary->remaining_balance-$display[1]->contract_balance)<0.001,'Summary/schedule balances disagree');
    $reverse=Payement_schedules_model::with_contract_balances(array_reverse($payments));
    check(abs($reverse[1]->contract_balance-95375.38)<0.001,'Unordered schedules changed remaining balance');
    echo "PASS: supervisors include branch managers; loan officers exclude branch managers; correct employee IDs; branch CRUD checks and consistent contract balance.\n";
} finally {$ci->db->trans_rollback();}
