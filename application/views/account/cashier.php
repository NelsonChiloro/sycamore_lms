
<style>
	.imgy {
		width:150px;
		height: 150px;
		margin:30px;
		padding: 20px;
	}
	input[type="radio"]{
		-webkit-appearance:none;
	}
	.labell{
		height: 150px;
		width: 200px;
		border:6px solid #18f98d;
		position: relative;
		margin: auto;
		border-radius: 10px;
		color: #18f98d;
		transition: 0.5s;
	}
	.imgg{
		font-size: 80px;
		position: absolute;
		top:50%;
		left: 50%;
		transform: translate(-50%,-80%);
	}
	label>span{
		font-size: 25px;
		position: absolute;
		top:50%;
		left: 50%;
		transform: translate(-50%,80%);
		font-family: "Poppins",sans-serif;
		font-weight: 500;
	}
	input[type="radio"]:checked + label{
		background-color: #18f98d;
		color: white;
		box-shadow: 0 15px 45px rgba(24,249,141,0.2);
	}
</style>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Savings cash operations</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Savings </a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<div class="row">
				<div class="col">
					<div class="card">
						<?php
						$get_account = $this->Tellering_model->get_teller_account($this->session->userdata('user_id'));

						if(empty($get_account)){
							echo "<font color='red' size='20px'>Sorry you are not legible to do Teller Transactions</font>";
						}else{

						?>
						<!-- Card header -->
						<div class="card-header border-0">
							<div style="padding: 1em; margin-top: 10px; background-color: darkgray;margin-bottom: 50px; display: none;" id="panelp">
								No Transaction
							</div>
							<h3 class="mb-0 float-left">MAKE DEPOSIT/WITHDRAW</h3><h5 class="mb-0 float-right btn btn-info" id="vtrans"><i class="fa fa-eye"></i>View transactions</h5><h5 class="mb-0 float-right btn btn-success" style="display: none;" id="htrans"><i class="anticon anticon-close"></i>hide transactions</h5>
							<br><br>
							<input type="text" hidden value="<?php echo $get_account->account; ?>" id="myacc">
							Your-account-Number: <i id="teller_account" style="font-weight: bolder; color: red;"><?php echo $get_account->account; ?></i> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;  Drawer Balance :
							<input id="drawer_balance" type="text" value="MK<?php echo number_format($get_account->balance,2); ?>" style="background-color: #C1D797;">
						</div>
						<!-- Light table -->

						<form action="" id="transaction_deposit_form" >
							<div class="row" style="padding: 1em;">

								<div class="col-lg-3">

									<fieldset>
										<legend>Search:</legend>
										Account #:    &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;
										<input class="form-control" type="text" id="search_input" placeholder="Search account number" size="7">

										Account type:<input readonly type="text" placeholder="" class="form-control" id="chartof">
										<br>
										Account No:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input id="dacn" name="account"  type="text" placeholder="" class="form-control">
									</fieldset>

								</div>
								<div class="col-lg-4">
									<br>
									<h5>Account Details</h5>
									<hr>


									<table>
										<tr>
											<td>Account Name :</td><td><input type="text" id="account_name" readonly></td>
										</tr>

										<tr>
											<td>Balance : MWK</td><td><input type="text" id="account_balance" readonly></td>
										</tr>

										<tr>
											<td>Opening Date :</td><td><input type="text" id="account_date" readonly></td>
										</tr>

										<tr>
											<td>Account Status :</td><td><input type="text" id="account_status" readonly></td>
										</tr>
									</table>



								</div>
								<div class="col-lg-4">
									<h5>Mode</h5>
									<hr>
										<div style="padding: 1em; border: thin red solid; border-radius: 12px;">
										<input type="radio" name="transaction_mode" id="card" value="deposit">
										<label class="labell" for="card">
											<img class="imgg imgy" src='<?php echo base_url("uploads/recieved.png")?>' />
											<span>Deposit</span>
										</label>
										<input type="radio" name="transaction_mode" id="cash" value="withdraw">
										<label class="labell" for="cash">
											<img class="imgg imgy" src='<?php echo base_url("uploads/send-money.png")?>' />
											<span>Withdraw</span>
										</label>


									</div>
									<h6> Amount & Date</h6>
									<div class="pages" id="cashp">

										Date:<input type="date" name="dateof" required>	Amt MK:<input type="text"  name="amount" id="tt" size="10" >&nbsp;<button style="background-color:light-blue" class="savec" id="svbutton" type="submit">Commit</button>

									</div>

								</div>


							</div>
						</form>
						<hr>
						<div class="row" style="padding: 1em;">
							<hr>
							<div class="col-lg-3">Passport photo<div id="photoid"><img src="<?php echo base_url('uploads/') ?>profile.jpg" style="border: thick solid coral; border-radius: 15px;" height="150" width="150"></div></div>
							<div class="col-lg-3">Signature<div id="sigid" ><img src="<?php echo base_url('uploads/') ?>finger.jpg" style="border: thick solid coral; border-radius: 15px;" height="150" width="150"></div></div>
							<div class="col-lg-3">ID front<div id="idfront"><img src="<?php echo base_url('uploads/') ?>frontid.png" style="border: thick solid coral; border-radius: 15px;" height="150" width="250"></div></div>
							<div class="col-lg-3">ID back<div id="idback"><img src="<?php echo base_url('uploads/') ?>backend.png" style="border: thick solid coral; border-radius: 15px;" height="150" width="250"></div></div>
						</div>
						<!-- Card footer -->
						<div class="card-footer py-4">

						</div>
						<?php
						}
						?>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>


