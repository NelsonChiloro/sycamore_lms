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
        <h2 style="margin-top:0px">Access <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="int">Roleid <?php echo form_error('roleid') ?></label>
            <input type="text" class="form-control" name="roleid" id="roleid" placeholder="Roleid" value="<?php echo $roleid; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Controllerid <?php echo form_error('controllerid') ?></label>
            <input type="text" class="form-control" name="controllerid" id="controllerid" placeholder="Controllerid" value="<?php echo $controllerid; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('access') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>