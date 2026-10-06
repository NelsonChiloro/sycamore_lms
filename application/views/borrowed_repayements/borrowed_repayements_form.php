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
        <h2 style="margin-top:0px">Borrowed_repayements <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="int">Borrowed Id <?php echo form_error('borrowed_id') ?></label>
            <input type="text" class="form-control" name="borrowed_id" id="borrowed_id" placeholder="Borrowed Id" value="<?php echo $borrowed_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Interest Paid <?php echo form_error('interest_paid') ?></label>
            <input type="text" class="form-control" name="interest_paid" id="interest_paid" placeholder="Interest Paid" value="<?php echo $interest_paid; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Principal Paid <?php echo form_error('principal_paid') ?></label>
            <input type="text" class="form-control" name="principal_paid" id="principal_paid" placeholder="Principal Paid" value="<?php echo $principal_paid; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Paid By <?php echo form_error('paid_by') ?></label>
            <input type="text" class="form-control" name="paid_by" id="paid_by" placeholder="Paid By" value="<?php echo $paid_by; ?>" />
        </div>
	    <div class="form-group">
            <label for="date">Date Of Repaymet <?php echo form_error('date_of_repaymet') ?></label>
            <input type="text" class="form-control" name="date_of_repaymet" id="date_of repaymet" placeholder="Date Of Repaymet" value="<?php echo $date_of_repaymet; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Stamp <?php echo form_error('stamp') ?></label>
            <input type="text" class="form-control" name="stamp" id="stamp" placeholder="Stamp" value="<?php echo $stamp; ?>" />
        </div>
	    <input type="hidden" name="b_id" value="<?php echo $b_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('borrowed_repayements') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>
