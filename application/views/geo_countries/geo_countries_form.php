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
        <h2 style="margin-top:0px">Geo_countries <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Name <?php echo form_error('name') ?></label>
            <input type="text" class="form-control" name="name" id="name" placeholder="Name" value="<?php echo $name; ?>" />
        </div>
	    <div class="form-group">
            <label for="char">Abv3 <?php echo form_error('abv3') ?></label>
            <input type="text" class="form-control" name="abv3" id="abv3" placeholder="Abv3" value="<?php echo $abv3; ?>" />
        </div>
	    <div class="form-group">
            <label for="char">Abv3 Alt <?php echo form_error('abv3_alt') ?></label>
            <input type="text" class="form-control" name="abv3_alt" id="abv3_alt" placeholder="Abv3 Alt" value="<?php echo $abv3_alt; ?>" />
        </div>
	    <div class="form-group">
            <label for="char">Code <?php echo form_error('code') ?></label>
            <input type="text" class="form-control" name="code" id="code" placeholder="Code" value="<?php echo $code; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Slug <?php echo form_error('slug') ?></label>
            <input type="text" class="form-control" name="slug" id="slug" placeholder="Slug" value="<?php echo $slug; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('geo_countries') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>