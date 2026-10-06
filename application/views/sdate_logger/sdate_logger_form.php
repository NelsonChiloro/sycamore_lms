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
        <h2 style="margin-top:0px">Sdate_logger <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="enum">Table Name <?php echo form_error('table_name') ?></label>
            <input type="text" class="form-control" name="table_name" id="table_name" placeholder="Table Name" value="<?php echo $table_name; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Crud <?php echo form_error('crud') ?></label>
            <input type="text" class="form-control" name="crud" id="crud" placeholder="Crud" value="<?php echo $crud; ?>" />
        </div>
	    <div class="form-group">
            <label for="timestamp">Server Time <?php echo form_error('server_time') ?></label>
            <input type="text" class="form-control" name="server_time" id="server_time" placeholder="Server Time" value="<?php echo $server_time; ?>" />
        </div>
	    <div class="form-group">
            <label for="date">System Date <?php echo form_error('system_date') ?></label>
            <input type="text" class="form-control" name="system_date" id="system_date" placeholder="System Date" value="<?php echo $system_date; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('sdate_logger') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>