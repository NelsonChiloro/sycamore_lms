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
        <h2 style="margin-top:0px">Menuitems <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="int">Mid <?php echo form_error('mid') ?></label>
            <input type="text" class="form-control" name="mid" id="mid" placeholder="Mid" value="<?php echo $mid; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Label <?php echo form_error('label') ?></label>
            <input type="text" class="form-control" name="label" id="label" placeholder="Label" value="<?php echo $label; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Method <?php echo form_error('method') ?></label>
            <input type="text" class="form-control" name="method" id="method" placeholder="Method" value="<?php echo $method; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Fa Icon <?php echo form_error('fa_icon') ?></label>
            <input type="text" class="form-control" name="fa_icon" id="fa_icon" placeholder="Fa Icon" value="<?php echo $fa_icon; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Sortt <?php echo form_error('sortt') ?></label>
            <input type="text" class="form-control" name="sortt" id="sortt" placeholder="Sortt" value="<?php echo $sortt; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('menuitems') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>