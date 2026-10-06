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
        <h2 style="margin-top:0px">Sytem_date Read</h2>
        <table class="table">
	    <tr><td>S Date</td><td><?php echo $s_date; ?></td></tr>
	    <tr><td>Is Active</td><td><?php echo $is_active; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('sytem_date') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>