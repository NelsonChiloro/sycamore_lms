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
        <h2 style="margin-top:0px">Currency Read</h2>
        <table class="table">
	    <tr><td>Name</td><td><?php echo $name; ?></td></tr>
	    <tr><td>Symbol</td><td><?php echo $symbol; ?></td></tr>
	    <tr><td>Code</td><td><?php echo $code; ?></td></tr>
	    <tr><td>Is Local</td><td><?php echo $is_local; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('currency') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>