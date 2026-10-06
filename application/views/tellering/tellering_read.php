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
        <h2 style="margin-top:0px">Tellering Read</h2>
        <table class="table">
	    <tr><td>Teller</td><td><?php echo $teller; ?></td></tr>
	    <tr><td>Account</td><td><?php echo $account; ?></td></tr>
	    <tr><td>Date Time</td><td><?php echo $date_time; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('tellering') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>