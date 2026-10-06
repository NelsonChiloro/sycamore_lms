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
        <h2 style="margin-top:0px">Branches Read</h2>
        <table class="table">
	    <tr><td>BranchCode</td><td><?php echo $BranchCode; ?></td></tr>
	    <tr><td>BranchName</td><td><?php echo $BranchName; ?></td></tr>
	    <tr><td>AddressLine1</td><td><?php echo $AddressLine1; ?></td></tr>
	    <tr><td>AddressLine2</td><td><?php echo $AddressLine2; ?></td></tr>
	    <tr><td>City</td><td><?php echo $City; ?></td></tr>
	    <tr><td>Stamp</td><td><?php echo $Stamp; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('branches') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>