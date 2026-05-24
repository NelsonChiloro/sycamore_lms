<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">All written off loans</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">All loans written off</span>
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
            <table  id="data-table" class="tableCss">
                <thead>
                <tr>

                    <th>#</th>
                    <th>Loan Number</th>
                    <th>Loan Product</th>
                    <th>Loan Customer</th>
                    <th>Loan Date</th>
                    <th>Loan Principal</th>
                    <th>Loan Period</th>
                    <!--					<th>Period Type</th>-->
                    <th>Loan Interest</th>
                    <th>Loan Amount Total</th>
                    <th>Loan File</th>


                    <th>Loan Status</th>
                    <th>Loan Added Date</th>


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
                    $pays = FALSE;
                    $paid = get_by_id('charges','charge_id','1');
                    if($paid->is_active=="Yes"){
                        $mandate_fees = TRUE;
                        $get_transactions = check_paid_fees($loan->loan_id);

                        if(!empty($get_transactions)){
                            $pays = TRUE;

                        }
                    }
                    ?>
                    <tr>

                        <td><?php echo $n ?></td>
                        <td><a href="<?php echo base_url('loan/view/').$loan->loan_id?>" ><?php echo $loan->loan_number ?></a></td>
                        <td><?php echo $loan->product_name ?></td>
                        <td><a href="<?php echo base_url('individual_customers/view/').$loan->id?>""><?php echo $loan->Firstname." ".$loan->Lastname?></a></td>
                        <td><?php echo $loan->loan_date ?></td>
                        <td>MK<?php echo number_format($loan->loan_principal,2) ?></td>
                        <td><?php echo $loan->loan_period ?></td>
                        <!--						<td>--><?php //echo $loan->period_type ?><!--</td>-->
                        <td><?php echo $loan->loan_interest ?>%</td>
                        <td>MK<?php echo number_format($loan->loan_amount_total,2) ?></td>
                        <td><a href="<?php echo base_url('uploads/').$loan->worthness_file?>" download >Download file <i class="fa fa-download fa-flip"></i></a></td>

                        <td><?php echo $loan->loan_status ?></td>
                        <td><?php echo $loan->loan_added_date ?></td>


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
