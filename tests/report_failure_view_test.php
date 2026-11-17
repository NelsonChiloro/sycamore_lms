<?php
function base_url($path){return '/'.$path;}
function site_url($path){return '/'.$path;}
function anchor($url,$label,$attributes=''){return '<a href="'.$url.'">'.$label.'</a>';}
function report_preview_url($path){return $path;}
$defaults=array('report_type'=>'RISK_RECOVERY_REPORT','percentage'=>0,'download_link'=>null,
    'report_date_from'=>null,'report_date_to'=>null,'generated_time'=>'2026-11-17 00:00:00','user'=>'Tester');
$reports_data=array();
foreach(array(
    array('id'=>1,'status'=>'in progress','completed_time'=>null),
    array('id'=>2,'status'=>'failed','completed_time'=>'2026-11-17 00:01:00'),
    array('id'=>3,'status'=>'in progress','completed_time'=>'2026-11-17 00:01:00'),
    array('id'=>4,'status'=>'completed','completed_time'=>'2026-11-17 00:01:00','percentage'=>100,'download_link'=>'reports/ok.html')
) as $row) $reports_data[]=(object)array_merge($defaults,$row);
ob_start();require __DIR__.'/../application/views/reports/reports_list.php';$html=ob_get_clean();
if(substr_count($html,'Failed — please generate again.')!==2 || substr_count($html,"In Progress</font>")!==1
    || substr_count($html,"Complete</font>")!==1 || substr_count($html,'>Preview</a>')!==1) {
    throw new RuntimeException('Report list misclassified a terminal failure or exposed an invalid preview.');
}
echo "PASS: report list distinguishes queued, completed, modern failed and legacy terminal-failed jobs.\n";
