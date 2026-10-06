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
        <h2 style="margin-top:0px">Transactions Read</h2>
        <table class="table">
	    <tr><td>Ref</td><td><?php echo $ref; ?></td></tr>
	    <tr><td>Loan Id</td><td><?php echo $loan_id; ?></td></tr>
	    <tr><td>Amount</td><td><?php echo $amount; ?></td></tr>
	    <tr><td>Payment Number</td><td><?php echo $payment_number; ?></td></tr>
	    <tr><td>Date Stamp</td><td><?php echo $date_stamp; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('transactions') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>