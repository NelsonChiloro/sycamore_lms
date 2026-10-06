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
        <h2 style="margin-top:0px">Payement_schedules Read</h2>
        <table class="table">
	    <tr><td>Id</td><td><?php echo $id; ?></td></tr>
	    <tr><td>Customer</td><td><?php echo $customer; ?></td></tr>
	    <tr><td>Loan Id</td><td><?php echo $loan_id; ?></td></tr>
	    <tr><td>Payment Schedule</td><td><?php echo $payment_schedule; ?></td></tr>
	    <tr><td>Payment Number</td><td><?php echo $payment_number; ?></td></tr>
	    <tr><td>Amount</td><td><?php echo $amount; ?></td></tr>
	    <tr><td>Principal</td><td><?php echo $principal; ?></td></tr>
	    <tr><td>Interest</td><td><?php echo $interest; ?></td></tr>
	    <tr><td>Paid Amount</td><td><?php echo $paid_amount; ?></td></tr>
	    <tr><td>Loan Balance</td><td><?php echo $loan_balance; ?></td></tr>
	    <tr><td>Status</td><td><?php echo $status; ?></td></tr>
	    <tr><td>Loan Date</td><td><?php echo $loan_date; ?></td></tr>
	    <tr><td>Paid Date</td><td><?php echo $paid_date; ?></td></tr>
	    <tr><td>Marked Due</td><td><?php echo $marked_due; ?></td></tr>
	    <tr><td>Marked Due Date</td><td><?php echo $marked_due_date; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('payement_schedules') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>