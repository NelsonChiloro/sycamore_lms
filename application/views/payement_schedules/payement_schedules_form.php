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
        <h2 style="margin-top:0px">Payement_schedules <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="int">Id <?php echo form_error('id') ?></label>
            <input type="text" class="form-control" name="id" id="id" placeholder="Id" value="<?php echo $id; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Customer <?php echo form_error('customer') ?></label>
            <input type="text" class="form-control" name="customer" id="customer" placeholder="Customer" value="<?php echo $customer; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Loan Id <?php echo form_error('loan_id') ?></label>
            <input type="text" class="form-control" name="loan_id" id="loan_id" placeholder="Loan Id" value="<?php echo $loan_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Payment Schedule <?php echo form_error('payment_schedule') ?></label>
            <input type="text" class="form-control" name="payment_schedule" id="payment_schedule" placeholder="Payment Schedule" value="<?php echo $payment_schedule; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Payment Number <?php echo form_error('payment_number') ?></label>
            <input type="text" class="form-control" name="payment_number" id="payment_number" placeholder="Payment Number" value="<?php echo $payment_number; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Amount <?php echo form_error('amount') ?></label>
            <input type="text" class="form-control" name="amount" id="amount" placeholder="Amount" value="<?php echo $amount; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Principal <?php echo form_error('principal') ?></label>
            <input type="text" class="form-control" name="principal" id="principal" placeholder="Principal" value="<?php echo $principal; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Interest <?php echo form_error('interest') ?></label>
            <input type="text" class="form-control" name="interest" id="interest" placeholder="Interest" value="<?php echo $interest; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Paid Amount <?php echo form_error('paid_amount') ?></label>
            <input type="text" class="form-control" name="paid_amount" id="paid_amount" placeholder="Paid Amount" value="<?php echo $paid_amount; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Loan Balance <?php echo form_error('loan_balance') ?></label>
            <input type="text" class="form-control" name="loan_balance" id="loan_balance" placeholder="Loan Balance" value="<?php echo $loan_balance; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Status <?php echo form_error('status') ?></label>
            <input type="text" class="form-control" name="status" id="status" placeholder="Status" value="<?php echo $status; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Loan Date <?php echo form_error('loan_date') ?></label>
            <input type="text" class="form-control" name="loan_date" id="loan_date" placeholder="Loan Date" value="<?php echo $loan_date; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Paid Date <?php echo form_error('paid_date') ?></label>
            <input type="text" class="form-control" name="paid_date" id="paid_date" placeholder="Paid Date" value="<?php echo $paid_date; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Marked Due <?php echo form_error('marked_due') ?></label>
            <input type="text" class="form-control" name="marked_due" id="marked_due" placeholder="Marked Due" value="<?php echo $marked_due; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Marked Due Date <?php echo form_error('marked_due_date') ?></label>
            <input type="text" class="form-control" name="marked_due_date" id="marked_due_date" placeholder="Marked Due Date" value="<?php echo $marked_due_date; ?>" />
        </div>
	    <input type="hidden" name="" value="<?php echo $; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('payement_schedules') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>