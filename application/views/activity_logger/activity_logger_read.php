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
        <h2 style="margin-top:0px">Activity_logger Read</h2>
        <table class="table">
	    <tr><td>User Id</td><td><?php echo $user_id; ?></td></tr>
	    <tr><td>Activity</td><td><?php echo $activity; ?></td></tr>
	    <tr><td>System Time</td><td><?php echo $system_time; ?></td></tr>
	    <tr><td>Server Time</td><td><?php echo $server_time; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('activity_logger') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>