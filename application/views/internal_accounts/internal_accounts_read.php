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
        <h2 style="margin-top:0px">Internal_accounts Read</h2>
        <table class="table">
	    <tr><td>Account Name</td><td><?php echo $account_name; ?></td></tr>
	    <tr><td>Is Cash Account</td><td><?php echo $is_cash_account; ?></td></tr>
	    <tr><td>Adde By</td><td><?php echo $adde_by; ?></td></tr>
	    <tr><td>Date Created</td><td><?php echo $date_created; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('internal_accounts') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>