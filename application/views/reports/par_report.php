<?php
$users = get_all('employees');
$products = get_all('loan_products');
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All par report</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All loans par report</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<form action="<?php echo base_url('reports/parfilter') ?>" method="get">
				<fieldset>
					<legend>Report filter</legend>

					<div id="controlgroup">
                        Product: <select name="productid" id="" class="select2">
                            <option value="All">All Products</option>
                            <?php

                            foreach ($products as $product){
                                ?>
                                <option value="<?php  echo $product->loan_product_id; ?>"><?php echo $product->product_name; ?></option>
                                <?php
                            }
                            ?>
                        </select>

						Officer:
						<select name="id">
							<option value="">All Officers</option>
							<?php
							foreach ($users as $user){
								?>
								<option value="<?php echo $user->id;?>" <?php if($user->id==$this->input->get('id')){echo 'selected';}  ?>><?php echo $user->Firstname." ".$user->Lastname;?></option>
								<?php
							}

							?>
                            <



						</select>
						<button type="submit" name="search" value="filter">Filter</button>
                    </div>
				</fieldset>
			</form>
			<hr>
			<div  class="frm_inputs" style="overflow-x:auto;">
				<div id="employee_table">
					<table id="resulta"  class="table" cellspacing="1">
						<thead>
						<tr>
							<th>Loan #</th>
							<th>Customer</th>
							<th>Officer</th>

							<th>Total Principal in Arreas</th>


							<th>Aged 0-7 days</th>
							<th>Aged 8-30 days</th>
							<th>Aged 31-60 days</th>
							<th>Aged 61-90 days</th>
							<th>Aged 91-120 days</th>
							<th>Aged 121-180 days</th>
							<th>Aged 181-366 days</th>
							<th>Aged 367+ days</th>
						</tr>
						</thead>
						<tbody>
						<?php
                        $Totalrincipalbalance=0;
						$tarrears=0;
						$totalprincipal=0;
						$tzerotoseven=0;
						$morethanseven=0;
						$morethanthirty=0;
						$morethansixty=0;
						$morethanninety=0;
						$morethanonetwenty=0;
						$morethanoneeighty=0;
						$morethanthreesixty=0;
						$summary = $this->Loan_model->get_summaryu($this->session->userdata('officerid'),$this->session->userdata('productid'));
						$t_payment = 0; $t_principal = 0; $t_interest = 0; $t_balance = 0; $t_amount=0;$u_payment=0;$uga=0;
						?>
						<?php if($summary) : ?>
							<?php foreach ($summary as $row) :?>
								<?php


										//$totalprincipal=$t_principal + $row->t_principal;
										//$tarrears=$u_payment + $row->u_payment;
                                        if($row->customer_type=='group'){
                                            $group = $this->Groups_model->get_by_id($row->loan_customer);

                                            $customer_name = $group->group_name.'('.$group->group_code.')';
                                            $preview_url = "Customer_groups/members/";
                                        }elseif($row->customer_type=='individual'){
                                            $indi = $this->Individual_customers_model->get_by_id($row->loan_customer);
                                            $customer_name = $indi->Firstname.' '.$indi->Lastname;
                                            $preview_url = "Individual_customers/view/";
                                        }
										?>

										<tr>
											<td<?php  ?>><a href="<?php echo base_url();?>loan/view/<?php echo $row->loan_id ;?>"><?php echo $row->lnumber ;?></a></td>
                                            <td><a href="<?php echo base_url($preview_url).$row->loan_customer?>""><?php echo $customer_name?></a></td>
                                            <td><a href="<?php  echo base_url().'Employees/read/'.$row->loan_added_by;?>"><?php echo $row->Firstname.' '.$row->Lastname ?></a></td>

											<td><?php

                                                $totalamountArreas=0;
                                                $arrreas= get_all_over_due_payments($row->loan_id);
                                                $totalamountArreas=$arrreas->total_arrears;
                                                echo number_format($totalamountArreas, 2, '.', ',') ;$tarrears +=$totalamountArreas?></td>

                                           <td><?php

												$u=$uga + $totalamountArreas;
												$date=date("Y-m-d H:i:s");
												$dateOne = new DateTime($date);
												$dateTwo = new DateTime($row->max_d);

												$diff = $dateTwo->diff($dateOne)->format("%a");

												if($diff>=0 && $diff<=7){
													$tzerotoseven+=$u;
													echo number_format( $u,2);
												}else{
													echo '0';
												}
												?></td>
											<td><?php  if($diff>=8 && $diff<=30){
													$morethanseven +=$u;
													echo number_format( $u,2);
												}else{
													echo '0';
												} ?></td>
											<td><?php  if($diff>=31 && $diff<=60){
													$morethanthirty +=$u;
													echo number_format( $u,2);
												}else{
													echo '0';
												} ?></td>
											<td><?php  if($diff>=61 && $diff<=90){
													$morethansixty +=$u;
													echo number_format( $u,2);
												}else{
													echo '0';
												} ?></td>
											<td><?php  if($diff>=91 && $diff<=120){
													$morethanninety +=$u;
													echo number_format( $u,2);
												}else{
													echo '0';
												}  ?></td>
											<td><?php  if($diff>=121 && $diff<=180){
													$morethanonetwenty +=$u;
													echo number_format( $u,2);
												}else{
													echo '0';
												}  ?></td>
											<td><?php  if($diff>=181 && $diff<=366){
													$morethanoneeighty +=$u;
													echo number_format( $u,2);
												}else{
													echo '0';
												}  ?></td>
											<td><?php  if($diff>=367){
													$morethanthreesixty += $u;
													echo number_format( $u,2);
												}else{
													echo '0';
												}  ?></td>
										</tr>

							<?php endforeach; ?>
						<?php endif; ?>
						<?php
						$p=0;
						$p1=0;
						$totaldisb=0;
						$gt=$this->Loan_model->sum_total_par();
						$gt2=$this->Loan_model->sum_total2($this->session->userdata('officerid'));
						foreach ($gt as $tamt){
							$totaldisb +=$tamt->lm;
						}
						foreach ($gt2 as $tamt2){
							if($tamt2->paid_amount >=$tamt2->principal){
//       $p = $tamt2->principal;
								$p=0;
								$p1 +=$p;

							}elseif($tamt2->paid_amount < $tamt2->principal){
								$p = $tamt2->principal-$tamt2->paid_amount;
								$p1 +=$p;

							}

						}

						?>
						</tbody>
						<tfoot>
						<tr>
							<td>TOTAL</td>
							<td>-</td>
							<td>-</td>
							<td><?php echo "MK" .number_format($tarrears,2); ?></td>
                            

							<td>-</td>
							<td><?php echo "MK" .number_format($tzerotoseven,2); ?></td>
							<td><?php echo "MK" .number_format($morethanseven,2); ?></td>
							<td><?php echo "MK" .number_format($morethanthirty,2); ?></td>
							<td><?php echo "MK" .number_format($morethansixty,2); ?></td>
							<td><?php echo "MK" .number_format($morethanninety,2); ?></td>
							<td><?php echo "MK" .number_format($morethanonetwenty,2); ?></td>
							<td><?php echo "MK" .number_format($morethanoneeighty,2); ?></td>
							<td><?php echo "MK" .number_format($morethanthreesixty,2); ?></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>TOTAL PORTFOLIO </td>
							<td> </td>
							<td></td>
                            <td><?php
