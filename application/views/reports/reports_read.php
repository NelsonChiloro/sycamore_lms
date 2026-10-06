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
        <h2 style="margin-top:0px">Reports Read</h2>
        <table class="table">
	    <tr><td>Completed Time</td><td><?php echo $completed_time; ?></td></tr>
	    <tr><td>Download Link</td><td><?php echo $download_link; ?></td></tr>
	    <tr><td>Generated Time</td><td><?php echo $generated_time; ?></td></tr>
	    <tr><td>Report Type</td><td><?php echo $report_type; ?></td></tr>
	    <tr><td>Status</td><td><?php echo $status; ?></td></tr>
	    <tr><td>User</td><td><?php echo $user; ?></td></tr>
	    <tr><td>User Id</td><td><?php echo $user_id; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('reports') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>