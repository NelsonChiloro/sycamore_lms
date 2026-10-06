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
        <h2 style="margin-top:0px">Fyer_holiday <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="int">Fyr Id <?php echo form_error('fyr_id') ?></label>
            <input type="text" class="form-control" name="fyr_id" id="fyr_id" placeholder="Fyr Id" value="<?php echo $fyr_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="date">Date <?php echo form_error('date') ?></label>
            <input type="text" class="form-control" name="date" id="date" placeholder="Date" value="<?php echo $date; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Holiday Description <?php echo form_error('holiday_description') ?></label>
            <input type="text" class="form-control" name="holiday_description" id="holiday_description" placeholder="Holiday Description" value="<?php echo $holiday_description; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Date Added <?php echo form_error('date_added') ?></label>
            <input type="text" class="form-control" name="date_added" id="date_added" placeholder="Date Added" value="<?php echo $date_added; ?>" />
        </div>
	    <input type="hidden" name="fyer_id" value="<?php echo $fyer_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('fyer_holiday') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>