<?php
require __DIR__.'/branch_officer_balance_test.php';
require APPPATH.'controllers/Revenue_report.php';
require APPPATH.'helpers/report_supervisor_helper.php';
require APPPATH.'helpers/report_xlsx_helper.php';
class RevenueTestLoad {public function model($name){}public function helper($name){}}
class RevenueTestInput {
    public $values=array();
    public function get($key,$clean=false){return $this->values[$key]??null;}
    public function post($key,$clean=false){return $this->get($key,$clean);}
}
class RevenueTestOutput {
    public $bytes;public function set_content_type($type){return $this;}
    public function set_header($header){return $this;}
    public function set_output($bytes){$this->bytes=$bytes;return $this;}
}
$ci->load=new RevenueTestLoad();$ci->input=new RevenueTestInput();$ci->output=new RevenueTestOutput();
$ci->Employees_model=new Employees_model();$ci->Branches_model=new Branches_model();
$db=$ci->db;
foreach(array('product_name'=>'VARCHAR(200)','branch'=>'VARCHAR(100)') as $field=>$type) {
    if (!$db->field_exists($field,'loan_products')) check($db->query('ALTER TABLE loan_products ADD '.$field.' '.$type.' NULL')!==false,'Test product schema');
}
check($db->query('CREATE TABLE IF NOT EXISTS loan_penalties (penalty_id INT AUTO_INCREMENT PRIMARY KEY,loan_id INT,penalty_amount DECIMAL(18,2))')!==false,'Test penalty schema');
$controller=(new ReflectionClass('Revenue_report'))->newInstanceWithoutConstructor();
$query=new ReflectionMethod('Revenue_report','get_revenue_data');
$filters=new ReflectionMethod('Revenue_report','read_revenue_filters');
$db->trans_begin();
try {
    foreach(array(990070=>array('Mzuzu','90070',70),990071=>array('Lilongwe','90071',71),990072=>array('Blantyre','90072',72)) as $id=>$branch) {
        $db->insert('branches',array('id'=>$id,'BranchName'=>$branch[0],'Code'=>$branch[1],'BranchCode'=>$branch[2],'AddressLine1'=>'','AddressLine2'=>'','City'=>$branch[0]));
    }
    // All branches use the same product even though its metadata says Blantyre.
    $db->insert('loan_products',array('loan_product_id'=>990070,'product_name'=>'Shared Product','branch'=>'90072'));
    $db->insert('roles',array('id'=>990070,'RoleName'=>'Relationship Officer'));
    $db->insert('employees',array('id'=>990170,'Firstname'=>'Revenue','Lastname'=>'Fixture','Role'=>990070,'Supervisor'=>990171));
    $branch_values=array(990070,90070,70,990071,90071,71,990072,90072,72);
    foreach($branch_values as $index=>$branch) {
        $id=990070+$index;
        check($db->insert('loan',array('loan_id'=>$id,'loan_number'=>'REV-'.$id,'branch'=>$branch,'loan_product'=>990070,
            'loan_status'=>'ACTIVE','loan_added_by'=>990170,'disbursed'=>'Yes','loan_date'=>'2026-05-11','loan_principal'=>100,
            'loan_interest_amount'=>10,'admin_fees_amount'=>2,'loan_cover_amount'=>3)),'Loan fixture');
        $db->insert('loan_penalties',array('loan_id'=>$id,'penalty_amount'=>1));
        $db->insert('transactions',array('loan_id'=>$id,'transaction_type'=>1,'payment_number'=>0,'ref'=>'GF.REVENUE.'.$id,'amount'=>2));
        $db->insert('transactions',array('loan_id'=>$id,'transaction_type'=>1,'payment_number'=>1,'ref'=>'CUSTOMER.REVENUE.'.$id,'amount'=>300));
    }
    $db->insert('loan',array('loan_id'=>990079,'loan_number'=>'NOT-DISBURSED','branch'=>990070,'loan_product'=>990070,
        'loan_status'=>'APPROVED','loan_added_by'=>990170,'disbursed'=>'No','loan_date'=>'2026-05-11','loan_interest_amount'=>999));
    $db->insert('transactions',array('loan_id'=>990070,'transaction_type'=>1,'payment_number'=>0,'ref'=>'GF.REVERSED.REVENUE','amount'=>500));
    $db->insert('transaction',array('transaction_id'=>'GF.REVERSED.REVENUE','reversed'=>'YES'));
    // Raw ID wins a collision with another branch's legacy Code.
    $db->insert('branches',array('id'=>990073,'BranchName'=>'Collision Fixture','Code'=>'990070','BranchCode'=>73,'AddressLine1'=>'','AddressLine2'=>'','City'=>'Test'));
    $all=$query->invoke($controller,null,null,990170);
    check(count($all)===3,'All branches must appear despite shared product metadata');
    $count=0;foreach($all as $row)$count+=$row['loans_count'];
    check($count===9,'Branch identity collision caused duplication or loss');
    foreach(array(990070=>3,990071=>3,990072=>3) as $id=>$expected) {
        $rows=$query->invoke($controller,null,null,990170,$id);
        check(count($rows)===1 && $rows[0]['loans_count']===$expected,'Wrong revenue for branch '.$id);
        check(abs($rows[0]['interest']-$expected*10)<.001,'Wrong interest totals');
        check(abs($rows[0]['processing_fees']-$expected*2)<.001,'Processing fee counted customer receipts');
        check(abs($rows[0]['total']-$expected*18)<.001,'Wrong total');
    }
    check(!$query->invoke($controller,'2026-06-01',null,990170,990070),'Start date ignored');
    check(!$query->invoke($controller,null,'2026-05-10',990170,990070),'End date ignored');
    check(!$query->invoke($controller,null,null,990172,990070),'Officer filter ignored');
    check(count($query->invoke($controller,null,null,null,990070,990171))===1,'Supervisor filter lost assigned officers');
    $ci->input->values=array('branch_id'=>'990070','supervisor'=>'990171','officer_id'=>'990172','start_date'=>'2026-05-11','end_date'=>'2026-05-11');
    $parsed=$filters->invoke($controller,'get');check($parsed['officer_id']===null,'Supervisor did not supersede officer');
    $controller->export_excel();
    check(substr($ci->output->bytes,0,2)==='PK','Export is not a real XLSX workbook');
    file_put_contents(__DIR__.'/../.local/revenue-export-test.xlsx',$ci->output->bytes);
    file_put_contents(__DIR__.'/../.local/revenue-text-test.xlsx',report_xlsx_bytes(array(array('Label','Amount'),array('=1+1',1234.56),array('000123',0)),'Safe Text',array(1)));
    $ci->input->values=array('start_date'=>'2026-02-30');
    $blocked=false;try{$filters->invoke($controller,'post');}catch(Throwable $e){$blocked=true;}
    check($blocked,'Invalid report date accepted');
    $ci->input->values=array('start_date'=>'2026-06-01','end_date'=>'2026-05-01');
    $blocked=false;try{$filters->invoke($controller,'get');}catch(Throwable $e){$blocked=true;}
    check($blocked,'Reversed date range accepted');
    echo "PASS: all-branch revenue from shared products, ID/Code/BranchCode resolution, collision precedence, totals, processing-fee isolation, date/officer/supervisor filters and real filtered XLSX export.\n";
} finally {$db->trans_rollback();}
