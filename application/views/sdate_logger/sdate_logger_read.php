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
        <h2 style="margin-top:0px">Sdate_logger Read</h2>
        <table class="table">
	    <tr><td>Table Name</td><td><?php echo $table_name; ?></td></tr>
	    <tr><td>Crud</td><td><?php echo $crud; ?></td></tr>
	    <tr><td>Server Time</td><td><?php echo $server_time; ?></td></tr>
	    <tr><td>System Date</td><td><?php echo $system_date; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('sdate_logger') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>