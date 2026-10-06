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
        <h2 style="margin-top:0px">Group_loan_tracker Read</h2>
        <table class="table">
	    <tr><td>Disbursement Id</td><td><?php echo $disbursement_id; ?></td></tr>
	    <tr><td>Group Id</td><td><?php echo $group_id; ?></td></tr>
	    <tr><td>Customer Id</td><td><?php echo $customer_id; ?></td></tr>
	    <tr><td>Amount</td><td><?php echo $amount; ?></td></tr>
	    <tr><td>Date Added</td><td><?php echo $date_added; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('group_loan_tracker') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>