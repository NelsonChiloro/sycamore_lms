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
        <h2 style="margin-top:0px">Sms_settings List</h2>
        <div class="row" style="margin-bottom: 10px">
            <div class="col-md-4">
                <?php echo anchor(site_url('sms_settings/create'),'Create', 'class="btn btn-primary"'); ?>
            </div>
            <div class="col-md-4 text-center">
                <div style="margin-top: 8px" id="message">
                    <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                </div>
            </div>
            <div class="col-md-1 text-right">
            </div>
            <div class="col-md-3 text-right">
                <form action="<?php echo site_url('sms_settings/index'); ?>" class="form-inline" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="<?php echo $q; ?>">
                        <span class="input-group-btn">
                            <?php 
                                if ($q <> '')
                                {
                                    ?>
                                    <a href="<?php echo site_url('sms_settings'); ?>" class="btn btn-default">Reset</a>
                                    <?php
                                }
                            ?>
                          <button class="btn btn-primary" type="submit">Search</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
        <table class="table table-bordered" style="margin-bottom: 10px">
            <tr>
                <th>No</th>
		<th>Customer Approval</th>
		<th>Group Approval</th>
		<th>Loan Disbursement</th>
		<th>Before Notice</th>
		<th>Before Notice Period</th>
		<th>Arrears</th>
		<th>Arrears Age</th>
		<th>Customer App Pass Recovery</th>
		<th>Customer Birthday Notify</th>
		<th>Loan Payment Notification</th>
		<th>Deposit Withdraw Notification</th>
		<th>Action</th>
            </tr><?php
            foreach ($sms_settings_data as $sms_settings)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $sms_settings->customer_approval ?></td>
			<td><?php echo $sms_settings->group_approval ?></td>
			<td><?php echo $sms_settings->loan_disbursement ?></td>
			<td><?php echo $sms_settings->before_notice ?></td>
			<td><?php echo $sms_settings->before_notice_period ?></td>
			<td><?php echo $sms_settings->arrears ?></td>
			<td><?php echo $sms_settings->arrears_age ?></td>
			<td><?php echo $sms_settings->customer_app_pass_recovery ?></td>
			<td><?php echo $sms_settings->customer_birthday_notify ?></td>
			<td><?php echo $sms_settings->loan_payment_notification ?></td>
			<td><?php echo $sms_settings->deposit_withdraw_notification ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('sms_settings/read/'.$sms_settings->id),'Read'); 
				echo ' | '; 
				echo anchor(site_url('sms_settings/update/'.$sms_settings->id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('sms_settings/delete/'.$sms_settings->id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
				?>
			</td>
		</tr>
                <?php
            }
            ?>
        </table>
        <div class="row">
            <div class="col-md-6">
                <a href="#" class="btn btn-primary">Total Record : <?php echo $total_rows ?></a>
	    </div>
            <div class="col-md-6 text-right">
                <?php echo $pagination ?>
            </div>
        </div>
    </body>
</html>