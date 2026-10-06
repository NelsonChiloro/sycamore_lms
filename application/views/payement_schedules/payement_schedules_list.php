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
        <h2 style="margin-top:0px">Payement_schedules List</h2>
        <div class="row" style="margin-bottom: 10px">
            <div class="col-md-4">
                <?php echo anchor(site_url('payement_schedules/create'),'Create', 'class="btn btn-primary"'); ?>
            </div>
            <div class="col-md-4 text-center">
                <div style="margin-top: 8px" id="message">
                    <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                </div>
            </div>
            <div class="col-md-1 text-right">
            </div>
            <div class="col-md-3 text-right">
                <form action="<?php echo site_url('payement_schedules/index'); ?>" class="form-inline" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="<?php echo $q; ?>">
                        <span class="input-group-btn">
                            <?php 
                                if ($q <> '')
                                {
                                    ?>
                                    <a href="<?php echo site_url('payement_schedules'); ?>" class="btn btn-default">Reset</a>
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
		<th>Id</th>
		<th>Customer</th>
		<th>Loan Id</th>
		<th>Payment Schedule</th>
		<th>Payment Number</th>
		<th>Amount</th>
		<th>Principal</th>
		<th>Interest</th>
		<th>Paid Amount</th>
		<th>Loan Balance</th>
		<th>Status</th>
		<th>Loan Date</th>
		<th>Paid Date</th>
		<th>Marked Due</th>
		<th>Marked Due Date</th>
		<th>Action</th>
            </tr><?php
            foreach ($payement_schedules_data as $payement_schedules)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $payement_schedules->id ?></td>
			<td><?php echo $payement_schedules->customer ?></td>
			<td><?php echo $payement_schedules->loan_id ?></td>
			<td><?php echo $payement_schedules->payment_schedule ?></td>
			<td><?php echo $payement_schedules->payment_number ?></td>
			<td><?php echo $payement_schedules->amount ?></td>
			<td><?php echo $payement_schedules->principal ?></td>
			<td><?php echo $payement_schedules->interest ?></td>
			<td><?php echo $payement_schedules->paid_amount ?></td>
			<td><?php echo $payement_schedules->loan_balance ?></td>
			<td><?php echo $payement_schedules->status ?></td>
			<td><?php echo $payement_schedules->loan_date ?></td>
			<td><?php echo $payement_schedules->paid_date ?></td>
			<td><?php echo $payement_schedules->marked_due ?></td>
			<td><?php echo $payement_schedules->marked_due_date ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('payement_schedules/read/'.$payement_schedules->id),'Read');
				echo ' | '; 
				echo anchor(site_url('payement_schedules/update/'.$payement_schedules->id),'Update');
				echo ' | '; 
				echo anchor(site_url('payement_schedules/delete/'.$payement_schedules->id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"');
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
