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
        <h2 style="margin-top:0px">Transaction Read</h2>
        <table class="table">
	    <tr><td>Account Number</td><td><?php echo $account_number; ?></td></tr>
	    <tr><td>Transaction Id</td><td><?php echo $transaction_id; ?></td></tr>
	    <tr><td>Credit</td><td><?php echo $credit; ?></td></tr>
	    <tr><td>Debit</td><td><?php echo $debit; ?></td></tr>
	    <tr><td>Balance</td><td><?php echo $balance; ?></td></tr>
	    <tr><td>System Time</td><td><?php echo $system_time; ?></td></tr>
	    <tr><td>Server Time</td><td><?php echo $server_time; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('transaction') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>