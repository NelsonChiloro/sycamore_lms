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
        <h2 style="margin-top:0px">Transaction <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Account Number <?php echo form_error('account_number') ?></label>
            <input type="text" class="form-control" name="account_number" id="account_number" placeholder="Account Number" value="<?php echo $account_number; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Transaction Id <?php echo form_error('transaction_id') ?></label>
            <input type="text" class="form-control" name="transaction_id" id="transaction_id" placeholder="Transaction Id" value="<?php echo $transaction_id; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Credit <?php echo form_error('credit') ?></label>
            <input type="text" class="form-control" name="credit" id="credit" placeholder="Credit" value="<?php echo $credit; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Debit <?php echo form_error('debit') ?></label>
            <input type="text" class="form-control" name="debit" id="debit" placeholder="Debit" value="<?php echo $debit; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Balance <?php echo form_error('balance') ?></label>
            <input type="text" class="form-control" name="balance" id="balance" placeholder="Balance" value="<?php echo $balance; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">System Time <?php echo form_error('system_time') ?></label>
            <input type="text" class="form-control" name="system_time" id="system_time" placeholder="System Time" value="<?php echo $system_time; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Server Time <?php echo form_error('server_time') ?></label>
            <input type="text" class="form-control" name="server_time" id="server_time" placeholder="Server Time" value="<?php echo $server_time; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('transaction') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>