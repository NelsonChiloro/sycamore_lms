<?php
$vault = get_by_id('account','is_vault',"Yes")
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">internal  cash account </h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">cash account configure</a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
		<h3>Configure Vault</h3>
		<form action="<?php echo base_url('Internal_accounts/update_vault')?>" method="post">
			<table class="table table-bordered">
				<tr>
					<td>Current  assigned vault account</td>
					<td>Assign new</td>
					<td></td>
				</tr>
				<tr>
					<td><?php
						if(empty($vault)){
							echo "Not configured";
						}else{
							echo $vault->account_number;
						}
						?></td>
					<td>
						<select name="account_number" class="form-control" required>
							<option value="">--select--account</option>
							<?php
							foreach ($all_cash as $a){
							?>
							<option value="<?php echo $a->account_number ?>"><?php echo $a->account_number." ".$a->account_name ?></option>
							<?php
							}
							?>
						</select>

					</td>
					<td><input type="submit" value="save" class="btn btn-danger"></td>
				</tr>
			</table>
		</form>
		<hr>
		<h3>Configure Teller</h3>
			<table class="table table-bordered">
				<thead>
				<tr>
					<th>Account</th>
					<th>Account name </th>
					<th>Account Desc </th>
					<th>Teller account user</th>
					<th>Action</th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ($teller as $account){
					?>
					<tr>
						<td><?php echo $account->account_number ?></td>
						<td><?php echo $account->account_name ?></td>
						<td><?php echo $account->account_desc ?></td>
						<td><?php
							$t = $this->Tellering_model->get_mine($account->account_number );
							if(empty($t)){
								echo "Not configured";
							}else {
								echo $t->Firstname . " " . $t->Lastname;
							}

							?></td>
						<td><button class="btn btn-info" onclick="mish('<?php echo $account->account_number?>')">Configure</button></td>
					</tr>

					<?php
				}
				?>
				</tbody>

			</table>
	</div>
</div>
</div>

<div aria-hidden="true" class="onboarding-modal modal fade" id="tellering-modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Configure teller process</h4>
				<p style="color: red;">Are you sure you want to assign this account  <i id="textt" style="font-weight: bolder; color: green;"></i> to user below</p>
				<form action="<?php echo base_url('Tellering/create_action')?>" method="post" class="form-row" enctype="multipart/form-data">
					<input type="text" name="account" id="tellering_account" hidden >


					<div class="form-group col-12">
						<select name="teller" id="" class="form-control">
							<option value="">--select user--</option>
							<?php
							foreach ($all_user as $u){
								?>
								<option value="<?php echo $u->Employee ?>"><?php echo $u->Firstname." ".$u->Lastname ?></option>
							<?php
							}
							?>
						</select>
					</div>


					<button type="submit" class="btn btn-primary">Save Changes</button>

				</form>
			</div>
		</div>
	</div>
</div>
