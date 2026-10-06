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
        <h2 style="margin-top:0px">Financial_year Read</h2>
        <table class="table">
	    <tr><td>Year Start</td><td><?php echo $year_start; ?></td></tr>
	    <tr><td>Year End</td><td><?php echo $year_end; ?></td></tr>
	    <tr><td>Status</td><td><?php echo $status; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('financial_year') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>