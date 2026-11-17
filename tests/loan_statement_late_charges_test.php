<?php
define('BASEPATH',__DIR__.'/../system/');
class CI_Model {}
require __DIR__.'/../application/models/Payement_schedules_model.php';
// Render the shared single/batch PDF template with deterministic schedule data.
function base_url($path){return 'data:text/plain,';}
function get_all_by_id($table,$field,$id){return array();}
$settings=(object)array('company_name'=>'Test','logo'=>'','address'=>'','company_email'=>'','phone_number'=>'','currency'=>'MWK');
$loan_customer='Test customer';$loan_principal=200000;$loan_amount_total=200000;
$product_name='Test';$branch_name='Test';$loan_interest=0;$period_type='Monthly';$loan_period=2;
$loan_amount_term=100000;$loan_number='TEST';$loan_date='2026-01-01';$maturity_date='2026-03-01';
$maturity_pay=100000;$first_payment=100000;$first_payment_date='2026-02-01';$officer='Test';$loan_id=1;
$payments=array((object)array('payment_number'=>1,'payment_schedule'=>'2026-02-01','principal'=>100000,'interest'=>0,
    'amount'=>100000,'paid_amount'=>100000,'loan_balance'=>100000,'status'=>'PAID','total_late_charge'=>0),
    (object)array('payment_number'=>2,'payment_schedule'=>'2026-03-01','principal'=>100000,'interest'=>0,
    'amount'=>100000,'paid_amount'=>0,'loan_balance'=>100000,'status'=>'NOT PAID','total_late_charge'=>1234.56));
ob_start();require __DIR__.'/../application/views/loan/_loan_statement_page.php';$html=ob_get_clean();
foreach(array('Late Charges(MWK)','Late Charges Outstanding:','1,234.56','101,234.56','Total Outstanding:') as $expected) {
    if (strpos($html,$expected)===false) throw new RuntimeException('PDF template is missing '.$expected);
}
echo "PASS: exported statement includes installment penalties, penalty summary, and total outstanding including penalties.\n";