<?php
$account = get_all('account_types');
?>
<div aria-hidden="true" class="onboarding-modal modal fade animated" id="search_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="os-icon os-icon-close"></span></button>
			<div class="onboarding-side-by-side">

				<div class="onboarding-content with-gradient">
					<h4 class="onboarding-title">Search account number</h4>
					<form id="search_account_form" method="post" action="">
						<div class="row">
							<div class="col-sm-3">
								<div class="form-group"><label for="">Acc group</label>
									<select class="form-control" name="agroup" required>
										<option value="">-Select-</option>
										<option value="customer">Individual account</option>
										<option value="group">Group account</option>
										<option value="internal">Internal account</option>

									</select>
								</div>
							</div>
							<div class="col-sm-3">
								<div class="form-group">
									<label for="">Search By</label>
									<select class="form-control" name="search_by" required>

										<option value="">-select-</option>
										<option value="account">Account number</option>
										<option value="name">Name</option>

									</select>
								</div>
							</div>
							<div class="col-sm-4">
								<div class="form-group"><label for="">Enter your search</label><input class="form-control" name="searchcode" placeholder="Search..." ></div>
							</div>
							<div class="col-sm-2">
								<div class="form-group"><label for=""></label><input class="btn btn-info" type="submit" name="submit" value="Search" ></div>
							</div>
						</div>
					</form>
					<div>
						<table class="table table-bordered">
							<thead>
							<tr>
								<th></th>
								<th>Account number</th>
								<th>Account Type</th>
								<th>Account name</th>
							</tr>
							</thead>
							<tbody id="search-r">

							</tbody>

						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="search_modall" tabindex="-1" role="dialog" aria-labelledby="modal-form" aria-hidden="true">
	<div class="modal-dialog  modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h6 class="modal-title" id="modal-title-defaultt">Search Account</h6>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button>
			</div>
			<div class="modal-body p-0" style="border: thick solid blue; border-radius: 15px;">
				<div class="card bg-success border-0 mb-0">
					<div class="card-body px-lg-5 py-lg-5">

						<form method="post" id="search_account_form" class="form-row" >
							<div class="form-group col-lg-6">
								<div class="input-group input-group-merge input-group-alternative">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="ni ni-circle-08"></i></span>
									</div>
									<select name="account_type" id="account_types_field" class="form-control">
										<option value="">-select account type-</option>
										<?php
										foreach ($account as $at){
											?>
											<option value="<?php echo $at->account_type_id?>"><?php echo $at->account_type_name?></option>
										<?php
										}
										?>

									</select>
								</div>
							</div>


							<div class="form-group col-lg-6">
								<div class="input-group input-group-merge input-group-alternative">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="ni ni-zoom-split-in"></i></span>
									</div>
									<input class="form-control" name="keyword" placeholder="Search account number" type="text">
								</div>
							</div>


							<div class="text-center">
								<button type="submit" class="btn btn-primary btn-block my-4" id="sbbtn">Submit search</button>
							</div>
						</form>
						<hr>
						<center>
							<h5>Search results</h5>
							<table class="table">

								<thead>
								<tr>
									<th>Account name</th>
									<th>Account Number</th>
									<th>Action</th>
								</tr>
								</thead>
								<tbody id="searched_data">

								</tbody>
							</table>
						</center>
						<br>

					</div>
				</div>




			</div>

		</div>
	</div>
</div>
