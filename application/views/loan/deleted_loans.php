<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Deleted / archived loans</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Deleted loans</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <?php if (!empty($show_loan_filters)) { $this->load->view('loan/_loan_list_filters'); } ?>
            <hr>
            <div style="overflow-y: auto">
            <table id="data-table" class="tableCss">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Loan Number</th>
                    <th>Loan Product</th>
                    <th>Loan Customer</th>
                    <th>Loan Date</th>
                    <th>Loan Principal</th>
                    <th>Loan Period</th>
                    <th>Loan Interest</th>
                    <th>Loan Amount Total</th>
                    <th>Loan Status</th>
                    <th>Branch</th>
                    <th>RBM Loan Classification</th>
                    <th>Loan officer</th>
                    <th>Batch</th>
                    <th>Loan Added Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody><?php
                $n = isset($list_offset) ? ($list_offset + 1) : 1;
                foreach ($loan_data as $loan) {
                    $preview_url = ($loan->customer_type == 'group') ? 'Customer_groups/members/' : 'Individual_customers/view/';
                    $customer_name = !empty($loan->customer_display_name) ? $loan->customer_display_name : (!empty($loan->customer_nam) ? $loan->customer_nam : 'Unknown');
                    ?>
                    <tr>
                        <td><?php echo $n ?></td>
                        <td><?php echo htmlspecialchars($loan->loan_number); ?></td>
                        <td><?php echo htmlspecialchars($loan->product_name . '(' . $loan->product_code . ')'); ?></td>
                        <td><a href="<?php echo base_url($preview_url) . $loan->loan_customer; ?>"><?php echo htmlspecialchars($customer_name); ?></a></td>
                        <td><?php echo htmlspecialchars($loan->loan_date); ?></td>
                        <td>MK<?php echo number_format($loan->loan_principal, 2); ?></td>
                        <td><?php echo htmlspecialchars($loan->loan_period); ?></td>
                        <td><?php echo htmlspecialchars($loan->loan_interest); ?>%</td>
                        <td>MK<?php echo number_format($loan->loan_amount_total, 2); ?></td>
                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($loan->loan_status); ?></span></td>
                        <td><?php echo !empty($loan->branch_display_name) ? htmlspecialchars($loan->branch_display_name) : 'N/A'; ?></td>
                        <td><?php echo !empty($loan->rbm_classification) ? htmlspecialchars($loan->rbm_classification) : 'Standard'; ?></td>
                        <td><?php echo htmlspecialchars(trim($loan->efname . ' ' . $loan->elname)); ?></td>
                        <td><?php echo isset($loan->batch_number) ? htmlspecialchars($loan->batch_number) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($loan->loan_added_date); ?></td>
                        <td>
                            <a href="<?php echo base_url('loan/view/') . $loan->loan_id; ?>" class="btn btn-sm btn-primary">View</a>
                            <a href="<?php echo base_url('loan/report/') . $loan->loan_id; ?>" class="btn btn-sm btn-info" target="_blank">Report</a>
                        </td>
                    </tr>
                    <?php
                    $n++;
                }
                ?>
                </tbody>
            </table>
            </div>
            <?php $this->load->view('loan/_loan_list_pagination'); ?>
        </div>
    </div>
</div>
