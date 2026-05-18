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
        <h2 style="margin-top:0px">Group_loan_tracker <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="int">Disbursement Id <?php echo form_error('disbursement_id') ?></label>
            <input type="text" class="form-control" name="disbursement_id" id="disbursement_id" placeholder="Disbursement Id" value="<?php echo $disbursement_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Group Id <?php echo form_error('group_id') ?></label>
            <input type="text" class="form-control" name="group_id" id="group_id" placeholder="Group Id" value="<?php echo $group_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Customer Id <?php echo form_error('customer_id') ?></label>
            <input type="text" class="form-control" name="customer_id" id="customer_id" placeholder="Customer Id" value="<?php echo $customer_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Amount <?php echo form_error('amount') ?></label>
            <input type="text" class="form-control" name="amount" id="amount" placeholder="Amount" value="<?php echo $amount; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Date Added <?php echo form_error('date_added') ?></label>
            <input type="text" class="form-control" name="date_added" id="date_added" placeholder="Date Added" value="<?php echo $date_added; ?>" />
        </div>
	    <input type="hidden" name="group_loan_tracker_id" value="<?php echo $group_loan_tracker_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('group_loan_tracker') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>