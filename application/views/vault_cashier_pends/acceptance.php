<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Cash operation</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Vault to cashier approval</a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
								<p>Pending  approval Requests</p>
								<table class="table table-data" id="data-table">
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
									foreach ($data as $r){
										?>
										<tr>
											<td><?php echo $r->vault_account ?></td>
											<td><?php echo $r->teller_account ?></td>
											<td><?php echo $r->amount ?></td>
											<td><?php echo $r->Firstname." ". $r->Lastname ?></td>
											<td><?php echo $r->sd ?></td>
											<td><?php echo $r->status ?></td>
											<td><a class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to accept this?')" href="<?php echo base_url('Account/accept_credit_teller/').$r->cvpid?>"><i class="fa fa-check"></i>Accept</a><a class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want reject this')" href="<?php echo base_url('Vault_cashier_pends/reject/').$r->cvpid?>"><i class="fa fa-recycle"></i>Reject</a></td>
										</tr>
										<?php
									}
									?>

									</tbody>
								</table>
		</div>
	</div>
</div>
