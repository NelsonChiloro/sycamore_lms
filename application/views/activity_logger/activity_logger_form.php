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
        <h2 style="margin-top:0px">Activity_logger <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">User Id <?php echo form_error('user_id') ?></label>
            <input type="text" class="form-control" name="user_id" id="user_id" placeholder="User Id" value="<?php echo $user_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="activity">Activity <?php echo form_error('activity') ?></label>
            <textarea class="form-control" rows="3" name="activity" id="activity" placeholder="Activity"><?php echo $activity; ?></textarea>
        </div>
	    <div class="form-group">
            <label for="datetime">System Time <?php echo form_error('system_time') ?></label>
            <input type="text" class="form-control" name="system_time" id="system_time" placeholder="System Time" value="<?php echo $system_time; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Server Time <?php echo form_error('server_time') ?></label>
            <input type="text" class="form-control" name="server_time" id="server_time" placeholder="Server Time" value="<?php echo $server_time; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('activity_logger') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>