if(empty($this->session->userdata('officerid') )AND empty($this->session->userdata('productid') ) ) {


    $totalunpaid = get_all_total_unpayments();
    echo "MK" . number_format($totalunpaid->total_not_paid, 2);
    $p1 = $totalunpaid->total_not_paid;
}
                                elseif($this->session->userdata('officerid')){
                                     $totalunpaid=get_all_total_unpayments_by_officer($this->session->userdata('officerid'));
                                echo "MK" .number_format($totalunpaid->total_not_paid,2);
                                $p1=$totalunpaid->total_not_paid ;

                                }
                                else{
                                    $totalunpaid=get_all_total_unpayments_by_product($this->session->userdata('productid'));
                                    echo "MK" .number_format($totalunpaid->total_not_paid,2);
                                    $p1=$totalunpaid->total_not_paid ;
                                }?></td>


							<td></td>
							<td> </td>
							<td><?php

                                 ?></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>PORTFOLIO AT RISK</td>
							<td></td>
							<td></td>
							<td><?php echo round(($tarrears/$totalunpaid->total_not_paid)*100,2).'%';




                                ?></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>MORE THAN 0 DAYS</td>
							<td></td>
							<td></td>
							<td><?php echo round(($totalprincipal/$p1)*100,2).'%'; ?></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>MORE THAN 7 DAYS</td>
							<td></td>
							<td></td>
							<td><?php echo round(($morethanseven/$p1)*100,2).'%'; ?></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>MORE THAN 30 DAYS</td>
							<td></td>
							<td></td>
							<td><?php echo round(($morethanthirty/$p1)*100,2).'%'; ?></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>MORE THAN 60 DAYS</td>
							<td></td>
							<td></td>
							<td><?php echo round(($morethansixty/$p1)*100,2).'%'; ?></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>MORE THAN 90 DAYS</td>
							<td></td>
							<td></td>
							<td><?php echo round(($morethanninety/$p1)*100,2).'%'; ?></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>MORE THAN 120 DAYS</td>
							<td></td>
							<td></td>
							<td><?php echo round(($morethanonetwenty/$p1)*100,2).'%'; ?></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>MORE THAN 180 DAYS</td>
							<td></td>
							<td></td>
							<td><?php echo round(($morethanoneeighty/$p1)*100,2).'%'; ?></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>MORE THAN 366 DAYS</td>
							<td></td>
							<td></td>
							<td><?php echo round(($morethanthreesixty/$p1)*100,2).'%'; ?></td>

							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
