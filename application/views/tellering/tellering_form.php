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
        <h2 style="margin-top:0px">Tellering <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Teller <?php echo form_error('teller') ?></label>
            <input type="text" class="form-control" name="teller" id="teller" placeholder="Teller" value="<?php echo $teller; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Account <?php echo form_error('account') ?></label>
            <input type="text" class="form-control" name="account" id="account" placeholder="Account" value="<?php echo $account; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Date Time <?php echo form_error('date_time') ?></label>
            <input type="text" class="form-control" name="date_time" id="date_time" placeholder="Date Time" value="<?php echo $date_time; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('tellering') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>