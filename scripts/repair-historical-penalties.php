<?php
/** Run from the server CLI; CodeIgniter supplies the existing DB configuration. */
if (PHP_SAPI !== 'cli') {http_response_code(403);exit('Command line only.');}
$mode=$argv[1]??'preview';
$source=$argv[2]??'account-only';
$after=$argv[3]??'0';
$batch=$argv[4]??'100';
if (!in_array($mode,array('preview','apply'),true) || !in_array($source,array('account-only','reference-date'),true)
    || !ctype_digit($after) || !ctype_digit($batch)) {
    fwrite(STDERR,"Usage: php scripts/repair-historical-penalties.php preview|apply account-only|reference-date [after_loan_id] [batch_size]\n");exit(1);
}
chdir(dirname(__DIR__));
$_SERVER['argv']=array('index.php','Late_charges','repair_all_history',$mode,$source,$after,$batch);
$_SERVER['argc']=count($_SERVER['argv']);
$argv=$_SERVER['argv'];$argc=$_SERVER['argc'];
require 'index.php';
