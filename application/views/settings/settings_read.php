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
        <h2 style="margin-top:0px">Settings Read</h2>
        <table class="table">
	    <tr><td>Logo</td><td><?php echo $logo; ?></td></tr>
	    <tr><td>Address</td><td><?php echo $address; ?></td></tr>
	    <tr><td>Phone Number</td><td><?php echo $phone_number; ?></td></tr>
	    <tr><td>Company Name</td><td><?php echo $company_name; ?></td></tr>
	    <tr><td>Company Email</td><td><?php echo $company_email; ?></td></tr>
	    <tr><td>Currency</td><td><?php echo $currency; ?></td></tr>
	    <tr><td>Time Zone</td><td><?php echo $time_zone; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('settings') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>