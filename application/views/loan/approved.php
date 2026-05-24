<?php
$charge = get_by_id('charges','charge_id','1');


?>

<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All Loan approved</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All loans approved</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
<<<<<<< HEAD
            <?php if (!empty($show_loan_filters)) { $this->load->view('loan/_loan_list_filters'); } ?>
            <hr>
=======
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
            <div style="overflow-y: auto"">
            <table  id="data-table" class="tableCss" >
                <thead>
                <tr>

                    <th>#</th>
                    <th>Loan Number</th>
                    <th>Loan Product</th>
                    <th>Loan Customer</th>
                    <th>Loan Date</th>
                    <th>Loan Principal</th>
                    <th>Processing fee</th>
                    <th>Amount to disburse</th>
                    <th>Loan Period</th>

                    <th>Has Active loan</th>
                    <th>Period Type</th>
                    <th>Loan Interest</th>
                    <th>Admin Fee</th>
                    <th>Loan Cover</th>
                    <th>Loan Amount Total</th>
                    <th>Loan File</th>


                    <th>Loan Status</th>
                    <th>Funds Source</th>
                    <th>Customer Group</th>
                    <th>Batch</th>
                    <th>Loan Added Date</th>
                    <th>Action</th>

                </tr>
                </thead>
                <tbody><?php
<<<<<<< HEAD
                $n = isset($list_offset) ? ($list_offset + 1) : 1;
=======
                $n = 1;
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d

                $mandate_fees = FALSE;
                foreach ($loan_data as $loan)
                {
<<<<<<< HEAD
                   $has_loan = $this->db->select("*")->from('loan')
                       ->where('loan_customer', $loan->loan_customer)
                       ->where('loan_product', $loan->loan_product)
                       ->where('loan_status', 'ACTIVE')
                       ->where('loan_id !=', $loan->loan_id)
                       ->get()->row();
=======
                   $has_loan = $this->db->select("*")->from('loan')->where('loan_customer',$loan->loan_customer)->where('loan_status','ACTIVE')->get()->row();
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d

                   $pays = FALSE;
                    $paid = get_by_id('loan_products','loan_product_id', $loan->loan_product);
					$charge_value =  ($paid->processing_fees/100)*$loan->loan_principal;
                    $preview_url = ($loan->customer_type == 'group') ? 'Customer_groups/members/' : 'Individual_customers/view/';
                    $customer_name = !empty($loan->customer_display_name) ? $loan->customer_display_name : (!empty($loan->customer_nam) ? $loan->customer_nam : 'Unknown');
                    ?>
                    <tr>

                        <td><?php echo $n ?></td>
                        <td><?php echo $loan->loan_number ?></td>
                        <td><?php echo $loan->product_name ?></td>
                        <td><a href="<?php echo base_url($preview_url).$loan->loan_customer?>""><?php echo $customer_name?></a></td>
                        <td><?php echo $loan->loan_date ?></td>
                        <td>MK<?php echo number_format($loan->loan_principal,2) ?></td>
                        <td>MK<?php echo number_format($charge_value,2) ?></td>
                        <td style="color: red;">MK<?php if($loan->loan_product==6) {echo number_format($loan->loan_principal,2);}else{ echo number_format($loan->loan_principal-$charge_value,2); } ?></td>
                        <td><?php echo $loan->loan_period ?></td>
                        <td><?php if(!empty($has_loan)){echo "<font color='red'>Has active loan -this will be loan top up</font>";}else{echo "<font color='green'>not topup loan</font>";} ?></td>
                        <td><?php echo $loan->period_type ?></td>
                        <td><?php echo $loan->loan_interest ?>%</td>
                        <td><?php echo $loan->admin_fee ?>%</td>
                        <td><?php echo $loan->loan_cover ?>%</td>
                        <td>MK<?php echo number_format($loan->loan_amount_total,2) ?></td>
                        <td><a href="<?php echo base_url('uploads/').$loan->worthness_file?>" download >Download file <i class="fa fa-download fa-flip"></i></a></td>

                        <td><?php echo $loan->loan_status ?></td>
                        <td><?php echo isset($loan->funds_source_name) ? $loan->funds_source_name : 'N/A' ?></td>
                        <td><?php echo isset($loan->customer_group_name) ? $loan->customer_group_name : 'N/A' ?></td>
                        <td><?php echo isset($loan->batch_number) ? $loan->batch_number : 'N/A' ?></td>
                        <td><?php echo $loan->loan_added_date ?></td>
                        <td width="250">



<<<<<<< HEAD
                            <button type="button" class="btn btn-sm btn-danger" onclick="openDisburseModal(<?php echo (int)$loan->loan_id; ?>, '<?php echo htmlspecialchars($loan->loan_number, ENT_QUOTES); ?>')">Disburse</button>
=======
                            <a class="btn btn-sm btn-danger" href="<?php echo base_url('loan/approval_action?id=').$loan->loan_id."&action=ACTIVE" ?>" onclick="return confirm('Are you sure you want to disburse this?')">Disburse</a>
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d


                            <a href="<?php echo base_url('loan/view/').$loan->loan_id?>" class="btn btn-sm btn-info">View loan</a>
                        </td>

                    </tr>
                    <?php
                    $n ++;
                }
                ?>
                </tbody>
            </table>
        </div>
<<<<<<< HEAD
        <?php $this->load->view('loan/_loan_list_pagination'); ?>
=======
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d

		</div>
	</div>
</div>
<<<<<<< HEAD

<div aria-hidden="true" class="onboarding-modal modal fade" id="disburse_loan_modal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Disburse loan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="<?php echo base_url('loan/approval_action'); ?>" id="disburse_loan_form">
                <div class="modal-body">
                    <input type="hidden" name="id" id="disburse_loan_id" value="">
                    <input type="hidden" name="action" value="ACTIVE">
                    <p class="mb-3">Loan: <strong id="disburse_loan_number"></strong></p>
                    <div class="form-group">
                        <label for="disbursement_date">Disbursement date</label>
                        <input type="date" class="form-control" name="disbursement_date" id="disbursement_date">
                        <small class="form-text text-muted">Leave blank to use today's date.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to disburse this loan?');">Disburse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDisburseModal(loanId, loanNumber) {
    document.getElementById('disburse_loan_id').value = loanId;
    document.getElementById('disburse_loan_number').textContent = loanNumber;
    document.getElementById('disbursement_date').value = '';
    $('#disburse_loan_modal').modal('show');
}
</script>
=======
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
