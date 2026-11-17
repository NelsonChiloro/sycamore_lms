<?php
define('BASEPATH',__DIR__.'/../system/');
class CI_Model {public function __construct(){}}
require __DIR__.'/../application/models/Loan_model.php';
$model=new Loan_model();
function same_money($actual,$expected,$message) {if(abs($actual-$expected)>.005)throw new RuntimeException($message.': '.$actual.' vs '.$expected);}
$rows=$model->straight_schedule_components(800000,1004807.69,0,8);
same_money(array_sum(array_column($rows,'principal')),800000,'Original principal conserved');
same_money(array_sum(array_column($rows,'padmin_fee')),204807.69,'Financed fees classified separately');
same_money(array_sum(array_column($rows,'amount')),1004807.69,'Installments reconcile to contract total');
same_money($rows[8]['loan_balance'],0,'Projected principal fully amortizes');
foreach($rows as $row) same_money($row['principal']+$row['interest']+$row['padmin_fee'],$row['amount'],'Each installment reconciles');
$rows=$model->straight_schedule_components(1000.01,1200.05,100.03,3);
same_money(array_sum(array_column($rows,'principal')),1000.01,'Principal rounding');
same_money(array_sum(array_column($rows,'interest')),100.03,'Interest rounding');
same_money(array_sum(array_column($rows,'amount')),1200.05,'Contract rounding');
$blocked=false;try{$model->straight_schedule_components(1000,900,0,3);}catch(RuntimeException $e){$blocked=true;}
if(!$blocked)throw new RuntimeException('Invalid contract accepted');
echo "PASS: principal/fee separation, exact contract total, component rounding and final principal balance.\n";
