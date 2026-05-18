<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">internal account list</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">internal accounts list</a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<!--			<a href="--><?php //echo base_url('account/create')?><!--" class="btn btn-sm btn-primary"><i class="anticon anticon-plus-square" style="color: white; font-size: 20px;"></i>Add Savings account</a>-->
			<div class="m-t-25">
				<table id="data-table" class="table">
					<thead>
            <tr>
                <th>No</th>
                <th>AccNo</th>
		<th>Account Name</th>
		<th>Is Cash Account</th>
		<th>Account Description</th>
		<th>Added By</th>
		<th>Date Created</th>
		<th>Action</th>
            </tr>
					</thead>
					<tbody>
			<?php
			$start = 0;
            foreach ($internal_accounts_data as $internal_accounts)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $internal_accounts->account_number ?></td>
			<td><?php echo $internal_accounts->account_name ?></td>
			<td><?php echo $internal_accounts->is_cash_account ?></td>
			<td><?php echo $internal_accounts->account_desc ?></td>
			<td><?php echo $internal_accounts->Firstname." ".$internal_accounts->Lastname ?></td>
			<td><?php echo $internal_accounts->date_created ?></td>
			<td style="text-align:center" width="200px">
				<?php 

				echo anchor(site_url('internal_accounts/update/'.$internal_accounts->internal_account_id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('internal_accounts/delete/'.$internal_accounts->internal_account_id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
				?>
			</td>
		</tr>
					</tbody>
                <?php
            }
            ?>
        </table>
			</div>
		</div>
	</div>
</div>
