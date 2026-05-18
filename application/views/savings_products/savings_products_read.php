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
        <h2 style="margin-top:0px">Savings_products Read</h2>
        <table class="table">
	    <tr><td>Name</td><td><?php echo $name; ?></td></tr>
	    <tr><td>Added By</td><td><?php echo $added_by; ?></td></tr>
	    <tr><td>Date Added</td><td><?php echo $date_added; ?></td></tr>
	    <tr><td>Interest Per Anum</td><td><?php echo $interest_per_anum; ?></td></tr>
	    <tr><td>Interest Method</td><td><?php echo $interest_method; ?></td></tr>
	    <tr><td>Interest Posting Frequency</td><td><?php echo $interest_posting_frequency; ?></td></tr>
	    <tr><td>When To Post</td><td><?php echo $when_to_post; ?></td></tr>
	    <tr><td>Minimum Balance For Interest</td><td><?php echo $minimum_balance_for_interest; ?></td></tr>
	    <tr><td>Minimum Balance Withdrawal</td><td><?php echo $minimum_balance_withdrawal; ?></td></tr>
	    <tr><td>Overdraft</td><td><?php echo $overdraft; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('savings_products') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>