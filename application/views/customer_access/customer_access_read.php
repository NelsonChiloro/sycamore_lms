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
        <h2 style="margin-top:0px">Customer_access Read</h2>
        <table class="table">
	    <tr><td>Customer Id</td><td><?php echo $customer_id; ?></td></tr>
	    <tr><td>Phone Number</td><td><?php echo $phone_number; ?></td></tr>
	    <tr><td>Created By</td><td><?php echo $created_by; ?></td></tr>
	    <tr><td>Approved By</td><td><?php echo $approved_by; ?></td></tr>
	    <tr><td>Denied By</td><td><?php echo $denied_by; ?></td></tr>
	    <tr><td>Status</td><td><?php echo $status; ?></td></tr>
	    <tr><td>Stamp</td><td><?php echo $stamp; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('customer_access') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>