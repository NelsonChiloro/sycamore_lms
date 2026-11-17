<?php
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH',__DIR__.'/../system/');define('APPPATH',__DIR__.'/../application/');define('ENVIRONMENT','development');
function is_php($version){return version_compare(PHP_VERSION,$version,'>=');}
function log_message($level,$message){}
function show_error($message){throw new RuntimeException($message);}
require BASEPATH.'database/DB.php';require BASEPATH.'core/Model.php';
class CI_Controller extends CI_Model {}
$ci=new stdClass();function &get_instance(){global $ci;return $ci;}
function ensure($ok,$message){if(!$ok)throw new RuntimeException($message);}
$ci->db=DB(array('hostname'=>'127.0.0.1','port'=>3407,'username'=>'root','password'=>'','database'=>'charge_framework_test','dbdriver'=>'mysqli',
    'dbprefix'=>'','pconnect'=>false,'db_debug'=>false,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_unicode_ci'),true);
ensure($ci->db->database==='charge_framework_test','Unsafe test database');
$ci->db->query("CREATE TABLE IF NOT EXISTS individual_customers (id INT PRIMARY KEY,ClientId VARCHAR(50),approval_status VARCHAR(30),added_by INT)");
$ci->db->query("CREATE TABLE IF NOT EXISTS proofofidentity (id INT PRIMARY KEY,ClientId VARCHAR(50),IDNumber VARCHAR(100),IDType VARCHAR(50),IssueDate DATE,ExpiryDate DATE,photograph VARCHAR(100),signature VARCHAR(100),Id_back VARCHAR(100),id_front VARCHAR(100))");
$migration=preg_replace('/^--.*$/m','',file_get_contents(__DIR__.'/../database/20261109_customer_soft_delete.sql'));
ensure($ci->db->query($migration)!==false,'Deleted status migration failed');
require APPPATH.'models/Individual_customers_model.php';require APPPATH.'models/Proofofidentity_model.php';require APPPATH.'controllers/Proofofidentity.php';
$customers=new Individual_customers_model();$ci->Proofofidentity_model=new Proofofidentity_model();
class CustomerTestValidation {public $rules=array();public function set_rules($name,$label,$rules){$this->rules[$name]=$rules;}public function set_error_delimiters($a,$b){}public function run(){return true;}}
class CustomerTestInput {public $data=array();public function post($name,$clean=true){return $this->data[$name]??null;}}
class CustomerTestToast {public function success($message){}public function error($message){throw new RuntimeException($message);}}
class CustomerTestRedirect extends Exception {}
function redirect($url){throw new CustomerTestRedirect();}
$ci->form_validation=new CustomerTestValidation();$ci->input=new CustomerTestInput();$ci->toaster=new CustomerTestToast();
$controller=(new ReflectionClass('Proofofidentity'))->newInstanceWithoutConstructor();
$ci->db->trans_begin();
try {
    foreach(array(991001,991002) as $id) {
        ensure($ci->db->insert('individual_customers',array('id'=>$id,'ClientId'=>'TEST-'.$id,'approval_status'=>'Approved','added_by'=>1))!==false,'Customer fixture');
        $ci->db->insert('proofofidentity',array('id'=>$id,'ClientId'=>'TEST-'.$id,'IDNumber'=>$id===991001?'AB-123':'XY-456',
            'photograph'=>'old-photo.jpg','signature'=>'old-sign.jpg','Id_back'=>'old-back.jpg','id_front'=>'old-front.jpg'));
    }
    ensure($ci->Proofofidentity_model->id_number_exists('ab /123'),'Live identity formatting duplicate missed');
    foreach(array('ACTIVE','CLOSED','WRITTEN_OFF','DELETED') as $status) {
        ensure($ci->db->insert('loan',array('loan_id'=>991003,'loan_customer'=>991002,'customer_type'=>'individual','loan_status'=>$status))!==false,'Loan fixture');
        ensure($customers->has_loan_record(991002),'Loan record was not detected for '.$status);
        ensure(!$customers->delete(991002),'Customer with '.$status.' loan was deleted');
        ensure($customers->get_by_id(991002)->approval_status==='Approved','Blocked delete changed customer status');
        $ci->db->where('loan_id',991003)->delete('loan');
    }
    $ci->db->insert('loan',array('loan_id'=>991003,'loan_customer'=>991001,'customer_type'=>'group','loan_status'=>'ACTIVE'));
    ensure(!$customers->has_loan_record(991001),'Unrelated group ID blocked individual customer');
    ensure($customers->delete(991001),'Soft deletion failed');
    ensure($customers->get_by_id(991001)->approval_status==='Deleted','Customer was not retained as Deleted');
    foreach($customers->get_all() as $row)ensure((int)$row->id!==991001,'Deleted customer appeared in list');
    foreach($customers->get_selective(1) as $row)ensure((int)$row->id!==991001,'Deleted customer appeared in officer list');
    ensure(!$ci->Proofofidentity_model->id_number_exists('AB123'),'Deleted customer still blocks ID reuse');
    ensure($ci->Proofofidentity_model->id_number_exists('XY456'),'Active customer duplicate check disabled');
    $controller->_rules(true);
    foreach(array('photograph','signature','Id_back','id_front') as $field)ensure(strpos($ci->form_validation->rules[$field],'required')===false,'Picture required on edit');
    $ci->input->data=array('id'=>991002,'ClientId'=>'TEST-991002','IDType'=>'National ID','IDNumber'=>'AB123','IssueDate'=>'2020-01-01','ExpiryDate'=>'2030-01-01','photograph'=>'','signature'=>null);
    $_SERVER['HTTP_REFERER']='/test';
    try{$controller->update_action();}catch(CustomerTestRedirect $e){}
    $saved=$ci->Proofofidentity_model->get_by_id(991002);
    ensure($saved->IDNumber==='AB123','Edit without pictures did not save changed data');
    ensure($saved->photograph==='old-photo.jpg' && $saved->signature==='old-sign.jpg' && $saved->Id_back==='old-back.jpg' && $saved->id_front==='old-front.jpg','Missing upload erased existing pictures');
    $controller->_rules();ensure(strpos($ci->form_validation->rules['photograph'],'required')!==false,'Creation upload requirement unexpectedly removed');
    ensure($ci->db->trans_status(),'Customer queries failed');
    echo "PASS: loan-record deletion guards, group-ID isolation, soft-delete migration, retained history, filtered lists, ID reuse, active duplicate detection, optional KYC pictures and preserved existing files.\n";
} finally {$ci->db->trans_rollback();}
