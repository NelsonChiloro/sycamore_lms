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
        <h2 style="margin-top:0px">Customer_groups <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="int">Customer <?php echo form_error('customer') ?></label>
            <input type="text" class="form-control" name="customer" id="customer" placeholder="Customer" value="<?php echo $customer; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Group Id <?php echo form_error('group_id') ?></label>
            <input type="text" class="form-control" name="group_id" id="group_id" placeholder="Group Id" value="<?php echo $group_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Date Joined <?php echo form_error('date_joined') ?></label>
            <input type="text" class="form-control" name="date_joined" id="date_joined" placeholder="Date Joined" value="<?php echo $date_joined; ?>" />
        </div>
	    <input type="hidden" name="customer_group_id" value="<?php echo $customer_group_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('customer_groups') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>