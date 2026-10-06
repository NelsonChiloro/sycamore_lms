
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Journal and transfers </h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">cash account configure</a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">

						<div class="row">
							Generate Batch Number : <input type="text" readonly size="20" style="border: thick black dotted; color: red; font-size: 20px;" id="batch_id" > <button type="button" class="btn btn-info" id="batch_gen">Generate</button>
						</div>
						<div class="row">
							<div class="col-lg-3">
								<h1>DR</h1>
								<fieldset>
									<legend>Search:</legend>
									Account #:    &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;
									<input class="form-control" type="text" id="searchp" placeholder="Search account number" size="7">
									<br>

									Chart of account #:<input readonly type="text" placeholder="" class="form-control" id="chartof">
									<br>
									Account No:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input id="dacn" name="account" readonly type="text" placeholder="" class="form-control">
								</fieldset>

							</div>
							<div class="col-lg-3 border-right">
								<br>
								<h5>Account Details</h5>
								<hr>


								<table>
									<tr>
										<td>Account Name :</td><td><input type="text" id="account_name" readonly></td>
									</tr>
									<tr>
										<td>Account balance :</td><td><input type="text" id="account_balance" readonly></td>
									</tr>
									<tr>
										<td>Account Currency :</td><td><input type="text" id="account_currency" readonly></td>
									</tr>
									<tr>
										<td>Opening Date :</td><td><input type="text" id="account_date" readonly></td>
									</tr>

									<tr>
										<td>Account Status :</td><td><input type="text" id="account_status" readonly></td>
									</tr>
									<tr>
										<td>Amount :</td><td><input type="text" id="amount_id" placeholder="Type amount" style="border: thick red solid"></td>
									</tr><tr>
										<td>Desc :</td><td><textarea name="" id="desc_id" cols="20"
																	 rows="5"></textarea></td>
									</tr>
								</table>



							</div>

							<div class="col-lg-3">
								<h1>CR</h1>
								<fieldset>
									<legend>Search:</legend>
									Account #:    &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;
									<input class="form-control" type="text" id="searchto" placeholder="Search account number" size="7">
									<br>

									Chart of account #:<input readonly type="text" placeholder="" class="form-control" id="chartofto">
									<br>
									Account No:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input id="dacnto" name="account" readonly type="text" placeholder="" class="form-control">
								</fieldset>

							</div>
							<div class="col-lg-3">
								<br>
								<h5>Account Details</h5>
								<hr>


								<table>
									<tr>
										<td>Account Name :</td><td><input type="text" id="account_nameto" readonly></td>
									</tr>
									<tr>
										<td>Account balance :</td><td><input type="text" id="account_balanceto" readonly></td>
									</tr>
									<tr>
										<td>Account Currency :</td><td><input type="text" id="account_currencyto" readonly></td>
									</tr>
									<tr>
										<td>Opening Date :</td><td><input type="text" id="account_dateto" readonly></td>
									</tr>

									<tr>
										<td>Account Status :</td><td><input type="text" id="account_statusto" readonly></td>
									</tr>
								</table>



							</div>

						</div>
						<div class="row">
							<div class="col-lg-12">
								<form action="" method="post" id="journal_form">
									<h4>Journal Batch List</h4><button type="button" style="background-color: yellow;" id="add_to_list_btn">Add to list</button>&nbsp;&nbsp;&nbsp;Finalize <input type="checkbox" required> &nbsp;&nbsp;&nbsp;<button type="submit" onclick="return confirm('Are you sure you want to submit?')">Save Journal batch</button>
									<div class="" style="overflow-x: scroll; overflow-y: scroll;   background-color: gainsboro;
            height: 300px;
            width: 100%;
            border-radius: 5px;" >

										<table class="table table-bordered" id="officers-table">
											<thead class="bg-dark text-white-50">
											<th>Batch No</th>
											<th>DR</th>
											<th>CR</th>
											<th>Amount</th>
											<th>Description</th>
											<th>Action</th>
											</thead>
											<tbody id="j_list_data">

											</tbody>
										</table>


									</div>
								</form>
							</div>
						</div>
		</div>
	</div>
</div>

