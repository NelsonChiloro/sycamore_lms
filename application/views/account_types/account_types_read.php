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
        <h2 style="margin-top:0px">Account_types Read</h2>
        <table class="table">
	    <tr><td>Account Type Name</td><td><?php echo $account_type_name; ?></td></tr>
	    <tr><td>Added By</td><td><?php echo $added_by; ?></td></tr>
	    <tr><td>Date Added</td><td><?php echo $date_added; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('account_types') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>