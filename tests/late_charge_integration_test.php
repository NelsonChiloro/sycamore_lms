<?php
// Dedicated disposable database only. Never point this at an application DB.
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH',__DIR__.'/../system/');
define('APPPATH',__DIR__.'/../application/');
define('ENVIRONMENT','development');
function is_php($v){return version_compare(PHP_VERSION,$v,'>=');}
function log_message($level,$message){}
function show_error($message){throw new RuntimeException($message);}
require BASEPATH.'database/DB.php';
require BASEPATH.'core/Model.php';
require APPPATH.'models/Late_charges_model.php';
require APPPATH.'models/Payement_schedules_model.php';
require APPPATH.'models/Loan_model.php';
function check_exist_in_table($table,$field,$value) {
    if($table!=='loan_products' || (int)$value!==990099)throw new RuntimeException('Unexpected product fixture');
    return (object)array('interest'=>0,'processing_fees'=>25,'admin_fees'=>0,'loan_cover'=>0,'frequency'=>'Weekly','product_name'=>'Zitsamba','product_code'=>'ZtSBT');
}
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function money($a,$b,$message){check(abs($a-$b)<.01,$message.': '.$a.' vs '.$b);}
class TestLoad { public function model($name){} }
class TestSession {public $role=1;public function userdata($key){return $key==='RoleName'?$this->role:1;}}
$ci=new stdClass();
function &get_instance(){global $ci;return $ci;}
$ci->load=new TestLoad(); $ci->session=new TestSession();
$ci->db=DB(array('hostname'=>'127.0.0.1','port'=>(int)(getenv('CHARGE_TEST_PORT')?:3407),
    'username'=>'root','password'=>'','database'=>'charge_framework_test','dbdriver'=>'mysqli',
    'dbprefix'=>'','pconnect'=>false,'db_debug'=>false,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_unicode_ci'),true);
check($ci->db->database==='charge_framework_test','Unsafe test DB');
$ddl=array(
    'CREATE TABLE IF NOT EXISTS loan (loan_id INT PRIMARY KEY,loan_status VARCHAR(20),loan_principal DECIMAL(18,2),loan_amount_total DECIMAL(18,2),next_payment_id INT)',
    'CREATE TABLE IF NOT EXISTS payement_schedules (id INT PRIMARY KEY AUTO_INCREMENT,loan_id INT,payment_number INT,payment_schedule DATE,amount DECIMAL(18,2),paid_amount DECIMAL(18,2),principal DECIMAL(18,2),interest DECIMAL(18,2),padmin_fee DECIMAL(18,2),ploan_cover DECIMAL(18,2),total_late_charge DECIMAL(18,2) DEFAULT 0,loan_balance DECIMAL(18,2),status VARCHAR(20),partial_paid VARCHAR(5),paid_date DATETIME NULL)',
    'CREATE TABLE IF NOT EXISTS transactions (transaction_id INT PRIMARY KEY AUTO_INCREMENT,loan_id INT,payment_number INT,ref VARCHAR(200),amount DECIMAL(18,2),transaction_type INT,description VARCHAR(250),payment_proof VARCHAR(20),added_by INT,date_stamp DATETIME,reference VARCHAR(200),payment_reference VARCHAR(255),payment_type VARCHAR(10),method INT)',
    'CREATE TABLE IF NOT EXISTS transaction (id INT PRIMARY KEY AUTO_INCREMENT,transaction_id VARCHAR(200),reversed VARCHAR(10))'
);
foreach($ddl as $sql)check($ci->db->query($sql)!==false,'Test schema');
if (!$ci->db->field_exists('paid_off','loan')) $ci->db->query("ALTER TABLE loan ADD paid_off VARCHAR(5) DEFAULT 'NO'");
if (!$ci->db->field_exists('loan_number','loan')) $ci->db->query('ALTER TABLE loan ADD loan_number VARCHAR(200) NULL');
foreach(array('account_number'=>'VARCHAR(200)','credit'=>'DECIMAL(18,2)','system_time'=>'DATETIME','transaction_type'=>"VARCHAR(20) DEFAULT 'other'",'debit'=>'DECIMAL(18,2)','balance'=>'DECIMAL(18,2)') as $field=>$type) {
    if (!$ci->db->field_exists($field,'transaction')) $ci->db->query('ALTER TABLE transaction ADD '.$field.' '.$type.' NULL');
}
foreach(array('loan_product'=>'INT','loan_customer'=>'INT','customer_type'=>'VARCHAR(20)','loan_date'=>'DATE','loan_period'=>'INT',
    'worthness_file'=>'VARCHAR(100)','narration'=>'TEXT','period_type'=>'VARCHAR(20)','loan_amount_term'=>'DECIMAL(18,2)',
    'loan_interest'=>'DECIMAL(18,2)','loan_interest_amount'=>'DECIMAL(18,2)','admin_fee'=>'DECIMAL(18,2)',
    'admin_fees_amount'=>'DECIMAL(18,2)','loan_cover'=>'DECIMAL(18,2)','loan_cover_amount'=>'DECIMAL(18,2)','loan_added_by'=>'INT','counter'=>'INT') as $field=>$type) {
    if(!$ci->db->field_exists($field,'loan'))check($ci->db->query('ALTER TABLE loan ADD '.$field.' '.$type.' NULL')!==false,'Loan generator schema');
}
foreach(array('customer'=>'INT','customer_type'=>'VARCHAR(20)','loan_date'=>'DATE') as $field=>$type) {
    if(!$ci->db->field_exists($field,'payement_schedules'))check($ci->db->query('ALTER TABLE payement_schedules ADD '.$field.' '.$type.' NULL')!==false,'Schedule generator schema');
}
$migration=file_get_contents(__DIR__.'/../database/20261022_late_charge_framework.sql');
$migration=preg_replace('/^--.*$/m','',$migration);
foreach(explode(';',$migration) as $sql)if(trim($sql)!=='')check($ci->db->query($sql)!==false,'Migration: '.json_encode($ci->db->error()));
$migration=preg_replace('/^--.*$/m','',file_get_contents(__DIR__.'/../database/20261022_historical_penalties.sql'));
foreach(explode(';',$migration) as $sql)if(trim($sql)!=='')check($ci->db->query($sql)!==false,'Historical migration');
$migration=preg_replace('/^--.*$/m','',file_get_contents(__DIR__.'/../database/20261022_bulk_history_dates.sql'));
foreach(explode(';',$migration) as $sql)if(trim($sql)!=='')check($ci->db->query($sql)!==false,'Date repair migration');
$ci->Late_charges_model=new Late_charges_model(); $model=new Payement_schedules_model();
$ci->Payement_schedules_model=$model;
$ci->db->trans_begin();
try {
    $ci->db->where('id',1)->update('loan_charge_policy',array('starts_on'=>'2026-10-01'));
    $ci->db->insert('loan',array('loan_id'=>990008,'loan_status'=>'ACTIVE','loan_principal'=>800000,'loan_amount_total'=>1004807.69,'next_payment_id'=>1));
    $generator=new Loan_model();
    $generator->add_amortization_straight_weekly_edit(990008,800000,1000000,990099,8,'2023-10-19',4,'group','','',1,'Weekly');
    check($ci->db->trans_status(),'Actual restructure generator rejected corrected components');
    $generated=$ci->db->where('loan_id',990008)->order_by('payment_number')->get('payement_schedules')->result();
    check(count($generated)===8,'Restructure generated wrong installment count');
    $sums=array('principal'=>0,'amount'=>0,'padmin_fee'=>0);
    foreach($generated as $schedule) foreach($sums as $field=>$value)$sums[$field]+=(float)$schedule->$field;
    money($sums['principal'],800000,'Generated principal excludes processing fees');
    money($sums['amount'],1004807.69,'Generated amounts match stored contract');
    money($sums['padmin_fee'],204807.69,'Generated fees account for contract excess');
    $ci->db->insert('transactions',array('loan_id'=>990008,'payment_number'=>1,'ref'=>'FEE-TEST','amount'=>125600.96,'transaction_type'=>3,'date_stamp'=>'2023-11-02','description'=>'charge_policy_v1'));
    $fixed_components=$model->repair_loan_payment_state(990008);
    check(!isset($fixed_components['error']),json_encode($fixed_components));
    $first=$ci->db->where('loan_id',990008)->where('payment_number',1)->get('payement_schedules')->row();
    money($first->loan_balance,700000,'Financed fee payments do not reduce actual principal');
    echo "PASS: actual restructure generator reconciles MWK800,000 principal, financed fees, contract totals and allocated principal balance.\n";
    check(!$ci->db->where('loan_id',990001)->count_all_results('loan'),'Test fixture already exists');
    $ci->db->insert('loan',array('loan_id'=>990001,'loan_status'=>'ACTIVE','loan_principal'=>140000,'loan_amount_total'=>200000,'next_payment_id'=>1));
    foreach(array(1,2) as $n)$ci->db->insert('payement_schedules',array('loan_id'=>990001,'payment_number'=>$n,'payment_schedule'=>$n===1?'2026-10-01':'2026-10-05',
        'amount'=>100000,'paid_amount'=>0,'principal'=>70000,'interest'=>15000,'padmin_fee'=>10000,'ploan_cover'=>5000,'loan_balance'=>140000,'status'=>'NOT PAID','partial_paid'=>'NO'));
    $charges=$ci->Late_charges_model;
    $charges->refresh_loan(990001,'2026-10-03');
    $s=$charges->refresh_loan(990001,'2026-10-04'); money($s['charges_added'],333.333333,'Grace transition');
    money($charges->refresh_loan(990001,'2026-10-04')['charges_added'],0,'Idempotent daily job');
    $r=$model->pay_loan_with_late_charges(990001,1,10333.33,'2026-10-04 10:00:00','TEST-PAY-1');check(!empty($r['success']),json_encode($r));
    $state=$ci->db->where('loan_id',990001)->where('payment_number',1)->get('loan_charge_state')->row();
    money($state->paid_cover,5000,'Cover'); money($state->paid_admin,5000,'Admin');money($state->paid_principal,0,'Principal must remain unpaid');
    $row=$ci->db->where('loan_id',990001)->where('payment_number',1)->get('payement_schedules')->row();money($row->paid_amount,10000,'Penalty excluded from contract');money($row->loan_balance,140000,'Principal balance unchanged until principal receives payment');
    $s=$charges->refresh_loan(990001,'2026-10-05');money($s['charges_added'],300,'Partial installment charge base');
    $r=$model->pay_loan_with_late_charges(990001,1,10300,'2026-10-05 10:00:00','TEST-PAY-2');check(!empty($r['success']),json_encode($r));
    $states=$ci->db->where('loan_id',990001)->order_by('payment_number')->get('loan_charge_state')->result();
    money($states[1]->paid_cover,0,'Next installment waits for arrears settlement');money($states[0]->paid_admin,10000,'Arrears admin');
    $bad=$model->pay_loan_with_late_charges(990001,1,100,'2026-10-04','BACKDATED');check(empty($bad['success']),'Backdated accrual rewrite accepted');
    $ci->db->insert('transaction',array('transaction_id'=>'TEST-PAY-1','reversed'=>'Yes'));
    $repair=$model->repair_loan_payment_state(990001);check(!isset($repair['error']),json_encode($repair));money($repair['applied'],10000,'Reversal excludes contract allocation');
    $state=$ci->db->where('loan_id',990001)->where('payment_number',1)->get('loan_charge_state')->row();money($state->penalty_paid,300,'Reversal restores penalty debt');
    $repair2=$model->repair_loan_payment_state(990001);money($repair2['applied'],$repair['applied'],'Repeat repair');
    // Rebuild with fresh IDs, as the restructure generator does.
    $rebuilt=$ci->db->where('loan_id',990001)->order_by('payment_number')->get('payement_schedules')->result_array();
    $ci->db->where('loan_id',990001)->delete('payement_schedules');
    foreach($rebuilt as $row) {unset($row['id']);$row['paid_amount']=0;$row['total_late_charge']=0;$ci->db->insert('payement_schedules',$row);}
    $repair3=$model->repair_loan_payment_state(990001);check(!isset($repair3['error']),json_encode($repair3));money($repair3['applied'],10000,'Rebuilt schedules recover component payments');
    $rows=$ci->db->where('loan_id',990001)->order_by('payment_number')->get('payement_schedules')->result();
    $quote=$model->calculate_payoff($rows);
    check($model->apply_payoff(990001,$quote,'2026-10-05 12:00:00','TEST-PAYOFF'),'Payoff failed');
    $rows=$ci->db->where('loan_id',990001)->order_by('payment_number')->get('payement_schedules')->result();
    foreach($rows as $row){money($row->total_late_charge,0,'Payoff penalties');money($row->paid_amount,$row->amount,'Payoff contract');}
    money(end($rows)->loan_balance,0,'Payoff principal');
    // Historical allocations may reference obsolete installment numbers.
    $ci->db->insert('loan',array('loan_id'=>990002,'loan_status'=>'ACTIVE','loan_principal'=>140000,'loan_amount_total'=>200000,'next_payment_id'=>1));
    foreach(array(1,2) as $n)$ci->db->insert('payement_schedules',array('loan_id'=>990002,'payment_number'=>$n,'payment_schedule'=>'2026-10-01',
        'amount'=>100000,'paid_amount'=>0,'principal'=>70000,'interest'=>15000,'padmin_fee'=>10000,'ploan_cover'=>5000,'loan_balance'=>140000,'status'=>'NOT PAID','partial_paid'=>'NO'));
    $ci->db->insert('transactions',array('loan_id'=>990002,'payment_number'=>9,'ref'=>'LEGACY','amount'=>150000,'transaction_type'=>3,'date_stamp'=>'2026-09-30'));
    $legacy=$model->repair_loan_payment_state(990002);check(!isset($legacy['error']),json_encode($legacy));money($legacy['applied'],150000,'Legacy excess carries forward');
    $ci->db->insert('transaction',array('transaction_id'=>'LEGACY','reversed'=>'Yes'));
    $legacy=$model->repair_loan_payment_state(990002);money($legacy['applied'],0,'All reversed payments excluded');
    $ci->db->insert('loan',array('loan_id'=>990003,'loan_status'=>'ACTIVE','loan_principal'=>70000,'loan_amount_total'=>100000,'next_payment_id'=>1));
    $ci->db->insert('payement_schedules',array('loan_id'=>990003,'payment_number'=>1,'payment_schedule'=>'2023-11-23',
        'amount'=>100000,'paid_amount'=>50000,'principal'=>70000,'interest'=>15000,'padmin_fee'=>10000,'ploan_cover'=>5000,'loan_balance'=>35000,
        'total_late_charge'=>200,'status'=>'PARTIAL PAID','partial_paid'=>'YES'));
    $ci->db->insert('transactions',array('loan_id'=>990003,'payment_number'=>1,'ref'=>'HIST-PAID','amount'=>50000,'transaction_type'=>3,'date_stamp'=>'2023-12-03'));
    $ci->db->insert('transactions',array('loan_id'=>990003,'payment_number'=>1,'ref'=>'HIST-PENALTY','amount'=>1000,'transaction_type'=>2,'date_stamp'=>'2024-01-01'));
    $preview=$charges->reconstruct_history(990003);money($preview['opening_penalty'],60333.33,'Historical net penalties');
    check(!$preview['applied'],'Preview unexpectedly applied');
    check(!$ci->db->where('loan_id',990003)->count_all_results('loan_charge_state'),'Preview seeded state');
    $charges->reconstruct_history(990003,true);
    $charges->reconstruct_history(990003,true);
    $history=$ci->db->where('loan_id',990003)->get('payement_schedules')->row();
    money($history->total_late_charge,60333.33,'Repeat replaces baseline without double counting');money($history->paid_amount,50000,'Historical contract payments unchanged');
    $charges->refresh_loan(990003,'2026-10-05');
    $history=$ci->db->where('loan_id',990003)->get('payement_schedules')->row();money($history->total_late_charge,60333.33,'Expired cap retains unpaid historical debt');
    check($ci->db->where('loan_id',990003)->count_all_results('loan_penalty_reconstructions')===2,'Audit history not retained');
    $ci->db->insert('transaction',array('transaction_id'=>'HIST-PAID','reversed'=>'Yes'));
    $blocked=false;try{$charges->reconstruct_history(990003);}catch(RuntimeException $e){$blocked=true;}
    check($blocked,'Mismatched historical repayments were guessed');
    $model->repair_loan_payment_state(990003);
    $reversed=$charges->reconstruct_history(990003,true);money($reversed['opening_penalty'],119000,'Reversed references excluded from historical base');
    $ci->db->insert('loan',array('loan_id'=>990004,'loan_number'=>'HIST-DATE-TEST','loan_status'=>'ACTIVE','loan_principal'=>70000,'loan_amount_total'=>100000,'next_payment_id'=>1));
    $ci->db->insert('payement_schedules',array('loan_id'=>990004,'payment_number'=>1,'payment_schedule'=>'2023-11-23',
        'amount'=>100000,'paid_amount'=>50000,'principal'=>70000,'interest'=>15000,'padmin_fee'=>10000,'ploan_cover'=>5000,'loan_balance'=>35000,
        'status'=>'PARTIAL PAID','partial_paid'=>'YES'));
    $ci->db->insert('transactions',array('loan_id'=>990004,'payment_number'=>1,'ref'=>'MISSING-DATE','amount'=>50000,'transaction_type'=>3,'date_stamp'=>null));
    $message='';try{$charges->reconstruct_history(990004);}catch(RuntimeException $e){$message=$e->getMessage();}
    check(strpos($message,'MISSING-DATE')!==false,'Invalid-date error did not identify repayment');
    $ci->db->insert('transaction',array('transaction_id'=>'MISSING-DATE','account_number'=>'HIST-DATE-TEST','credit'=>50000,'system_time'=>'2023-12-03 10:00:00'));
    $dated=$charges->reconstruct_history(990004);money($dated['opening_penalty'],61333.33,'Account ledger effective date');
    check(count($dated['resolved_payment_dates'])===1,'Recovered date source missing');
    check($ci->db->where('loan_id',990004)->get('transactions')->row()->date_stamp===null,'Source date was silently edited');
    $ci->db->insert('transaction',array('transaction_id'=>'MISSING-DATE','account_number'=>'HIST-DATE-TEST','credit'=>50000,'system_time'=>'2023-12-04 10:00:00'));
    $blocked=false;try{$charges->reconstruct_history(990004);}catch(RuntimeException $e){$blocked=strpos($e->getMessage(),'MISSING-DATE')!==false;}
    check($blocked,'Conflicting account dates were guessed');
    $ci->db->where('transaction_id','MISSING-DATE')->delete('transaction');
    $ci->db->where('loan_id',990004)->update('transactions',array('ref'=>'GF.20231203.141'));
    $safe=$charges->repair_history_dates(990004,false,false);check(count($safe['repairs'])===0 && count($safe['unresolved'])===1,'Reference fallback enabled without opt-in');
    $preview=$charges->repair_history_dates(990004,false,true);check(count($preview['repairs'])===1,'Reference-date preview missing');
    check($ci->db->where('loan_id',990004)->get('transactions')->row()->date_stamp===null,'Preview changed source');
    $fixed=$charges->repair_history_dates(990004,true,true);check($fixed['repairs'][0]['source']==='reference_posting_date_inferred','Inferred source not labelled');
    check($ci->db->where('loan_id',990004)->get('transactions')->row()->date_stamp==='2023-12-03 00:00:00','Bulk date repair');
    check(count($charges->repair_history_dates(990004,true,true)['repairs'])===0,'Repeated date repair not idempotent');
    check($ci->db->where('loan_id',990004)->count_all_results('loan_history_date_repairs')===1,'Repair audit missing or duplicated');
    $rebuilt=$charges->reconstruct_history(990004,true);money($rebuilt['opening_penalty'],61333.33,'Reconstruction after bulk date repair');
    check($ci->db->trans_status(),'DB failure: '.json_encode($ci->db->error()));
    echo "PASS: migration, daily job, component payments, separate penalties, principal balances, multiple arrears, backdate rejection, reversal, repeat repair, restructure and payoff.\n";
    echo "PASS: historical preview, partial dated payments, penalty receipts, repeat application, audit snapshots, expired cap, reconciliation guard and reversed historical payments.\n";
    echo "PASS: missing date diagnostics, matched account dates, source preservation and conflicting-date rejection.\n";
    echo "PASS: bulk date preview, explicit reference-date opt-in, repair audit, idempotence and subsequent reconstruction.\n";
} finally {$ci->db->trans_rollback();}

// Exercise the actual controller's batch transaction boundaries outside an
// enclosing test transaction: preview must truly roll back its date updates.
class CI_Controller extends CI_Model {}
function is_cli(){return true;}
require APPPATH.'controllers/Late_charges.php';
$controller=(new ReflectionClass('Late_charges'))->newInstanceWithoutConstructor();
$ci->db->query('CREATE TABLE IF NOT EXISTS approval_edits (approval_edits_id INT PRIMARY KEY AUTO_INCREMENT,id VARCHAR(50),state VARCHAR(30),type VARCHAR(30),old_info TEXT,new_info TEXT)');
unset($ci->db->data_cache['table_names']);
class HistoryTestInput {
    public $verb='POST';
    public function method($upper){return $this->verb;}
    public function post($key){return array('mode'=>'preview','date_source'=>'reference-date','after_id'=>990004)[$key]??null;}
}
class HistoryTestOutput {
    public $body;
    public function set_content_type($type){return $this;}
    public function set_output($body){$this->body=$body;return $this;}
}
class HistoryTestConfig {public function item($key){return true;}}
class HistoryTestSecurity {
    public function get_csrf_token_name(){return 'test_csrf';}
    public function get_csrf_hash(){return 'renewed-token';}
}
$ci->input=new HistoryTestInput();$ci->output=new HistoryTestOutput();
$ci->config=new HistoryTestConfig();$ci->security=new HistoryTestSecurity();
$blocked=false;try{$controller->bulk_history_batch();}catch(RuntimeException $e){$blocked=true;}
check($blocked,'Browser batch allowed a non-admin');
$ci->session->role='SUPER ADMIN';$ci->input->verb='GET';
$blocked=false;try{$controller->bulk_history_batch();}catch(RuntimeException $e){$blocked=true;}
check($blocked,'Browser batch accepted GET');$ci->input->verb='POST';
try {
    foreach(array(990005,990006) as $id) {
        $ci->db->insert('loan',array('loan_id'=>$id,'loan_number'=>'BATCH-'.$id,'loan_status'=>'ACTIVE','loan_principal'=>70000,'loan_amount_total'=>100000,'next_payment_id'=>1));
        $ci->db->insert('payement_schedules',array('loan_id'=>$id,'payment_number'=>1,'payment_schedule'=>'2023-11-23',
            'amount'=>100000,'paid_amount'=>50000,'principal'=>70000,'interest'=>15000,'padmin_fee'=>10000,'ploan_cover'=>5000,'loan_balance'=>35000,'status'=>'PARTIAL PAID','partial_paid'=>'YES'));
        $ci->db->insert('transactions',array('loan_id'=>$id,'payment_number'=>1,'ref'=>$id===990005?'GF.20231203.151':'BATCH-NO-DATE','amount'=>50000,'transaction_type'=>3,'date_stamp'=>null));
    }
    $ci->db->insert('transaction',array('transaction_id'=>'GF.20231203.151','account_number'=>'BATCH-990005',
        'credit'=>50000,'transaction_type'=>'deposit','system_time'=>'2023-12-03','reversed'=>'NO'));
    $repair_preview=$charges->prepare_restructure(990005,false,true);
    money($repair_preview['historical_opening_penalty'],0,'No automatic historical penalty reconstruction');
    money($repair_preview['contract_payments'],50000,'Existing contract receipt is not diverted to penalties');
    money($repair_preview['remaining_contract_balance'],50000,'Repair preview contract balance');
    money($repair_preview['outstanding_penalties'],0,'Repair preview separate penalties');
    money($repair_preview['total_outstanding_before_restructure'],50000,'Repair preview total outstanding');
    check($ci->db->where('loan_id',990005)->get('transactions')->row()->date_stamp===null,'Restructure preview saved inferred dates');
    check(!$ci->db->where('loan_id',990005)->count_all_results('loan_penalty_reconstructions'),'Restructure preview saved penalty audit');
    $old_terms=array('loan_date'=>'2023-10-19','loan_principal'=>70000,'loan_period'=>1,'loan_product'=>'Original');
    $new_terms=$old_terms;$new_terms['loan_period']=2;
    $ci->db->insert('approval_edits',array('id'=>'990005','state'=>'Approved','type'=>'Loan edit',
        'old_info'=>json_encode($old_terms),'new_info'=>json_encode($new_terms)));
    $approval_id=$ci->db->insert_id();
    $blocked=false;try{$charges->prepare_restructure(990005,false,true);}catch(RuntimeException $e){$blocked=true;}
    check($blocked,'Historical reconstruction ignored approved material edits');
    $pending=$charges->prepare_restructure(990005,false,true,$approval_id);
    money($pending['historical_opening_penalty'],0,'Pending approval uses original schedule');
    $ci->db->where('approval_edits_id',$approval_id)->delete('approval_edits');
    $ci->db->trans_begin();
    $charges->prepare_restructure(990005,true,true);
    $ci->db->where('loan_id',990005)->delete('payement_schedules');
    // Simulate a generator failure after the repair succeeded.
    $ci->db->trans_rollback();
    check($ci->db->where('loan_id',990005)->count_all_results('payement_schedules')===1,'Failed restructure lost original schedule');
    check($ci->db->where('loan_id',990005)->get('transactions')->row()->date_stamp===null,'Failed restructure saved date repair');
    check(!$ci->db->where('loan_id',990005)->count_all_results('loan_penalty_reconstructions'),'Failed restructure saved historical audit');
    $blocked=false;try{$charges->prepare_restructure(990006,true,true);}catch(RuntimeException $e){$blocked=true;}
    check($blocked,'Restructure accepted unresolved dates');
    $ci->db->where('loan_id',990005)->update('payement_schedules',array('paid_amount'=>40000));
    $reconciled=$charges->prepare_restructure(990005,false,true);
    money($reconciled['contract_payments'],50000,'Repair reallocates stale schedule from cash ledger');
    money($reconciled['historical_opening_penalty'],0,'History uses dated ledger after reconciliation');
    money($ci->db->where('loan_id',990005)->get('payement_schedules')->row()->paid_amount,40000,'Preview restores original stale schedule');
    check($ci->db->where('loan_id',990005)->get('transactions')->row()->date_stamp===null,'Preview saved date repairs');
    $ci->db->where('loan_id',990005)->update('payement_schedules',array('paid_amount'=>50000));
    $controller->bulk_history_batch();$browser=json_decode($ci->output->body,true);
    check($browser['success'] && count($browser['items'])===2 && !$browser['has_more'],'Browser batch response');
    check($browser['csrf']['value']==='renewed-token','Browser CSRF refresh missing');
    check($ci->db->where('loan_id',990005)->get('transactions')->row()->date_stamp===null,'Browser preview persisted date repairs');
    ob_start();$controller->repair_all_history('preview','reference-date',990004,1);$output=ob_get_clean();
    $lines=explode("\n",trim($output));$summary=json_decode(end($lines),true)['summary'];
    check($summary['processed']===2 && $summary['needs_review']===1,'Batch preview did not continue across pages');
    check($ci->db->where('loan_id',990005)->get('transactions')->row()->date_stamp===null,'Batch preview persisted inferred date');
    check(!$ci->db->where('loan_id',990005)->count_all_results('loan_history_date_repairs'),'Batch preview persisted audit');
    ob_start();$controller->repair_all_history('apply','reference-date',990004,1);$output=ob_get_clean();
    $lines=explode("\n",trim($output));$summary=json_decode(end($lines),true)['summary'];
    check($summary['reconstructed']===1 && $summary['needs_review']===1,'Batch apply isolation');
    money($ci->db->where('loan_id',990005)->get('payement_schedules')->row()->total_late_charge,61333.33,'Batch reconstructed penalties');
    check($ci->db->where('loan_id',990006)->get('transactions')->row()->date_stamp===null,'Unresolved date was guessed');
    money($ci->db->where('loan_id',990006)->get('payement_schedules')->row()->total_late_charge,0,'Blocked loan was charged');
    $ci->db->insert('loan',array('loan_id'=>990007,'loan_number'=>'RECONCILE-OVERFLOW','loan_status'=>'ACTIVE','loan_principal'=>200000,'loan_amount_total'=>200000,'next_payment_id'=>1));
    foreach(array(1=>'2023-11-23',2=>'2023-12-23') as $number=>$due) {
        $ci->db->insert('payement_schedules',array('loan_id'=>990007,'payment_number'=>$number,'payment_schedule'=>$due,
            'amount'=>100000,'principal'=>100000,'interest'=>0,'padmin_fee'=>0,'ploan_cover'=>0,'paid_amount'=>$number===1?10000:0,
            'loan_balance'=>200000,'status'=>'NOT PAID','partial_paid'=>'NO'));
    }
    $ci->db->insert('transactions',array('loan_id'=>990007,'payment_number'=>1,'ref'=>'RECONCILE-1','amount'=>150000,'transaction_type'=>3,'date_stamp'=>'2023-12-03'));
    $ci->db->insert('transaction',array('transaction_id'=>'RECONCILE-1','account_number'=>'RECONCILE-OVERFLOW','credit'=>150000,'transaction_type'=>'deposit','system_time'=>'2023-12-03','reversed'=>'NO'));
    $fixed=$charges->prepare_restructure(990007,true);
    money($fixed['contract_payments'],150000,'Contract overflow counted once');
    money($fixed['historical_opening_penalty'],0,'Historical payment overflow carries to next original term');
    $first_replayed=$ci->db->where('loan_id',990007)->where('payment_number',1)->get('payement_schedules')->row();
    check(substr($first_replayed->paid_date,0,10)==='2023-12-03','Replay did not restore the actual contract payment date');
    $ledger=$ci->db->select_sum('amount')->where('loan_id',990007)->where_in('transaction_type',array(2,3))->get('transactions')->row();
    money($ledger->amount,150000,'Receipt cash conserved after reclassification');
    $repaired=$model->repair_loan_payment_state(990007);
    check(!isset($repaired['error']),json_encode($repaired));
    money($repaired['applied'],150000,'Schedule rebuild retains reclassified contract allocation');
    check($ci->db->where('loan_id',990007)->where('status','PARTIAL PAID')->count_all_results('payement_schedules')===1,'More than one partial installment after reallocation');
    $audit=json_decode($ci->db->where('loan_id',990007)->order_by('id','DESC')->get('loan_penalty_reconstructions')->row()->audit_json,true);
    money($audit['before_payment_reconciliation'][0]['paid_amount'],10000,'Audit retains stale original payment amount');
    $ci->db->insert('transactions',array('loan_id'=>990007,'payment_number'=>1,'ref'=>'RECONCILE-2','amount'=>50000,'transaction_type'=>3,'date_stamp'=>'2023-12-04'));
    $ci->db->insert('transaction',array('transaction_id'=>'RECONCILE-2','account_number'=>'RECONCILE-OVERFLOW','credit'=>50000,'transaction_type'=>'deposit','system_time'=>'2023-12-04','reversed'=>'NO'));
    $ci->db->where('loan_id',990007)->update('loan',array('loan_status'=>'ACTIVE'));
    $fixed=$charges->prepare_restructure(990007,true);
    money($fixed['contract_payments'],200000,'Original contract cash remains contract payment');
    money($fixed['historical_opening_penalty'],0,'Unpaid contract portion accrues after reallocation');
    $again=$charges->prepare_restructure(990007,true);
    money($again['contract_payments'],$fixed['contract_payments'],'Repeated reallocation preserves contract total');
    money($again['outstanding_penalties'],$fixed['outstanding_penalties'],'Repeated reallocation does not duplicate penalties');
    echo "PASS: full batch preview rollback, pagination, apply and continuation past unresolved loans.\n";
    echo "PASS: browser admin and POST guards, JSON results, refreshed CSRF token and preview rollback.\n";
    echo "PASS: repair and restructure preview, payment preservation, generator rollback, unresolved dates, stale schedule reconciliation, historical overflow, original audit and fully paid contract reconstruction.\n";
} finally {
    $ci->db->where_in('transaction_id',array('GF.20231203.151','RECONCILE-1','RECONCILE-2'))->delete('transaction');
    $ci->db->where('id','990005')->delete('approval_edits');
    foreach(array('loan_penalty_reconstructions','loan_history_date_repairs','loan_payment_components','loan_charge_state','transactions','payement_schedules','loan') as $table) {
        $ci->db->where_in('loan_id',array(990005,990006,990007))->delete($table);
    }
}
