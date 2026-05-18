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
        <h2 style="margin-top:0px">Borrowed Read</h2>
        <table class="table">
	    <tr><td>Amount</td><td><?php echo $amount; ?></td></tr>
	    <tr><td>Total Interest</td><td><?php echo $total_interest; ?></td></tr>
	    <tr><td>Borrowed From</td><td><?php echo $borrowed_from; ?></td></tr>
	    <tr><td>Date Borrowed</td><td><?php echo $date_borrowed; ?></td></tr>
	    <tr><td>Stamp</td><td><?php echo $stamp; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('borrowed') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>