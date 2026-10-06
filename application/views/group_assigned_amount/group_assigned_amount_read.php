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
        <h2 style="margin-top:0px">Group_assigned_amount Read</h2>
        <table class="table">
	    <tr><td>Group Id</td><td><?php echo $group_id; ?></td></tr>
	    <tr><td>Amount</td><td><?php echo $amount; ?></td></tr>
	    <tr><td>Status</td><td><?php echo $status; ?></td></tr>
	    <tr><td>Approval Comment</td><td><?php echo $approval_comment; ?></td></tr>
	    <tr><td>Reject Comment</td><td><?php echo $reject_comment; ?></td></tr>
	    <tr><td>Disbursed By</td><td><?php echo $disbursed_by; ?></td></tr>
	    <tr><td>Date Disbursed</td><td><?php echo $date_disbursed; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('group_assigned_amount') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>