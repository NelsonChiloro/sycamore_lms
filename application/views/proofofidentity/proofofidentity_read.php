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
        <h2 style="margin-top:0px">Proofofidentity Read</h2>
        <table class="table">
	    <tr><td>IDType</td><td><?php echo $IDType; ?></td></tr>
	    <tr><td>IDNumber</td><td><?php echo $IDNumber; ?></td></tr>
	    <tr><td>IssueDate</td><td><?php echo $IssueDate; ?></td></tr>
	    <tr><td>ExpiryDate</td><td><?php echo $ExpiryDate; ?></td></tr>
	    <tr><td>DocImageURL</td><td><?php echo $DocImageURL; ?></td></tr>
	    <tr><td>Stamp</td><td><?php echo $Stamp; ?></td></tr>
	    <tr><td>ClientId</td><td><?php echo $ClientId; ?></td></tr>
	    <tr><td>Photograph</td><td><?php echo $photograph; ?></td></tr>
	    <tr><td>Signature</td><td><?php echo $signature; ?></td></tr>
	    <tr><td>Id Back</td><td><?php echo $Id_back; ?></td></tr>
	    <tr><td>Id Front</td><td><?php echo $id_front; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('proofofidentity') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>