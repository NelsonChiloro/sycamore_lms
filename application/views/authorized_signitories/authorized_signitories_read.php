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
        <h2 style="margin-top:0px">Authorized_signitories Read</h2>
        <table class="table">
	    <tr><td>ClientId</td><td><?php echo $ClientId; ?></td></tr>
	    <tr><td>FullLegalName</td><td><?php echo $FullLegalName; ?></td></tr>
	    <tr><td>IDType</td><td><?php echo $IDType; ?></td></tr>
	    <tr><td>IDNumber</td><td><?php echo $IDNumber; ?></td></tr>
	    <tr><td>DocImageURL</td><td><?php echo $DocImageURL; ?></td></tr>
	    <tr><td>SignatureImageURL</td><td><?php echo $SignatureImageURL; ?></td></tr>
	    <tr><td>Stamp</td><td><?php echo $Stamp; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('authorized_signitories') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>