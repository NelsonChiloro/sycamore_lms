<?php
$next_payment_details = $this->Payement_schedules_model->get_next($next_payment_id,$loan_id);
?>
<style>
/* Enhanced scrollable table styles */
.scrollable-table-container {
    overflow-x: auto;
    max-width: 100%;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    background: white;
}

.scrollable-table-container::-webkit-scrollbar {
    height: 8px;
}

.scrollable-table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.scrollable-table-container::-webkit-scrollbar-thumb {
    background: #153505;
    border-radius: 4px;
}

.scrollable-table-container::-webkit-scrollbar-thumb:hover {
    background: #0d2503;
}

.scrollable-table {
    width: 100%;
    min-width: 1200px;
    border-collapse: collapse;
    font-size: 13px;
}

.scrollable-table th {
    background-color: #153505;
    color: white;
    padding: 12px 8px;
    text-align: left;
    font-weight: 600;
    border: 1px solid #0d2503;
    position: sticky;
    top: 0;
    z-index: 10;
}

.scrollable-table td {
    padding: 10px 8px;
    border: 1px solid #ddd;
    white-space: nowrap;
}

.scrollable-table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

.scrollable-table tbody tr:hover {
    background-color: #f0f8f0;
}

/* Status styling */
.scrollable-table .due {
    background-color: #ffebee !important;
    color: #c62828;
    font-weight: bold;
}

.scrollable-table .due_now {
    background-color: #fff3e0 !important;
    color: #ef6c00;
    font-weight: bold;
}

