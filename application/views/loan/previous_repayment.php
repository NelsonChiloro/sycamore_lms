<?php
$next_payment_details = $this->Payement_schedules_model->get_next($next_payment_id,$loan_id);
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Loan view</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Loan Details</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #24C16B solid;border-radius: 14px;">
			<div class="row">
				<div class="col-lg-3 border-right">
					<h2>Loan Info</h2>
					<hr>
					<table>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Loan #</td>
							<td><?php echo $loan_number?></td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Loan Type</td>
							<td><?php echo $loan_product?></td>
						</tr>
						<tr >
							<td style="text-align: right;padding-right: 10px;">Loan Customer</td>
							<td><a href="<?php echo base_url($preview_url).$customer_id?>"><?php echo $loan_customer?></a></td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">customer Type</td>
							<td><?php echo $customer_type?></td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Loan Status</td>
							<td><?php echo $loan_status?></td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Loan Principal</td>
							<td>MK<?php echo number_format($loan_principal,2)?></td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Total interest</td>
							<td>MK<?php echo number_format($loan_interest_amount,2)?></td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Total Admin fees</td>
							<td>MK<?php echo number_format($admin_fees_amount,2)?></td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Total Loan cover</td>
							<td>MK<?php echo number_format($loan_cover_amount,2)?></td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Total loan amount</td>
							<td>MK <?php echo number_format($loan_amount_total,2)?></td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Payments Made</td>
							<td>MK
								<?php
								$total_p = 0;
								foreach ($payments as $pp){
									if($pp->status == "PAID"){
										$total_p +=$pp->amount;
									}

								}
								echo number_format($total_p,2);
								?>

							</td>
						</tr>
						<tr>
							<td style="text-align: right;padding-right: 10px;">Remaining Balance
							</td>
							<td>MK

								<?php
								$total_b = 0;
								foreach ($payments as $ppp){
									if($ppp->status == "NOT PAID"){
										$total_b +=$pp->amount;
									}

								}
								echo number_format($total_b,2);
								?>
							</td>
						</tr>
					</table>
					<br>
					<h2>Scheduled Payment</h2>
					<hr>
					<?php
					if(empty($next_payment_details)){
						echo "No more payments to make";
					}else{
						?>
						<table>
							<tr>
								<td style="text-align: right;padding-right: 10px;" width="150">Payment #</td>
								<td><?php echo $next_payment_id ?></td>
							</tr>
							<tr>
								<td style="text-align: right;padding-right: 10px;">Pay principal amt</td>
								<td>MK <?php echo number_format($next_payment_details->principal,2)?></td>
							</tr>
							<tr >
								<td style="text-align: right;padding-right: 10px;">Pay interest amt</td>
								<td><a href="#">MK <?php echo number_format($next_payment_details->interest,2)?></a></td>
							</tr>
							<tr >
								<td style="text-align: right;padding-right: 10px;">Admin fee amt</td>
								<td><a href="#">MK <?php echo number_format($next_payment_details->padmin_fee,2)?></a></td>
							</tr>
							<tr >
								<td style="text-align: right;padding-right: 10px;">Loan cover amt</td>
								<td><a href="#">MK <?php echo number_format($next_payment_details->ploan_cover,2)?></a></td>
							</tr>
							<tr>
								<td style="text-align: right;padding-right: 10px;">Payment amt</td>
								<td>MK <?php echo number_format($next_payment_details->amount,2)?></td>
							</tr>
							<tr>
								<td style="text-align: right;padding-right: 10px;">Due date</td>
								<td><?php echo $next_payment_details->payment_schedule;?></td>
							</tr>
							<tr>
								<td style="text-align: right;padding-right: 10px;">Payment status</td>
								<td>

									<?php

									$oved = '';
									if($next_payment_details->payment_schedule < date('Y-m-d')   AND $next_payment_details->status == 'NOT PAID') {
										$oved = ' | OVER DUE';
									} elseif($next_payment_details->status=='PAID') {

									} elseif($next_payment_details->payment_schedule == date('Y-m-d')  AND $next_payment_details->status == 'NOT PAID') {

										$oved = ' | DUE TODAY';
									}
									?>
									<span style="color:<?php echo $next_payment_details->status=='PAID' ? 'GREEN' : 'RED'?>"><?php echo $next_payment_details->status.$oved; ?></span>
								</td>
							</tr>


						</table>
						<?php
					}
					?>

				</div>
				<div class="col-lg-7 border-right">
					<h2>Overview</h2>
					<hr>
					<div style="overflow: auto"">
					<table style="width: 100%;border-collapse: collapse;">
						<thead>
						<tr style="border: 1px solid black;">
							<th>Payment #</th>
							<th>Check Date</th>
							<th>Principal</th>
							<th>Interest</th>
							<th>Admin fee</th>
							<th>Loan cover</th>
							<th>Pay Amount</th>
							<th>Amount Paid</th>
							<th>Loan Balance</th>
							<th>Status</th>
							<th>Action</th>
						</tr>
						</thead>
						<tbody>
						<?php
						foreach ($payments as $p){
							?>
							<?php
							//change color depending on it's status
							$css = '';
							$xstatus = '';
							if($p->payment_schedule < date('Y-m-d')   AND $p->status == 'NOT PAID') {
								$css = 'class="due"';
								$xstatus = ' | OVER DUE';
							} elseif($p->status=='PAID') {
								$css = 'class="paid"';
							} elseif($p->payment_schedule == date('Y-m-d')  AND $p->status == 'NOT PAID') {
								$css = 'class="due_now"';
								$xstatus = ' | DUE TODAY';
							}
							?>
							<!--							<tr style="border: 1px solid black;background-color: #F3D8D8;">-->
							<tr style="font-weight: <?php echo !empty($xstatus)?'900':'200'; ?>;">
								<td <?php echo $css; ?>><?php  echo $p->payment_number?></td>
								<td <?php echo $css; ?>><?php  echo $p->payment_schedule?></td>

								<td <?php echo $css; ?>>MK<?php  echo number_format($p->principal,2) ?></td>
								<td <?php echo $css; ?>>MK<?php  echo number_format($p->interest,2) ?></td>
								<td <?php echo $css; ?>>MK<?php  echo number_format($p->padmin_fee,2) ?></td>
								<td <?php echo $css; ?>>MK<?php  echo number_format($p->ploan_cover,2) ?></td>
								<td <?php echo $css; ?>>MK<?php  echo number_format($p->amount,2) ?></td>
								<td <?php echo $css; ?>>MK<?php  echo number_format($p->paid_amount,2)?></td>
								<td <?php echo $css; ?>>MK<?php  echo number_format($p->loan_balance,2)?></td>
								<td width="150" <?php echo $css; ?>><span style="color:<?php echo $p->status=='PAID' ? 'GREEN' : 'RED'?>"><?php echo $p->status.$xstatus; if($p->partial_paid=="YES"){echo "-<font color='green'>(Partial paid)</font>";}?></span></td>
								<td <?php echo $css; ?> width="70"><?php if($xstatus !="") { ?> <a href="#" onclick="pay_due('<?php echo $p->loan_id; ?>','<?php echo $p->payment_number; ?>','<?php echo $p->amount; ?>','<?php echo $p->paid_amount; ?>')" class="btn-danger">Pay now</a><?php  } ?></td>
							</tr>
							<?php
						}
						?>


						<!--						<tr style="border: 1px solid black;background-color: #D1EFD1;">-->
						<!--							<td>2</td>-->
						<!--							<td>2021-06-22</td>-->
						<!--							<td>MK 20,9393</td>-->
						<!--							<td>MK 20,9393</td>-->
						<!--							<td>MK 20,9393</td>-->
						<!--							<td>ACTIVE</td>-->
						<!--							<td><a href="" class="btn btn-sm btn-danger">Pay</a></td>-->
						<!--						</tr>-->
						</tbody>
					</table>
				</div>
			</div>
			<div class="col-lg-2">
				<a href="<?php echo base_url('loan/report/').$loan_id ?>" style="color: red;"><i class="fa fa-file-pdf fa-2x"></i>Report</a>
				<hr>
				<?php
				if(empty($next_payment_details)){
					echo "No more payments to make";
				}else{
					?>
					<h4>Pay</h4>
					<?php if($p->loan_status=='ACTIVE'){
						?>


						<?php
						if($oved !=""){

						}else{
							?>
							<a href="#" class="btn btn-sm btn-success" onclick="pay_current()">To current schedule</a>
							|

							<a href="#" onclick="advance_payment()" class="btn btn-small btn-warning">Advance payment</a>|

							<a href="#" onclick="finish_payment()" class="btn btn-small btn-danger">Finish payment</a>|

							<?php
						}

					}else{

					}
					?>
					<?php

				}
				?>

			</div>
		</div>
	</div>
