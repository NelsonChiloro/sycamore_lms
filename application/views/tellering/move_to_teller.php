<?php
$te = $this->Tellering_model->get_all2();


$requests = $this->Vault_cashier_pends_model->get_cash();
$v = get_by_id('account','is_vault','Yes');
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Cash operation</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Vault to cashier</a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
									<div class="row">
										<div class="col-6">
											<div class="form-group">
												<label for="">Vault Account</label>

											</div>
											<div  style="border: thick solid black; height: 250px; border-radius: 15px; padding: 2em;">

											<table class="table table-bordered" id="vactb">

												<tr>
													<td>Account number:</td>
													<td><?php echo $v->account_number ?></td>

												</tr>

												<tr>

													<td>Account balance:</td>
													<td><?php echo $v->balance?> </td>
												</tr>
											</table>
											</div>

										</div>
										<div class="col-6">
											<form action="<?php  echo base_url('Account/credit_teller')?>" method="post">
												<input hidden type="text" name="vault_account" id="vault_account" value="<?php echo $v->account_number?>">

												<div class="form-group">
												<label for="">Teller</label>
												<select name="account" id="misheck" class="form-control" required>
													<option value="">--select--</option>
													<?php

													foreach ($te as $value){
														?>
														<option value="<?php  echo $value->account_number ?>"><?php echo $value->Firstname." ".$value->Lastname."(-".$value->account_name." ".$value->account_number.")"?></option>
													<?php
													}
													?>
												</select>
												</div>
												<div id="teller_display" style="border: thick solid skyblue; height: 200px; border-radius: 15px; padding: 2em;">
													<h5 style="color: red;">Select teller account  to get started</h5>
												</div>
												<h6>Amount In Kwacha</h6>

												<div class="form-group">
													<label for="" style="color: red;">Total mount  to send to teller </label>
													<input type="text" id="tt" name="amount" class="form-control" placeholder="Enter amount" required>
												</div>

												<input type="submit" name="submit" value="save changes">
											</form>
										</div>
									</div>
						<hr>
						<br>
						<div class="row">
							<div class="col-lg-2"></div>
							<div class="col-lg-10">
								<p>Pending Requests</p>
								<table class="table table-data table-responsive" id="data-table">
									<thead>
									<tr>
										<th>Vault Account</th>
										<th>Teller Account</th>
										<th>Amount</th>
										<th>Created By</th>
										<th>Created date</th>
										<th>status</th>
										<th>Action</th>
									</tr>
									</thead>
									<tbody>
									<?php
									foreach ($requests as $r){
										?>
											<tr>
												<td><?php echo $r->vault_account ?></td>
												<td><?php echo $r->teller_account ?></td>
												<td><?php echo $r->amount ?></td>
												<td><?php echo $r->Firstname." ". $r->Lastname ?></td>
												<td><?php echo $r->sd ?></td>
												<td><?php echo $r->status ?></td>
												<td><a class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this? this cannot be recovered')" href="<?php echo base_url('Cashier_vault_pends/delete/').$r->cvpid?>"><i class="fa fa-trash"></i>Remove</a></td>
											</tr>
									<?php
									}
									?>

									</tbody>
								</table>
							</div>
						</div>
		</div>
	</div>
</div>