.scrollable-table .paid {
    background-color: #e8f5e8 !important;
    color: #2e7d32;
}
</style>
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
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
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
                            <td><?php echo $loan_product.'('.$product_code.')' ; ?></td>
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
                            <td style="text-align: right;padding-right: 10px;">Loan Officer</td>
                            <td style="color: black;"><?php echo $officer?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Loan Status</td>
                            <td><?php echo $loan_status?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Branch</td>
                            <td><?php
                                 $branchRow = get_branch_for_loan_value($branch);
                                echo $branchRow ? $branchRow->BranchName : 'N/A';

                                ?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Loan Principal</td>
                            <td>MWK<?php echo number_format($loan_principal,2)?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Total interest</td>
                            <td>MWK<?php echo number_format($loan_interest_amount,2)?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Total Admin fees</td>
                            <td>MWK<?php echo number_format($admin_fees_amount,2)?></td>
                        </tr><tr>
                            <td style="text-align: right;padding-right: 10px;">Total Loan cover</td>
                            <td>MWK<?php echo number_format($loan_cover_amount,2)?></td>
                        </tr>
                        <?php
                        if (!isset($payment_balance)) {
                            $payment_balance = $this->Payement_schedules_model->summarize_loan_balances($payments, $loan_amount_total);
                        }
                        ?>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Total loan amount</td>
                            <td>MWK <?php echo number_format($payment_balance->total_loan_amount, 2); ?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Payments Made</td>
                            <td>MWK <?php echo number_format($payment_balance->total_paid, 2); ?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Remaining Balance</td>
                            <td>MWK <?php echo number_format($payment_balance->remaining_balance, 2); ?></td>
                        </tr>
                        <?php if (!empty($payment_balance->total_late_charges)): ?>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Late charges</td>
                            <td>MWK <?php echo number_format($payment_balance->total_late_charges, 2); ?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;padding-right: 10px;">Total amount due now</td>
                            <td>MWK <?php echo number_format($payment_balance->total_due_now, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    <br>
                    <h2>Schedule Payment</h2>
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
                                <td>MWK <?php echo number_format($next_payment_details->principal,2)?></td>
                            </tr>
                            <tr >
                                <td style="text-align: right;padding-right: 10px;">Pay interest amt</td>
                                <td><a href="#">MK <?php echo number_format($next_payment_details->interest,2)?></a></td>
                            </tr>
                            <tr>
                                <td style="text-align: right;padding-right: 10px;">Payment amt</td>
                                <td>MWK <?php echo number_format($next_payment_details->amount,2)?></td>
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
                                    if($next_payment_details->payment_schedule < date('Y-m-d') AND in_array($next_payment_details->status, array('NOT PAID', 'PARTIAL PAID'))) {
                                        $oved = ' | OVER DUE';
                                    } elseif($next_payment_details->status=='PAID') {

                                    } elseif($next_payment_details->payment_schedule == date('Y-m-d') AND in_array($next_payment_details->status, array('NOT PAID', 'PARTIAL PAID'))) {

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
                    <div class="scrollable-table-container">
                    <table class="scrollable-table">
                        <thead>
                        <tr style="border: 1px solid black;">
                            <th>Payment #</th>
                            <th>Check Date</th>

                            <th>Principal</th>
                            <th>Interest</th>
                            <th>Admin fee</th>
                            <th>Loan cover</th>
                            <th>Late Charges</th>
                            <th>Total Pay Amount</th>
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
                            if($p->payment_schedule < date('Y-m-d') AND in_array($p->status, array('NOT PAID', 'PARTIAL PAID'))) {
                                $css = ' class="due"';
                                $xstatus = ' | OVER DUE';
                            } elseif($p->status=='PAID') {
                                $css = 'class="paid"';
                            } elseif($p->payment_schedule == date('Y-m-d') AND in_array($p->status, array('NOT PAID', 'PARTIAL PAID'))) {
                                $css = ' class="due_now"';
                                $xstatus = ' | DUE TODAY';
                            }
                            ?>
                            <!--							<tr style="border: 1px solid black;background-color: #F3D8D8;">-->
                            <tr style="font-weight: <?php echo !empty($xstatus)?'900':'200'; ?>;">
                                <td <?php echo $css; ?>><?php  echo $p->payment_number?></td>
                                <td <?php echo $css; ?>><?php  echo $p->payment_schedule?></td>

                                <td <?php echo $css; ?>>MWK<?php  echo number_format($p->principal,2) ?></td>
                                <td <?php echo $css; ?>>MWK<?php  echo number_format($p->interest,2) ?></td>
                                <td <?php echo $css; ?>>MWK<?php  echo number_format($p->padmin_fee,2) ?></td>
                                <td <?php echo $css; ?>>MWK<?php  echo number_format($p->ploan_cover,2) ?></td>
                                <td <?php echo $css; ?>>MWK<?php  echo number_format($p->total_late_charge ?? 0, 2) ?></td>
                                <td <?php echo $css; ?>>MWK<?php  echo number_format($p->total_pay_amount ?? $p->amount, 2) ?></td>
                                <td <?php echo $css; ?>>MWK<?php  echo number_format($p->paid_amount,2)?></td>
                                <td <?php echo $css; ?>>MWK<?php  echo number_format(max(0, $p->loan_balance),2)?></td>
                                <td width="150" <?php echo $css; ?>><span style="color:<?php echo $p->status=='PAID' ? 'GREEN' : 'RED'?>"><?php echo $p->status.$xstatus; if($p->partial_paid=="YES"){echo "-<font color='green'>(Partial paid)</font>";}?></span></td>
                                <td <?php echo $css; ?> width="70"></td>
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
                    <hr>
                    <h2>Collateral information</h2>
                    <?php
                    $collaterals = get_all_by_id('collateral','loan_id', $loan_id);

                    ?>
                    <table style="width: 100%;border-collapse: collapse;">
                        <tr>


                            <th>Collateral Name</th>
                            <th>Collateral Type</th>
                            <th>Serial</th>
                            <th>Estimated Price</th>
                            <th>Attachment</th>
                            <th>Description</th>
                            <th>Date Added</th>

                        </tr><?php
                        foreach ($collaterals as $collateral)
                        {
                            ?>
                            <tr>


                                <td><?php echo $collateral->collateral_name ?></td>
                                <td><?php echo $collateral->collateral_type ?></td>
                                <td><?php echo $collateral->serial ?></td>
                                <td>MWK<?php echo number_format($collateral->estimated_price, 2) ?></td>
                                <td><a href="<?php echo base_url('uploads/'). $collateral->attachement ?>">Download attachment</a></td>
                                <td><?php echo $collateral->description ?></td>
                                <td><?php echo $collateral->date_added ?></td>

                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                </div>
                <div class="col-lg-2">
                    <a href="<?php echo base_url('loan/report/').$loan_id ?>" style="color: red;"><i class="fa fa-file-pdf fa-2x"></i>Report</a>

                    <?php
                    $last = get_last($loan_number);
                    if(!empty($last)){
                        ?>
                        <a href="<?php echo base_url('account/print_loan_receipt/').$last->id ?>" target="_blank" style="color: green;"><i class="fa fa-print fa-2x"></i>Latest Receipt</a>
                        <hr>
                        <?php
                    }
                    // Only show the latest non-reversed deposit for reversal
                    $this->db->where('account_number', $loan_number);
                    $this->db->where('credit >', 0);
                    $this->db->where("(reversed IS NULL OR reversed != 'Yes')");
                    $this->db->where("transaction_type != 'other'");
                    $this->db->order_by('system_time', 'DESC');
                    $this->db->limit(1);
                    $latest_deposit = $this->db->get('transaction')->row();
                    if(!empty($latest_deposit)){
                    ?>
                    <h5 style="color:#153505;margin-top:10px;">Latest Deposit</h5>
                        <div style="border:1px solid #ddd;border-radius:6px;padding:8px;margin-bottom:6px;font-size:12px;">
                            <div><strong>Ref:</strong> <?php echo $latest_deposit->transaction_id; ?></div>
                            <div><strong>Amount:</strong> MWK<?php echo number_format($latest_deposit->credit,2); ?></div>
                            <div><strong>Date:</strong> <?php echo $latest_deposit->system_time; ?></div>
                            <a href="<?php echo base_url('Loan/transaction_reversal?tid='.$latest_deposit->transaction_id.'&account='.$loan_number)?>" class="btn btn-xs btn-danger" style="margin-top:4px;" onclick="return confirm('Are you sure you want to reverse this deposit of MWK<?php echo number_format($latest_deposit->credit,2); ?>? This is not recoverable.')"><i class="fa fa-undo"></i> Reverse</a>
                        </div>
                    <hr>
                    <?php
                    }
                    if(empty($next_payment_details)){
                        echo "No more payments to make";
                    }else{
                        ?>
                        <!--<h4>Pay</h4>-->
                        <?php if($p->loan_status=='ACTIVE'){
                            ?>
                            <!--							<a href="#" class="btn btn-sm btn-success" onclick="pay_current()">To current schedule</a> |-->
                            <!--							<a href="#" onclick="advance_payment()" class="btn btn-small btn-danger">Advance payment</a>-->

                        <?php }
                        ?>
                        <?php

                    }
                    ?>

                </div>
            </div>
        </div>
    </div>

</div>


<div aria-hidden="true" class="onboarding-modal modal fade" id="payment_modal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg modal-centered" role="document">
        <div class="modal-content text-center">
            <span></span><button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
            <div class="onboarding-content" style="padding: 1em;">
                <h4 class="onboarding-title" >Payments</h4>
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


                        <input style="border: thin red solid;" type="text" class="form-control" name="amount"  value="<?php echo $next_payment_details->amount?>" placeholder="Enter pay amount" readonly required />
                        <br/>
                        <label for="id_front" class="custom-file-upload"> Upload proof of payment (only image) </label>
                        <input type="file"  onchange="uploadcommon('id_front')"   id="id_front"  accept="image/*" />
                        <input type="text" id="id_front1"  name="pay_proof" hidden required>
                        <div id="id_front2">
                            <img src="<?php echo base_url('uploads/holder.PNG')?>" alt="" height="100" width="100">
                        </div>
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
                    <div class="scrollable-table-container">
                    <table class="scrollable-table" style="min-width: 1000px;">
                        <thead>
                        <tr style="border: 1px solid black;">
                            <th>Payment #</th>
                            <th>Check Date</th>
                            <th>Amount</th>
                            <th>Late Charges</th>
                            <th>Total Amount</th>
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
                            if($p->payment_schedule < date('Y-m-d') AND in_array($p->status, array('NOT PAID', 'PARTIAL PAID'))) {
                                $css = ' class="due"';
                                $xstatus = ' | OVER DUE';
                            } elseif($p->status=='PAID') {
                                $css = 'class="paid"';
                            } elseif($p->payment_schedule == date('Y-m-d') AND in_array($p->status, array('NOT PAID', 'PARTIAL PAID'))) {
                                $css = ' class="due_now"';
                                $xstatus = ' | DUE TODAY';
                            }
                            ?>
                            <!--							<tr style="border: 1px solid black;background-color: #F3D8D8;">-->
                            <tr style="font-weight: <?php echo !empty($xstatus)?'900':'200'; ?>;">
                                <td <?php echo $css; ?>><?php  echo $p->payment_number?></td>
                                <td <?php echo $css; ?>><?php  echo $p->payment_schedule?></td>
                                <td <?php echo $css; ?>>MK<?php  echo number_format($p->amount,2) ?></td>
                                <td <?php echo $css; ?>>MK<?php  echo number_format($p->total_late_charge ?? 0, 2) ?></td>
                                <td <?php echo $css; ?>>MK<?php  echo number_format($p->total_pay_amount ?? $p->amount, 2) ?></td>
                                <td <?php echo $css; ?>>MK<?php  echo number_format($p->paid_amount,2)?></td>
                                <td <?php echo $css; ?>>MK<?php  echo number_format(max(0, $p->loan_balance),2)?></td>
                                <td width="150" <?php echo $css; ?>><span style="color:<?php echo $p->status=='PAID' ? 'GREEN' : 'RED'?>"><?php echo $p->status.$xstatus; if($p->partial_paid=="YES"){echo "-<font color='green'>(Partial paid)</font>";}?></span></td>
                                <td <?php echo $css; ?> width="70"><?php if($p->status == 'NOT PAID') { ?>  <input type="checkbox" name="payment_number[]" value="<?php echo $p->payment_number  ?>" class="check-cls"><?php  } ?></td>
                            </tr>
                            <?php
                        }
                        ?>



                        </tbody>
                    </table>
                    </div>

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
                <h4 class="onboarding-title" >Payments</h4>
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
