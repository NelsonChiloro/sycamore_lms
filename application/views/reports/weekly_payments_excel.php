<?php
// Set headers for Excel file
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Weekly_Collection.xls");
?>

<table class="table tab-content" id="data-table-weekly-payment">
    <thead>
    <tr>
        <th>#</th>
        <th>Loan Customer</th>
        <th>Loan Number</th>
        <th>Loan product</th>
        <th>Check Date</th>
        <th>Amount to collect</th>
        <th>Payment number</th>
        <th>Officer</th>

    </tr>
    </thead>
    <tbody>
    <?php
    $n = 1;
    $totals = 0;
    foreach ($loan_data as $loan) {
        //$totals += $loan->amount;
        // Render table rows with data
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
            <td><?php echo $customer_name ?></td>
            <td><?php echo $loan->loan_number ?></td>
            <td><?php echo $loan->product_name ?></td>
            <td><?php echo $loan->payment_schedule ?></td>
            <td><?php echo $loan->amount ?></td>
            <td><?php echo $loan->payment_number ?></td>
            <td><?php echo  $loan->efname." ".$loan->elname ?></td>

        </tr>
        <?php
        $n++;
    }
    ?>
    </tbody>
    <tfoot>
    <tr>
        <th colspan="5"></th>
        <th>MK<?php echo number_format($totals, 2) ?></th>
        <th colspan="3"></th>
    </tr>
    </tfoot>
</table>