</div>

</div>

<div aria-hidden="true" class="onboarding-modal modal fade" id="finish_payment_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<span></span><button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title" >Finish Payment</h4>
				<p style="color: red;">Are you sure you want to pay the current payment and the balance to finish  loan ?</p>
				<table>
					<tr>
						<td style="text-align: right;padding-right: 10px;" width="150">Loan #</td>
						<td><?php echo $loan_number  ?></td>
					</tr>
					<tr>
						<td style="text-align: right;padding-right: 10px;" width="150">Payment #</td>
						<td><?php echo $next_payment_id ?></td>
					</tr>

					<tr>
						<td style="text-align: right;padding-right: 10px;">Payment amt</td>
						<td>MK <?php echo number_format($next_payment_details->amount,2)?></td>
					</tr>


				</table>
				<form action="<?php echo base_url('loan/finish_loan')?>" class="form-row" method="POST" >

					<div class="form-group col-lg-12" style="padding: 5em;">
						<label for="date">To pay amount  </label>
						<input type="text" name="loan_id" value="<?php echo $loan_id?>" hidden>
						<input type="text" name="payment_number" value="<?php echo $next_payment_id ?>" hidden>


						<input style="border: thin red solid;" type="text" class="form-control" name="amount"  value="<?php echo (($next_payment_details->amount)-($next_payment_details->paid_amount))+$next_payment_details->loan_balance?>" placeholder="Enter pay amount" readonly required />
						<br/>


						<input type="text" id="id_front1"  name="pay_proof" value="Null" hidden required>

					</div>
					<button class="btn btn-sm btn-block btn-danger" type="submit">Submit Payment</button>
				</form>
			</div>
		</div>
	</div>
