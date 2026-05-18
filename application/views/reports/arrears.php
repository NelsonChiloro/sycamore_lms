<?php
$users = get_all('employees');
$products = get_all_loans('loan');
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All arrears report</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All loans  arrears report</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<form action="<?php echo base_url('reports/arrears') ?>" method="get">
				<fieldset>
					<legend>Report filter</legend>
					<div id="controlgroup">
						Loan :

						<select name="loan" class="sselect">
							<option value="All">All loans</option>
							<?php
							foreach ($products as $product){
								?>
								<option value="<?php echo $product->loan_id;?>" <?php if($product->loan_id==$this->input->get('loan')){echo 'selected';}  ?>><?php echo $product->loan_number;?></option>
								<?php
							}

							?>
						</select>
						<select name="by_date" class="sselect" id="by_date">
							<option value="Custom">Custom</option>
							<option value="one_day" <?php  if($this->input->get('by_date')=="one_day"){echo "selected";} ?>>One day</option>
							<option value="three_days" <?php  if($this->input->get('by_date')=="three_days"){echo "selected";} ?>>Three days</option>
							<option value="week" <?php  if($this->input->get('by_date')=="week"){echo "selected";} ?>>One week</option>
							<option value="month" <?php  if($this->input->get('by_date')=="month"){echo "selected";} ?>>One month</option>
							<option value="2month" <?php  if($this->input->get('by_date')=="2month"){echo "selected";} ?>>Two months</option>
							<option value="3month" <?php  if($this->input->get('by_date')=="3month"){echo "selected";} ?>>Three months</option>
						</select>
                        Officer:
                        <select name="idofficer">
                            <option value="All">All Officers</option>
                            <?php
                            foreach ($users as $user){
                                ?>
                                <option value="<?php echo $user->id;?>" <?php if($user->id==$this->input->get('id')){echo 'selected';}  ?>><?php echo $user->Firstname." ".$user->Lastname;?></option>
                                <?php
                            }

                            ?>
                            <



                        </select>
						Date from:<input type="text"   class="dpicker" name="from" value="<?php  echo $this->input->get('from')?>" >
						Date to:<input type="text"  class="dpicker" name="to" value="<?php  echo $this->input->get('to')?>" >
						<button type="submit" name="search" value="filter">Filter</button>
<!--						<button type="submit" name="search" value="pdf"><i class="fa fa-file-pdf text-danger"></i></button>-->
<!--						<button type="submit" name="search" value="excel"><i class="fa fa-file-excel text-success"></i></button>-->
					</div>
				</fieldset>
			</form>
			<hr>
			<p>Search results</p>
			<table class="table tab-content" id="data-table-arrears">
				<thead>
				<tr>
					<th>#</th>
					<th>Loan Customer</th>
					<th>Loan Number</th>
					<th>Loan product</th>
					<th>Check Date</th>
					<th>Amount Due</th>
					<th>Payment number</th>
					<th>Officer</th>
					<th>Action</th>

				</tr>
				</thead>
				<tbody>
				<?php
				$n = 1;
$totals =0;
				foreach ($loan_data as $loan)
				{
					$totals +=$loan->amount;
					if($loan->customer_type=='group'){
						$group = $this->Groups_model->get_by_id($loan->loan_customer);

						$customer_name = $group->group_name.'('.$group->group_code.')';
						$preview_url = "Customer_groups/members/";
					}elseif($loan->customer_type=='individual'){
						$indi = $this->Individual_customers_model->get_by_id($loan->loan_customer);
						$customer_name = $indi->Firstname.' '.$indi->Lastname;
						$preview_url = "Individual_customers/view/";
					}
					?>
					<tr>

						<td><?php echo $n ?></td>
						<td><a href="<?php echo base_url($preview_url).$loan->loan_customer?>""><?php echo $customer_name?></a></td>

						<td><a href="<?php echo base_url('loan/view/').$loan->loan_id?>"><?php echo $loan->loan_number ?></a></td>
						<td><?php echo $loan->product_name ?></td>
						<td><?php echo $loan->payment_schedule ?></td>
<!--						<td>MK--><?php //echo number_format($loan->loan_principal,2) ?><!--</td>-->
						<td><?php echo $loan->amount ?></td>
						<td><?php echo $loan->payment_number ?></td>
						<td><?php echo $loan->efname." ".$loan->elname ?></td>

						<td><a href="<?php echo base_url('loan/view/').$loan->loan_id?>">View</a></td>

					</tr>
					<?php
					$n ++;
				}
				?>
				</tbody>
				<tfoot>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th>MK<?php echo number_format($totals,2)?></th>
				<th></th>
				<th></th>
				</tfoot>
			</table>
		</div>
	</div>
</div>
