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
        <h2 style="margin-top:0px">Vault_cashier_pends Read</h2>
        <table class="table">
	    <tr><td>Vault Account</td><td><?php echo $vault_account; ?></td></tr>
	    <tr><td>Teller Account</td><td><?php echo $teller_account; ?></td></tr>
	    <tr><td>Amount</td><td><?php echo $amount; ?></td></tr>
	    <tr><td>Creator</td><td><?php echo $creator; ?></td></tr>
	    <tr><td>Teller</td><td><?php echo $teller; ?></td></tr>
	    <tr><td>System Date</td><td><?php echo $system_date; ?></td></tr>
	    <tr><td>Status</td><td><?php echo $status; ?></td></tr>
	    <tr><td>Stamp</td><td><?php echo $stamp; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('vault_cashier_pends') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>