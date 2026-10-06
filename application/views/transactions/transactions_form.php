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
        <h2 style="margin-top:0px">Transactions <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Ref <?php echo form_error('ref') ?></label>
            <input type="text" class="form-control" name="ref" id="ref" placeholder="Ref" value="<?php echo $ref; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Id <?php echo form_error('loan_id') ?></label>
            <input type="text" class="form-control" name="loan_id" id="loan_id" placeholder="Loan Id" value="<?php echo $loan_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Amount <?php echo form_error('amount') ?></label>
            <input type="text" class="form-control" name="amount" id="amount" placeholder="Amount" value="<?php echo $amount; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Payment Number <?php echo form_error('payment_number') ?></label>
            <input type="text" class="form-control" name="payment_number" id="payment_number" placeholder="Payment Number" value="<?php echo $payment_number; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Date Stamp <?php echo form_error('date_stamp') ?></label>
            <input type="text" class="form-control" name="date_stamp" id="date_stamp" placeholder="Date Stamp" value="<?php echo $date_stamp; ?>" />
        </div>
	    <input type="hidden" name="transaction_id" value="<?php echo $transaction_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('transactions') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>