<?php
define('BASEPATH', __DIR__.'/../system/');
require __DIR__.'/../application/libraries/Loan_charge_policy.php';
function check_close($actual,$expected,$message) {
    if (abs($actual-$expected)>.005) throw new RuntimeException($message.': '.$actual.' != '.$expected);
}
check_close(Loan_charge_policy::accrue('2026-01-01','2025-12-31','2026-01-03',100000),0,'Two-day grace');
check_close(Loan_charge_policy::accrue('2026-01-01','2025-12-31','2026-01-04',100000),333.33333333,'First charging day');
check_close(Loan_charge_policy::accrue('2026-01-01','2025-12-31','2027-12-31',100000),120000,'360-day cap');
check_close(Loan_charge_policy::accrue('2026-01-01','2026-01-04','2026-01-04',100000),0,'No duplicate accrual');
check_close(Loan_charge_policy::accrue('2026-01-01','2026-01-04','2026-01-05',50000),166.66666667,'Partial outstanding base');
check_close(Loan_charge_policy::accrue('2026-01-01','2026-01-04','2026-01-10',0),0,'No charge after settlement');
$rows=array(); $states=array();
foreach(array(1,2) as $n) {
    $rows[]=(object)array('payment_number'=>$n,'payment_schedule'=>'2026-01-01','amount'=>100000,'paid_amount'=>0,'principal'=>70000,'interest'=>15000,'padmin_fee'=>10000,'ploan_cover'=>5000);
    $states[$n]=array('paid_cover'=>0,'paid_admin'=>0,'paid_interest'=>0,'paid_principal'=>0,'penalty_base'=>1000,'penalty_accrued'=>0,'penalty_paid'=>0);
}
$result=Loan_charge_policy::allocate($rows,$states,22000,'2026-01-05');
check_close($result['states'][1]['penalty_paid'],1000,'First installment penalty first');
check_close($result['states'][2]['penalty_paid'],0,'Later penalty waits for earlier contract');
check_close($result['states'][1]['paid_cover'],5000,'First installment cover');
check_close($result['states'][2]['paid_cover'],0,'Later contract remains untouched');
check_close($result['states'][1]['paid_admin'],10000,'Admin before interest');
check_close($result['states'][1]['paid_interest'],6000,'Interest before principal within installment');
check_close($result['states'][1]['paid_principal'],0,'Principal last');
$next=Loan_charge_policy::allocate($rows,$result['states'],1000000,'2026-01-05');
check_close($next['remaining'],820000,'Overpayment exposed');
check_close($next['states'][2]['paid_principal'],70000,'Full principal allocation');
$boundary=Loan_charge_policy::allocate(array_reverse($rows),$states,101000,'2026-01-05');
check_close($boundary['states'][1]['paid_principal'],70000,'Unsorted input still completes earliest installment');
check_close($boundary['states'][2]['penalty_paid'],0,'Exact settlement does not advance');
$boundary=Loan_charge_policy::allocate($rows,$states,101500,'2026-01-05');
check_close($boundary['states'][2]['penalty_paid'],500,'Only excess reaches next penalty');
check_close($boundary['states'][2]['paid_cover'],0,'Next cover waits for its penalty');
echo "PASS: grace, daily precision, cap, partial balance, settlement, repeat accrual, sequential installment priority and overpayment.\n";
check_close(Loan_charge_policy::historical_accrual('2023-11-23',125000,array(array('date'=>'2025-03-19','amount'=>500)),'2026-10-21'),150000,'Old capped charges remain owed after a later payment');
check_close(Loan_charge_policy::historical_accrual('2023-11-23',125000,array(array('date'=>'2023-11-23','amount'=>125000)),'2026-10-21'),0,'On-time payment incurs no historical charge');
check_close(Loan_charge_policy::historical_accrual('2023-11-23',100000,array(array('date'=>'2023-12-03','amount'=>50000)),'2026-10-21'),61333.333333,'Historical partial payment changes daily base');
echo "PASS: historical dates, partial payments, settled installments and expired charging windows.\n";
