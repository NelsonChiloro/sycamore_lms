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
        <h2 style="margin-top:0px">Account List</h2>
        <div class="row" style="margin-bottom: 10px">
            <div class="col-md-4">
                <?php echo anchor(site_url('account/create'),'Create', 'class="btn btn-primary"'); ?>
            </div>
            <div class="col-md-4 text-center">
                <div style="margin-top: 8px" id="message">
                    <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                </div>
            </div>
            <div class="col-md-1 text-right">
            </div>
            <div class="col-md-3 text-right">
                <form action="<?php echo site_url('account/index'); ?>" class="form-inline" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="<?php echo $q; ?>">
                        <span class="input-group-btn">
                            <?php 
                                if ($q <> '')
                                {
                                    ?>
                                    <a href="<?php echo site_url('account'); ?>" class="btn btn-default">Reset</a>
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
		<th>Client Id</th>
		<th>Account Number</th>
		<th>Balance</th>
		<th>Account Type</th>
		<th>Account Type Product</th>
		<th>Added By</th>
		<th>Date Added</th>
		<th>Action</th>
            </tr><?php
            foreach ($account_data as $account)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $account->client_id ?></td>
			<td><?php echo $account->account_number ?></td>
			<td><?php echo $account->balance ?></td>
			<td><?php echo $account->account_type ?></td>
			<td><?php echo $account->account_type_product ?></td>
			<td><?php echo $account->added_by ?></td>
			<td><?php echo $account->date_added ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('account/read/'.$account->account_id),'Read'); 
				echo ' | '; 
				echo anchor(site_url('account/update/'.$account->account_id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('account/delete/'.$account->account_id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
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