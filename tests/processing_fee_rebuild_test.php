<?php
// Uses only the disposable charge_framework_test database and existing bootstrap.
require __DIR__.'/late_charge_integration_test.php';
require APPPATH.'models/Account_model.php';
require APPPATH.'models/Transactions_model.php';
require APPPATH.'controllers/Loan.php';
check($ci->db->database==='charge_framework_test','Unsafe test database');
$ci->db->query('CREATE TABLE IF NOT EXISTS account (account_id INT AUTO_INCREMENT PRIMARY KEY,account_number VARCHAR(200),balance DECIMAL(18,2)) ENGINE=InnoDB');
$ci->db->query('CREATE TABLE IF NOT EXISTS loan_products (loan_product_id INT PRIMARY KEY,processing_fees DECIMAL(18,2)) ENGINE=InnoDB');
foreach(array('disbursed'=>"VARCHAR(5) DEFAULT 'No'") as $field=>$type) {
    if (!$ci->db->field_exists($field,'loan')) check($ci->db->query('ALTER TABLE loan ADD '.$field.' '.$type)!==false,'Test loan schema');
}
class FeeTeller {public function get_teller_account1(){return (object)array('account'=>'FEE-TEST-TELLER');}}
class BrokenFeeTransfer extends Account_model {
    public function transfer_funds1($sender,$recipient,$amount,$ref,$date) {
        parent::transfer_funds1($sender,$recipient,$amount,$ref,$date);
        $this->db->query('INSERT INTO missing_fee_test_table VALUES (1)');
        return 'success';
    }
}
class BrokenFeeReceipt extends Transactions_model {
    public function insert($data) {
        parent::insert($data);
        $this->db->query('INSERT INTO missing_fee_test_table VALUES (1)');
    }
}
class BrokenFeeDeposit extends Account_model {
    public function cash_transaction($teller,$account,$amount,$mode,$ref,$date) {
        $id=parent::cash_transaction($teller,$account,$amount,$mode,$ref,$date);
        $this->db->query('INSERT INTO missing_fee_test_table VALUES (1)');
        return $id;
    }
}
$ci->Tellering_model=new FeeTeller();
$ci->Account_model=new Account_model();
$ci->Transactions_model=new Transactions_model();
$fee=(new ReflectionClass('Loan'))->newInstanceWithoutConstructor();
try {
    $ci->db->insert('loan_products',array('loan_product_id'=>990020,'processing_fees'=>6));
    $ci->db->insert('account',array('account_number'=>'FEE-TEST-TELLER','balance'=>100000));
    foreach(array(990020,990021,990022,990023,990025) as $id) {
        $ci->db->insert('loan',array('loan_id'=>$id,'loan_number'=>'FEE-TEST-'.$id,'loan_status'=>'APPROVED',
            'disbursed'=>'No','loan_product'=>990020,'loan_principal'=>1000000));
        $ci->db->insert('account',array('account_number'=>'FEE-TEST-'.$id,'balance'=>0));
    }
    check($fee->cash_transaction(990020,'2026-05-11')===true,'Fee post failed');
    money($ci->db->where('account_number','FEE-TEST-TELLER')->get('account')->row()->balance,100000,'Teller net balance');
    money($ci->db->where('account_number','FEE-TEST-990020')->get('account')->row()->balance,0,'Loan net balance');
    check($ci->db->where_in('transaction_id',array('PF.IN.990020','PF.OUT.990020'))->count_all_results('transaction')===4,'Four balancing fee ledger rows');
    money($ci->db->where('loan_id',990020)->get('transactions')->row()->amount,60000,'Six percent fee');
    $fee->cash_transaction(990020,'2026-05-11');
    check($ci->db->where('loan_id',990020)->count_all_results('transactions')===1,'Retry duplicated fee');
    check($ci->db->where_in('transaction_id',array('PF.IN.990020','PF.OUT.990020'))->count_all_results('transaction')===4,'Retry duplicated ledger');
    $ci->Account_model=new BrokenFeeTransfer();
    $failed=false;try{$fee->cash_transaction(990021);}catch(Throwable $e){$failed=true;}
    check($failed,'Transfer database failure was ignored');
    check(!$ci->db->where_in('transaction_id',array('PF.IN.990021','PF.OUT.990021'))->count_all_results('transaction'),'Partial fee ledger escaped rollback');
    money($ci->db->where('account_number','FEE-TEST-TELLER')->get('account')->row()->balance,100000,'Failed fee changed teller');
    $ci->Account_model=new Account_model();
    $ci->Transactions_model=new BrokenFeeReceipt();
    $failed=false;try{$fee->cash_transaction(990022);}catch(Throwable $e){$failed=true;}
    check($failed,'Fee record database failure was ignored');
    check(!$ci->db->where_in('transaction_id',array('PF.IN.990022','PF.OUT.990022'))->count_all_results('transaction'),'Fee record failure left ledger');
    check(!$ci->db->where('loan_id',990022)->count_all_results('transactions'),'Failed fee record remained');
    $ci->Transactions_model=new Transactions_model();
    $ci->Account_model=new BrokenFeeDeposit();
    $failed=false;try{$fee->cash_transaction(990025);}catch(Throwable $e){$failed=true;}
    check($failed,'Deposit database failure was ignored');
    check(!$ci->db->where_in('transaction_id',array('PF.IN.990025','PF.OUT.990025'))->count_all_results('transaction'),'Deposit failure left ledger');
    $ci->Account_model=new Account_model();
    $ci->db->insert('transaction',array('account_number'=>'FEE-TEST-990023','transaction_id'=>'OLD-PARTIAL-FEE','credit'=>60000,'transaction_type'=>'other','reversed'=>'NO'));
    $failed=false;try{$fee->cash_transaction(990023);}catch(Throwable $e){$failed=true;}
    check($failed,'Historical partial fee silently reposted');
    echo "PASS: numeric return compatibility, complete fee posting, retry safety, deposit/transfer/record failure rollback and historical-posting guard.\n";

    // Supplied loan 7622 schedule, cash dates and amounts; refs are synthetic.
    $id=990024;$account='FIXTURE-7622';
    $ci->db->insert('loan',array('loan_id'=>$id,'loan_number'=>$account,'loan_status'=>'ACTIVE',
        'loan_date'=>'2026-04-24','loan_principal'=>1000000,'loan_amount_total'=>1435759.38,'next_payment_id'=>1));
    $old_terms=array('loan_date'=>'2026-05-11','loan_principal'=>'1000000.00','loan_period'=>'6','loan_product'=>'Mizu');
    $corrected_terms=$old_terms;$corrected_terms['loan_date']='2026-04-24';
    $ci->db->insert('approval_edits',array('id'=>(string)$id,'state'=>'Approved','type'=>'Loan edit',
        'old_info'=>json_encode($old_terms),'new_info'=>json_encode($corrected_terms)));
    $correction_id=$ci->db->insert_id();
    $ci->db->insert('approval_edits',array('id'=>(string)$id,'state'=>'Approved','type'=>'Loan edit',
        'old_info'=>json_encode($corrected_terms),'new_info'=>json_encode($corrected_terms)));
    $due=array('2026-05-31','2026-06-30','2026-07-31','2026-08-31','2026-09-30','2026-10-31');
    $principal=array(126376.56,140277.99,155708.56,172836.51,191848.52,212951.86);
    $interest=array(47916.67,42229.72,35917.21,28910.33,21132.68,12499.50);
    $admin=array(35000,30576.82,25667.09,20217.29,14168.01,7453.32);
    $cover=array(30000,26208.70,22000.36,17329.11,12144.01,6388.56);
    foreach($due as $index=>$date) {
        $ci->db->insert('payement_schedules',array('loan_id'=>$id,'payment_number'=>$index+1,'payment_schedule'=>$date,
            'amount'=>239293.23,'paid_amount'=>0,'principal'=>$principal[$index],'interest'=>$interest[$index],
            'ploan_cover'=>$cover[$index],'padmin_fee'=>$admin[$index],
            'status'=>'PARTIAL PAID','partial_paid'=>'YES','loan_balance'=>1000000));
    }
    $cash=array('2026-07-30'=>345694.73,'2026-08-17'=>360000,'2026-08-28'=>12184.96,'2026-09-29'=>239293.23,'2026-10-22'=>239293.23);
    foreach($cash as $date=>$amount) {
        $ref='ACTUAL-FIXTURE-'.$date;
        $ci->db->insert('transactions',array('loan_id'=>$id,'payment_number'=>1,'ref'=>$ref,'amount'=>$amount,
            'transaction_type'=>1,'payment_reference'=>'BANK-'.$date,'payment_type'=>'bank','date_stamp'=>$date.' 10:15:00'));
        // Stale/missing allocation amounts must not replace the receipt amount.
        if ($date!=='2026-08-28') $ci->db->insert('transactions',array('loan_id'=>$id,'payment_number'=>1,'ref'=>$ref,
            'amount'=>1,'transaction_type'=>3,'date_stamp'=>$date.' 10:15:00'));
    }
    $ci->db->insert('transaction',array('account_number'=>$account,'transaction_id'=>'INTERNAL-FEE-FIXTURE','credit'=>60000,'transaction_type'=>'other','reversed'=>'NO'));
    $ci->db->insert('transactions',array('loan_id'=>$id,'payment_number'=>0,'ref'=>'GF.FIXTURE','amount'=>60000,'transaction_type'=>1,'date_stamp'=>'2026-05-11'));
    $receipts=$ci->db->where('loan_id',$id)->where('transaction_type',1)->order_by('transaction_id')->get('transactions')->result_array();
    $account_ledger=$ci->db->where('account_number',$account)->get('transaction')->result_array();
    $preview=$charges->prepare_restructure($id,false);
    check(!$ci->db->where('loan_id',$id)->count_all_results('loan_payment_components'),'Preview left allocations');
    $first=$charges->prepare_restructure($id,true);
    money($first['contract_payments'],1196466.15,'Historical contract receipts were diverted to penalties');
    money($first['remaining_contract_balance'],239293.23,'Wrong remaining contract balance');
    $audit=json_decode($ci->db->where('loan_id',$id)->order_by('id','DESC')->get('loan_penalty_reconstructions')->row()->audit_json,true);
    check($audit['report']['retroactive_date_corrections'][0]['approval_edits_id']===$correction_id,'Retroactive correction not audited');
    money($first['contract_payments']+$ci->db->select_sum('amount')->where('loan_id',$id)->where('component','penalty')->get('loan_payment_components')->row()->amount,1196466.15,'Five receipts counted exactly once');
    check($receipts===$ci->db->where('loan_id',$id)->where('transaction_type',1)->order_by('transaction_id')->get('transactions')->result_array(),'Original receipts changed');
    check($account_ledger===$ci->db->where('account_number',$account)->get('transaction')->result_array(),'Rebuild changed internal fee ledger');
    $schedules=$ci->db->where('loan_id',$id)->order_by('payment_number')->get('payement_schedules')->result();
    foreach($schedules as $index=>$row) {
        money($row->paid_amount,$index<5?239293.23:0,'Exact paid installment amount');
        check($row->status===($index<5?'PAID':'NOT PAID'),'Wrong paid/zero-paid status');
    }
    foreach($schedules as $row) if ((float)$row->paid_amount===0.0) check($row->status==='NOT PAID' && $row->partial_paid==='NO','Zero-paid installment incorrectly partial');
    $allocation_count=$ci->db->where('loan_id',$id)->where_in('transaction_type',array(2,3))->count_all_results('transactions');
    $again=$charges->prepare_restructure($id,true);
    money($again['contract_payments'],$first['contract_payments'],'Repeated rebuild contract changed');
    money($again['outstanding_penalties'],$first['outstanding_penalties'],'Repeated rebuild penalty changed');
    check($allocation_count===$ci->db->where('loan_id',$id)->where_in('transaction_type',array(2,3))->count_all_results('transactions'),'Repeated rebuild duplicated allocations');
    // Emulate the prior bad rebuild: it manufactured penalty allocations from
    // contract receipts. The earliest audit must recover their original split.
    $ci->db->insert('transactions',array('loan_id'=>$id,'payment_number'=>1,'ref'=>'ACTUAL-FIXTURE-2026-07-30',
        'amount'=>60000,'transaction_type'=>2,'description'=>'charge_policy_v1','date_stamp'=>'2026-07-30 10:15:00'));
    $ci->db->where('loan_id',$id)->update('loan_charge_state',array('penalty_accrued'=>999999));
    $recovered=$charges->prepare_restructure($id,true);
    money($recovered['contract_payments'],1196466.15,'Previous rebuild diversion was not recovered');
    money($recovered['remaining_contract_balance'],239293.23,'Previous bad rebuild left inflated contract balance');
    money($recovered['outstanding_penalties'],$first['outstanding_penalties'],'Previous rebuild penalties were reused as genuine charges');
    $repaired=$model->repair_loan_payment_state($id);
    check(!isset($repaired['error']),json_encode($repaired));
    money($repaired['applied'],1196466.15,'Post-generator repair lost contract cash');
    $after_repair=$ci->db->where('loan_id',$id)->order_by('payment_number')->get('payement_schedules')->result();
    foreach($after_repair as $index=>$row) check($row->status===($index<5?'PAID':'NOT PAID'),'Post-generator repair reopened a settled installment');
    money($after_repair[4]->loan_balance,212951.86,'Rounding changed principal outstanding');
    check($receipts===$ci->db->where('loan_id',$id)->where('transaction_type',1)->order_by('transaction_id')->get('transactions')->result_array(),'Recovery altered original receipts');
    $ci->db->where('loan_id',$id)->update('loan',array('loan_date'=>'2026-05-11'));
    $failed=false;try{$charges->prepare_restructure($id,false);}catch(Throwable $e){$failed=true;}
    check($failed,'Unapplied date correction accepted');
    $ci->db->where('loan_id',$id)->update('loan',array('loan_date'=>'2026-04-24'));
    $states=array();foreach($schedules as $row){$row->paid_amount=0;$states[$row->payment_number]=array_merge(Loan_charge_policy::seed($row),array('penalty_base'=>0,'penalty_accrued'=>0,'penalty_paid'=>0));}
    foreach($cash as $date=>$amount){$allocation=Loan_charge_policy::allocate($schedules,$states,$amount,$date,false);$states=$allocation['states'];}
    $paid=0;foreach($states as $state)foreach(Loan_charge_policy::$components as $component=>$field)$paid+=$state['paid_'.$component];
    money(1435759.38-$paid,239293.23,'Contract-only remaining installment');
    $expected_schedules=$ci->db->where('loan_id',$id)->order_by('payment_number')->get('payement_schedules')->result();
    $ci->db->insert('transactions',array('loan_id'=>$id,'payment_number'=>1,'ref'=>'UNVERIFIED-ALLOCATION','amount'=>100,'transaction_type'=>3,'date_stamp'=>'2026-10-23'));
    $failed=false;try{$charges->prepare_restructure($id,true);}catch(Throwable $e){$failed=true;}
    check($failed,'Unverified legacy allocation accepted');
    $ci->db->where('loan_id',$id)->where('ref','UNVERIFIED-ALLOCATION')->delete('transactions');
    $duplicate=$receipts[0];unset($duplicate['transaction_id']);
    $ci->db->insert('transactions',$duplicate);
    $failed=false;try{$charges->prepare_restructure($id,true);}catch(Throwable $e){$failed=true;}
    check($failed,'Duplicate receipt was counted');
    $after=$ci->db->where('loan_id',$id)->order_by('payment_number')->get('payement_schedules')->result();
    foreach($after as $n=>$row) money($row->paid_amount,$expected_schedules[$n]->paid_amount,'Blocked rebuild changed schedule');
    // Separately recorded penalty payments must survive contract repair.
    $penalty_id=990026;
    $ci->db->insert('loan',array('loan_id'=>$penalty_id,'loan_number'=>'RECORDED-PENALTY','loan_status'=>'ACTIVE','loan_principal'=>100,'loan_amount_total'=>100));
    $ci->db->insert('payement_schedules',array('loan_id'=>$penalty_id,'payment_number'=>1,'payment_schedule'=>'2023-01-01',
        'amount'=>100,'principal'=>100,'interest'=>0,'padmin_fee'=>0,'ploan_cover'=>0,'paid_amount'=>100,'total_late_charge'=>30));
    $ci->db->insert('loan_charge_state',array('loan_id'=>$penalty_id,'payment_number'=>1,'paid_principal'=>100,
        'penalty_base'=>50,'penalty_paid'=>20,'accrued_through'=>date('Y-m-d')));
    foreach(array(1=>120,2=>20,3=>100) as $type=>$amount) $ci->db->insert('transactions',array(
        'loan_id'=>$penalty_id,'payment_number'=>1,'ref'=>'GENUINE-PENALTY-RECEIPT','transaction_type'=>$type,'amount'=>$amount,'date_stamp'=>'2026-10-22 12:00:00'));
    $preserved=$charges->prepare_restructure($penalty_id,true);
    money($preserved['contract_payments'],100,'Recorded penalty cash counted as contract');
    money($preserved['outstanding_penalties'],30,'Existing posted penalty balance changed');
    $preserved_again=$charges->prepare_restructure($penalty_id,true);
    money($preserved_again['outstanding_penalties'],30,'Recorded penalty counted twice on repeat');
    echo "PASS: five verified payments, receipt precedence, missing allocations, internal-fee exclusion, preview rollback, preserved receipts/ledger, zero-paid status, repeatability and ambiguous-history guard.\n";
} finally {
    $ci->db->where('id','990024')->where('type','Loan edit')->delete('approval_edits');
    foreach(array('loan_penalty_reconstructions','loan_history_date_repairs','loan_payment_components','loan_charge_state','transactions','payement_schedules','loan') as $table) {
        $ci->db->where_in('loan_id',array(990020,990021,990022,990023,990024,990025,990026))->delete($table);
    }
    $ci->db->where('loan_product_id',990020)->delete('loan_products');
    $ci->db->where_in('account_number',array('FEE-TEST-TELLER','FEE-TEST-990020','FEE-TEST-990021','FEE-TEST-990022','FEE-TEST-990023','FEE-TEST-990025'))->delete('account');
    $ci->db->where_in('account_number',array('FEE-TEST-TELLER','FEE-TEST-990020','FEE-TEST-990021','FEE-TEST-990022','FEE-TEST-990023','FEE-TEST-990025','FIXTURE-7622'))->delete('transaction');
}
