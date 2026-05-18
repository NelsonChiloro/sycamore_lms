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
        <h2 style="margin-top:0px">Loan Read</h2>
        <table class="table">
	    <tr><td>Loan Number</td><td><?php echo $loan_number; ?></td></tr>
	    <tr><td>Loan Product</td><td><?php echo $loan_product; ?></td></tr>
	    <tr><td>Loan Customer</td><td><?php echo $loan_customer; ?></td></tr>
	    <tr><td>Loan Date</td><td><?php echo $loan_date; ?></td></tr>
	    <tr><td>Loan Principal</td><td><?php echo $loan_principal; ?></td></tr>
	    <tr><td>Loan Period</td><td><?php echo $loan_period; ?></td></tr>
	    <tr><td>Period Type</td><td><?php echo $period_type; ?></td></tr>
	    <tr><td>Loan Interest</td><td><?php echo $loan_interest; ?></td></tr>
	    <tr><td>Loan Amount Total</td><td><?php echo $loan_amount_total; ?></td></tr>
	    <tr><td>Next Payment Id</td><td><?php echo $next_payment_id; ?></td></tr>
	    <tr><td>Loan Added By</td><td><?php echo $loan_added_by; ?></td></tr>
	    <tr><td>Loan Approved By</td><td><?php echo $loan_approved_by; ?></td></tr>
	    <tr><td>Loan Status</td><td><?php echo $loan_status; ?></td></tr>
	    <tr><td>Loan Added Date</td><td><?php echo $loan_added_date; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('loan') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>