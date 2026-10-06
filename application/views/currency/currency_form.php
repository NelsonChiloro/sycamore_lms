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
        <h2 style="margin-top:0px">Currency <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Name <?php echo form_error('name') ?></label>
            <input type="text" class="form-control" name="name" id="name" placeholder="Name" value="<?php echo $name; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Symbol <?php echo form_error('symbol') ?></label>
            <input type="text" class="form-control" name="symbol" id="symbol" placeholder="Symbol" value="<?php echo $symbol; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Code <?php echo form_error('code') ?></label>
            <input type="text" class="form-control" name="code" id="code" placeholder="Code" value="<?php echo $code; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Is Local <?php echo form_error('is_local') ?></label>
            <input type="text" class="form-control" name="is_local" id="is_local" placeholder="Is Local" value="<?php echo $is_local; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('currency') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>