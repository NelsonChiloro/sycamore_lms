<!doctype html>
<html>
    <head>
        <title>harviacode.com - codeigniter crud generator</title>
        <link rel="stylesheet" href="<?php echo base_url('assets/bootstrap/css/bootstrap.min.css') ?>"/>
        <style>
            body{
                padding: 15px;
            }
        </style>
    </head>
    <body>
        <h2 style="margin-top:0px">Sms_settings Read</h2>
        <table class="table">
	    <tr><td>Customer Approval</td><td><?php echo $customer_approval; ?></td></tr>
	    <tr><td>Group Approval</td><td><?php echo $group_approval; ?></td></tr>
	    <tr><td>Loan Disbursement</td><td><?php echo $loan_disbursement; ?></td></tr>
	    <tr><td>Before Notice</td><td><?php echo $before_notice; ?></td></tr>
	    <tr><td>Before Notice Period</td><td><?php echo $before_notice_period; ?></td></tr>
	    <tr><td>Arrears</td><td><?php echo $arrears; ?></td></tr>
	    <tr><td>Arrears Age</td><td><?php echo $arrears_age; ?></td></tr>
	    <tr><td>Customer App Pass Recovery</td><td><?php echo $customer_app_pass_recovery; ?></td></tr>
	    <tr><td>Customer Birthday Notify</td><td><?php echo $customer_birthday_notify; ?></td></tr>
	    <tr><td>Loan Payment Notification</td><td><?php echo $loan_payment_notification; ?></td></tr>
	    <tr><td>Deposit Withdraw Notification</td><td><?php echo $deposit_withdraw_notification; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('sms_settings') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>