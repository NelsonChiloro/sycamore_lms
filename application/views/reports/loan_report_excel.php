<?php
// Set headers for Excel file
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=loan_portfolio.xls");
?>

<table class="collapse" id="data-table-loan-portfolio">
    <thead>
    <tr>
        <th>Loan Number</th>
        <th>Loan Product</th>
        <th>Loan Customer</th>
        <th>Loan Date</th>
        <th>Loan Principal</th>
        <th>Loan Period</th>
        <th>Period Type</th>
        <th>Loan Officer</th>
        <th>Loan Amount Total</th>
        <th>Loan Status</th>
        <th>Loan Added Date</th>

    </tr>
    </thead>
    <tbody>
    <?php


    foreach ($loan_data as $loan)
    {

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

            <td><?php echo $loan->loan_number ?></td>
            <td><?php echo $loan->product_name ?></td>
            <td><a href="<?php echo base_url($preview_url).$loan->loan_customer?>""><?php echo $customer_name?></a></td>


            <td><?php echo $loan->loan_date ?></td>
            <td>MK<?php echo number_format($loan->loan_principal,2) ?></td>
            <td><?php echo $loan->loan_period ?></td>
            <td><?php echo $loan->period_type ?></td>
            <td><?php echo $loan->efname." ".$loan->elname ?></td>
            <td>MK<?php echo number_format($loan->loan_amount_total,2) ?></td>
            <td><?php echo $loan->loan_status ?></td>
            <td><?php echo $loan->loan_added_date ?></td>

        </tr>
        <?php

    }
    ?>

    </tbody>
</table>