</div>
<div aria-hidden="true" class="onboarding-modal modal fade" id="payment_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<span></span><button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title" >Loan Payments</h4>
				<p style="color: red;">Are you sure you want to pay the current payment of loan ?</p>
				<table>
					<tr>
						<td style="text-align: right;padding-right: 10px;" width="150">Loan #</td>
						<td><?php echo $loan_number  ?></td>
					</tr>
					<tr>
						<td style="text-align: right;padding-right: 10px;" width="150">Payment #</td>
						<td><?php echo $next_payment_id ?></td>
					</tr>

					<tr>
						<td style="text-align: right;padding-right: 10px;">Payment amt</td>
						<td>MK <?php echo number_format($next_payment_details->amount,2)?></td>
					</tr>


				</table>
				<form action="<?php echo base_url('loan/pay_loan')?>" class="form-row" method="POST" >

					<div class="form-group col-lg-12" style="padding: 5em;">
						<label for="date">To pay amount  </label>
						<input type="text" name="loan_id" value="<?php echo $loan_id?>" hidden>
						<input type="text" name="payment_number" value="<?php echo $next_payment_id ?>" hidden>


						<input style="border: thin red solid;" type="text" class="form-control" name="amount"  value="<?php echo ($next_payment_details->amount)-($next_payment_details->paid_amount)?>" placeholder="Enter pay amount" readonly required />
						<br/>


						<input type="text" id="id_front1"  name="pay_proof" value="Null" hidden required>

					</div>
					<button class="btn btn-sm btn-block btn-danger" type="submit">Submit Payment</button>
				</form>
			</div>
		</div>
	</div>
