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
        <h2 style="margin-top:0px">Loan <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Loan Number <?php echo form_error('loan_number') ?></label>
            <input type="text" class="form-control" name="loan_number" id="loan_number" placeholder="Loan Number" value="<?php echo $loan_number; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Loan Product <?php echo form_error('loan_product') ?></label>
            <input type="text" class="form-control" name="loan_product" id="loan_product" placeholder="Loan Product" value="<?php echo $loan_product; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Loan Customer <?php echo form_error('loan_customer') ?></label>
            <input type="text" class="form-control" name="loan_customer" id="loan_customer" placeholder="Loan Customer" value="<?php echo $loan_customer; ?>" />
        </div>
	    <div class="form-group">
            <label for="date">Loan Date <?php echo form_error('loan_date') ?></label>
            <input type="text" class="form-control" name="loan_date" id="loan_date" placeholder="Loan Date" value="<?php echo $loan_date; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Loan Principal <?php echo form_error('loan_principal') ?></label>
            <input type="text" class="form-control" name="loan_principal" id="loan_principal" placeholder="Loan Principal" value="<?php echo $loan_principal; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Loan Period <?php echo form_error('loan_period') ?></label>
            <input type="text" class="form-control" name="loan_period" id="loan_period" placeholder="Loan Period" value="<?php echo $loan_period; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Period Type <?php echo form_error('period_type') ?></label>
            <input type="text" class="form-control" name="period_type" id="period_type" placeholder="Period Type" value="<?php echo $period_type; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Loan Interest <?php echo form_error('loan_interest') ?></label>
            <input type="text" class="form-control" name="loan_interest" id="loan_interest" placeholder="Loan Interest" value="<?php echo $loan_interest; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Loan Amount Total <?php echo form_error('loan_amount_total') ?></label>
            <input type="text" class="form-control" name="loan_amount_total" id="loan_amount_total" placeholder="Loan Amount Total" value="<?php echo $loan_amount_total; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Next Payment Id <?php echo form_error('next_payment_id') ?></label>
            <input type="text" class="form-control" name="next_payment_id" id="next_payment_id" placeholder="Next Payment Id" value="<?php echo $next_payment_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Loan Added By <?php echo form_error('loan_added_by') ?></label>
            <input type="text" class="form-control" name="loan_added_by" id="loan_added_by" placeholder="Loan Added By" value="<?php echo $loan_added_by; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Loan Approved By <?php echo form_error('loan_approved_by') ?></label>
            <input type="text" class="form-control" name="loan_approved_by" id="loan_approved_by" placeholder="Loan Approved By" value="<?php echo $loan_approved_by; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Loan Status <?php echo form_error('loan_status') ?></label>
            <input type="text" class="form-control" name="loan_status" id="loan_status" placeholder="Loan Status" value="<?php echo $loan_status; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Loan Added Date <?php echo form_error('loan_added_date') ?></label>
            <input type="text" class="form-control" name="loan_added_date" id="loan_added_date" placeholder="Loan Added Date" value="<?php echo $loan_added_date; ?>" />
        </div>
	    <input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('loan') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>