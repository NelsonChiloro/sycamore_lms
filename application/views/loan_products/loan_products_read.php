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
        <h2 style="margin-top:0px">Loan_products Read</h2>
        <table class="table">
	    <tr><td>Product Name</td><td><?php echo $product_name; ?></td></tr>
	    <tr><td>Interest</td><td><?php echo $interest; ?></td></tr>
	    <tr><td>Added By</td><td><?php echo $added_by; ?></td></tr>
	    <tr><td>Date Created</td><td><?php echo $date_created; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('loan_products') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>