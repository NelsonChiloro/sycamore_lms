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
        <h2 style="margin-top:0px">Collateral <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Loan Id <?php echo form_error('loan_id') ?></label>
            <input type="text" class="form-control" name="loan_id" id="loan_id" placeholder="Loan Id" value="<?php echo $loan_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Collateral Name <?php echo form_error('collateral_name') ?></label>
            <input type="text" class="form-control" name="collateral_name" id="collateral_name" placeholder="Collateral Name" value="<?php echo $collateral_name; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Collateral Type <?php echo form_error('collateral_type') ?></label>
            <input type="text" class="form-control" name="collateral_type" id="collateral_type" placeholder="Collateral Type" value="<?php echo $collateral_type; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Serial <?php echo form_error('serial') ?></label>
            <input type="text" class="form-control" name="serial" id="serial" placeholder="Serial" value="<?php echo $serial; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Estimated Price <?php echo form_error('estimated_price') ?></label>
            <input type="text" class="form-control" name="estimated_price" id="estimated_price" placeholder="Estimated Price" value="<?php echo $estimated_price; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Attachement <?php echo form_error('attachement') ?></label>
            <input type="text" class="form-control" name="attachement" id="attachement" placeholder="Attachement" value="<?php echo $attachement; ?>" />
        </div>
	    <div class="form-group">
            <label for="description">Description <?php echo form_error('description') ?></label>
            <textarea class="form-control" rows="3" name="description" id="description" placeholder="Description"><?php echo $description; ?></textarea>
        </div>
	    <div class="form-group">
            <label for="timestamp">Date Added <?php echo form_error('date_added') ?></label>
            <input type="text" class="form-control" name="date_added" id="date_added" placeholder="Date Added" value="<?php echo $date_added; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Added By <?php echo form_error('added_by') ?></label>
            <input type="text" class="form-control" name="added_by" id="added_by" placeholder="Added By" value="<?php echo $added_by; ?>" />
        </div>
	    <input type="hidden" name="collateral_id" value="<?php echo $collateral_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('collateral') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>