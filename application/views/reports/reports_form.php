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
        <h2 style="margin-top:0px">Reports <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="datetime">Completed Time <?php echo form_error('completed_time') ?></label>
            <input type="text" class="form-control" name="completed_time" id="completed_time" placeholder="Completed Time" value="<?php echo $completed_time; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Download Link <?php echo form_error('download_link') ?></label>
            <input type="text" class="form-control" name="download_link" id="download_link" placeholder="Download Link" value="<?php echo $download_link; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Generated Time <?php echo form_error('generated_time') ?></label>
            <input type="text" class="form-control" name="generated_time" id="generated_time" placeholder="Generated Time" value="<?php echo $generated_time; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Report Type <?php echo form_error('report_type') ?></label>
            <input type="text" class="form-control" name="report_type" id="report_type" placeholder="Report Type" value="<?php echo $report_type; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Status <?php echo form_error('status') ?></label>
            <input type="text" class="form-control" name="status" id="status" placeholder="Status" value="<?php echo $status; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">User <?php echo form_error('user') ?></label>
            <input type="text" class="form-control" name="user" id="user" placeholder="User" value="<?php echo $user; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">User Id <?php echo form_error('user_id') ?></label>
            <input type="text" class="form-control" name="user_id" id="user_id" placeholder="User Id" value="<?php echo $user_id; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('reports') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>