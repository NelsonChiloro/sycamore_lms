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
        <h2 style="margin-top:0px">Charges Read</h2>
        <table class="table">
	    <tr><td>Name</td><td><?php echo $name; ?></td></tr>
	    <tr><td>Charge Type</td><td><?php echo $charge_type; ?></td></tr>
	    <tr><td>Fixed Amount</td><td><?php echo $fixed_amount; ?></td></tr>
	    <tr><td>Variable Value</td><td><?php echo $variable_value; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('charges') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>