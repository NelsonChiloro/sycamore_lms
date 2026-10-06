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
        <h2 style="margin-top:0px">Menu <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Label <?php echo form_error('label') ?></label>
            <input type="text" class="form-control" name="label" id="label" placeholder="Label" value="<?php echo $label; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Type <?php echo form_error('type') ?></label>
            <input type="text" class="form-control" name="type" id="type" placeholder="Type" value="<?php echo $type; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Icon Color <?php echo form_error('icon_color') ?></label>
            <input type="text" class="form-control" name="icon_color" id="icon_color" placeholder="Icon Color" value="<?php echo $icon_color; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Link <?php echo form_error('link') ?></label>
            <input type="text" class="form-control" name="link" id="link" placeholder="Link" value="<?php echo $link; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Sort <?php echo form_error('sort') ?></label>
            <input type="text" class="form-control" name="sort" id="sort" placeholder="Sort" value="<?php echo $sort; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Parent <?php echo form_error('parent') ?></label>
            <input type="text" class="form-control" name="parent" id="parent" placeholder="Parent" value="<?php echo $parent; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Icon <?php echo form_error('icon') ?></label>
            <input type="text" class="form-control" name="icon" id="icon" placeholder="Icon" value="<?php echo $icon; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Menu Type Id <?php echo form_error('menu_type_id') ?></label>
            <input type="text" class="form-control" name="menu_type_id" id="menu_type_id" placeholder="Menu Type Id" value="<?php echo $menu_type_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Active <?php echo form_error('active') ?></label>
            <input type="text" class="form-control" name="active" id="active" placeholder="Active" value="<?php echo $active; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Roll <?php echo form_error('roll') ?></label>
            <input type="text" class="form-control" name="roll" id="roll" placeholder="Roll" value="<?php echo $roll; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('menu') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>