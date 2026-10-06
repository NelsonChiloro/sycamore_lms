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
        <h2 style="margin-top:0px">User_group <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="int">User Id <?php echo form_error('user_id') ?></label>
            <input type="text" class="form-control" name="user_id" id="user_id" placeholder="User Id" value="<?php echo $user_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Group Id <?php echo form_error('group_id') ?></label>
            <input type="text" class="form-control" name="group_id" id="group_id" placeholder="Group Id" value="<?php echo $group_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Added By <?php echo form_error('added_by') ?></label>
            <input type="text" class="form-control" name="added_by" id="added_by" placeholder="Added By" value="<?php echo $added_by; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Date Created <?php echo form_error('date_created') ?></label>
            <input type="text" class="form-control" name="date_created" id="date_created" placeholder="Date Created" value="<?php echo $date_created; ?>" />
        </div>
	    <input type="hidden" name="user_group_id" value="<?php echo $user_group_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('user_group') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>