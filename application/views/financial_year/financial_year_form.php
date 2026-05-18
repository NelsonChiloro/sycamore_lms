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
        <h2 style="margin-top:0px">Financial_year <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="date">Year Start <?php echo form_error('year_start') ?></label>
            <input type="text" class="form-control" name="year_start" id="year_start" placeholder="Year Start" value="<?php echo $year_start; ?>" />
        </div>
	    <div class="form-group">
            <label for="date">Year End <?php echo form_error('year_end') ?></label>
            <input type="text" class="form-control" name="year_end" id="year_end" placeholder="Year End" value="<?php echo $year_end; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Status <?php echo form_error('status') ?></label>
            <input type="text" class="form-control" name="status" id="status" placeholder="Status" value="<?php echo $status; ?>" />
        </div>
	    <input type="hidden" name="fyid" value="<?php echo $fyid; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('financial_year') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>