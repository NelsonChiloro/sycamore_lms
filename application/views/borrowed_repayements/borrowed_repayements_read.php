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
        <h2 style="margin-top:0px">Borrowed_repayements Read</h2>
        <table class="table">
	    <tr><td>Borrowed Id</td><td><?php echo $borrowed_id; ?></td></tr>
	    <tr><td>Interest Paid</td><td><?php echo $interest_paid; ?></td></tr>
	    <tr><td>Principal Paid</td><td><?php echo $principal_paid; ?></td></tr>
	    <tr><td>Paid By</td><td><?php echo $paid_by; ?></td></tr>
	    <tr><td>Date Of Repaymet</td><td><?php echo $date_of repaymet; ?></td></tr>
	    <tr><td>Stamp</td><td><?php echo $stamp; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('borrowed_repayements') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>