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
        <h2 style="margin-top:0px">Sytem_date <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="date">S Date <?php echo form_error('s_date') ?></label>
            <input type="text" class="form-control" name="s_date" id="s_date" placeholder="S Date" value="<?php echo $s_date; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Is Active <?php echo form_error('is_active') ?></label>
            <input type="text" class="form-control" name="is_active" id="is_active" placeholder="Is Active" value="<?php echo $is_active; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('sytem_date') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>