</div>
<div aria-hidden="true" class="onboarding-modal modal fade" id="advance_payment_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<span></span><button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title" >Loan advance payments</h4>
				<p style="color: red;">Are you sure you want to make advance payments ?</p>
				<form action="<?php echo base_url('loan/pay_advance')?>" class="form-row" method="POST" >
					<table style="width: 100%;border-collapse: collapse;">
						<thead>
						<tr style="border: 1px solid black;">
							<th>Payment #</th>
							<th>Check Date</th>
							<th>Amount</th>
							<th>Amount Paid</th>
							<th>Loan Balance</th>
							<th>Status</th>
							<th>Action</th>
						</tr>
						</thead>
						<tbody>
						<?php
						foreach ($payments as $p){
							?>
							<?php
							//change color depending on it's status
							$css = '';
							$xstatus = '';
							if($p->payment_schedule < date('Y-m-d')   AND $p->status == 'NOT PAID') {
								$css = ' class="due"';
								$xstatus = ' | OVER DUE';
							} elseif($p->status=='PAID') {
								$css = 'class="paid"';
							} elseif($p->payment_schedule == date('Y-m-d')  AND $p->status == 'NOT PAID') {
								$css = ' class="due_now"';
								$xstatus = ' | DUE TODAY';
							}
							?>
							<!--							<tr style="border: 1px solid black;background-color: #F3D8D8;">-->
							<tr style="font-weight: <?php echo !empty($xstatus)?'900':'200'; ?>;">
								<td <?php echo $css; ?>><?php  echo $p->payment_number?></td>
								<td <?php echo $css; ?>><?php  echo $p->payment_schedule?></td>
								<td <?php echo $css; ?>>MK<?php  echo number_format($p->amount,2) ?></td>
								<td <?php echo $css; ?>>MK<?php  echo number_format($p->paid_amount,2)?></td>
								<td <?php echo $css; ?>>MK<?php  echo number_format($p->loan_balance,2)?></td>
								<td width="150" <?php echo $css; ?>><span style="color:<?php echo $p->status=='PAID' ? 'GREEN' : 'RED'?>"><?php echo $p->status.$xstatus; if($p->partial_paid=="YES"){echo "-<font color='green'>(Partial paid)</font>";}?></span></td>
								<td <?php echo $css; ?> width="70"><?php if($p->status == 'NOT PAID') { ?>  <input type="checkbox" name="payment_number[]" value="<?php echo $p->payment_number  ?>" class="check-cls"><?php  } ?></td>
							</tr>
							<?php
						}
						?>



						</tbody>
					</table>

					<input type="text" name="loan_id" value="<?php echo $loan_id?>" hidden>

					<input style="border: thin red solid;" type="text" class="form-control" name="amount"  value="<?php echo $next_payment_details->amount?>" hidden />

					<div class="form-group col-lg-12" style="padding: 2em;">
			<span class="tool-tip" data-toggle="tooltip" data-placement="top" title="You need to choose at least one option">
    <button class="btn btn-sm  btn-danger submit-btn" style="border: red solid thin;" disabled="disabled" type="submit">Submit Payments</button>
			</span>

					</div>

				</form>

			</div>
		</div>
	</div>
</div>

<div aria-hidden="true" class="onboarding-modal modal fade" id="late_payment_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<span></span><button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title" >Late Payment Fee</h4>
				<p style="color: red;">Are you sure you want to pay  loan  as below details?</p>
				<table>
					<tr>
						<td style="text-align: right;padding-right: 10px;" width="150">Loan #</td>
						<td><?php echo $loan_number ?></td>
					</tr>
					<tr>
						<td style="text-align: right;padding-right: 10px;" width="150">Payment #</td>
						<td id="spn"></td>
					</tr>

					<tr>
						<td style="text-align: right;padding-right: 10px;">Payment amt</td>
						<td id="slm"></td>
					</tr>
					<tr>
						<td style="text-align: right;padding-right: 10px;">Payment Charge</td>
						<td id="spc"></td>
					</tr>


				</table>
				<form action="<?php echo base_url('loan/pay_late_loan')?>" class="form-row" method="POST" >

					<div class="form-group col-lg-12" style="padding: 5em;">
						<label for="date">To pay amount  </label>
						<input type="text" name="loan_id" value="<?php echo $loan_id?>" hidden>
						<input type="text" name="payment_number" id="pn" hidden required>
						<label for="lm">Payment amount</label>
						<input style="border: thin red solid;" type="text" class="form-control" id="lm" name="amount"  readonly required />
						<label for="late_charge_amount">Late payment charge</label>
						<input style="border: thin red solid;" type="text" class="form-control" id="late_charge_amount" name="lamount"  readonly required />
					</div>
					<button class="btn btn-sm btn-block btn-danger" type="submit">Submit Payment</button>
				</form>
			</div>
		</div>
	</div>
</div>
