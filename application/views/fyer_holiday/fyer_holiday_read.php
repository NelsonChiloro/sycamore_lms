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
        <h2 style="margin-top:0px">Fyer_holiday Read</h2>
        <table class="table">
	    <tr><td>Fyr Id</td><td><?php echo $fyr_id; ?></td></tr>
	    <tr><td>Date</td><td><?php echo $date; ?></td></tr>
	    <tr><td>Holiday Description</td><td><?php echo $holiday_description; ?></td></tr>
	    <tr><td>Date Added</td><td><?php echo $date_added; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('fyer_holiday') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>