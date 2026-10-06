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
        <h2 style="margin-top:0px">Collateral Read</h2>
        <table class="table">
	    <tr><td>Loan Id</td><td><?php echo $loan_id; ?></td></tr>
	    <tr><td>Collateral Name</td><td><?php echo $collateral_name; ?></td></tr>
	    <tr><td>Collateral Type</td><td><?php echo $collateral_type; ?></td></tr>
	    <tr><td>Serial</td><td><?php echo $serial; ?></td></tr>
	    <tr><td>Estimated Price</td><td><?php echo $estimated_price; ?></td></tr>
	    <tr><td>Attachement</td><td><?php echo $attachement; ?></td></tr>
	    <tr><td>Description</td><td><?php echo $description; ?></td></tr>
	    <tr><td>Date Added</td><td><?php echo $date_added; ?></td></tr>
	    <tr><td>Added By</td><td><?php echo $added_by; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('collateral') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>