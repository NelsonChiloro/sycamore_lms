<!doctype html>
<html>
    <head>
        <title>harviacode.com - codeigniter crud generator</title>
        <link rel="stylesheet" href="<?php echo base_url('assets/bootstrap/css/bootstrap.min.css') ?>"/>
        <style>
            body{
                padding: 15px;
            }
        </style>
    </head>
    <body>
        <h2 style="margin-top:0px">Loan_customer_first_drafr List</h2>
        <div class="row" style="margin-bottom: 10px">
            <div class="col-md-4">
                <?php echo anchor(site_url('loan_customer_first_drafr/create'),'Create', 'class="btn btn-primary"'); ?>
            </div>
            <div class="col-md-4 text-center">
                <div style="margin-top: 8px" id="message">
                    <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                </div>
            </div>
            <div class="col-md-1 text-right">
            </div>
            <div class="col-md-3 text-right">
                <form action="<?php echo site_url('loan_customer_first_drafr/index'); ?>" class="form-inline" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="<?php echo $q; ?>">
                        <span class="input-group-btn">
                            <?php 
                                if ($q <> '')
                                {
                                    ?>
                                    <a href="<?php echo site_url('loan_customer_first_drafr'); ?>" class="btn btn-default">Reset</a>
                                    <?php
                                }
                            ?>
                          <button class="btn btn-primary" type="submit">Search</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
        <table class="table table-bordered" style="margin-bottom: 10px">
            <tr>
                <th>No</th>
		<th>Title</th>
		<th>Firstname</th>
		<th>Middlename</th>
		<th>Lastname</th>
		<th>Gender</th>
		<th>DateOfBirth</th>
		<th>PhoneNumber</th>
		<th>Village</th>
		<th>TA</th>
		<th>ClubName</th>
		<th>City</th>
		<th>MarritalStatus</th>
		<th>Country</th>
		<th>ResidentialStatus</th>
		<th>Profession</th>
		<th>SourceOfIncome</th>
		<th>GrossMonthlyIncome</th>
		<th>CreatedOnCustomer</th>
		<th>Loan Number</th>
		<th>Loan Product</th>
		<th>Loan Effectve Date</th>
		<th>Loan Principal</th>
		<th>Loan Period</th>
		<th>Period Type</th>
		<th>Loan Interest</th>
		<th>Next Payment Number</th>
		<th>Loan Added By</th>
		<th>Loan Status</th>
		<th>Loan Added Date</th>
		<th>Totalrepaid</th>
		<th>PrincipalPaid</th>
		<th>InteresrPaid</th>
		<th>Action</th>
            </tr><?php
            foreach ($loan_customer_first_drafr_data as $loan_customer_first_drafr)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $loan_customer_first_drafr->Title ?></td>
			<td><?php echo $loan_customer_first_drafr->Firstname ?></td>
			<td><?php echo $loan_customer_first_drafr->Middlename ?></td>
			<td><?php echo $loan_customer_first_drafr->Lastname ?></td>
			<td><?php echo $loan_customer_first_drafr->Gender ?></td>
			<td><?php echo $loan_customer_first_drafr->DateOfBirth ?></td>
			<td><?php echo $loan_customer_first_drafr->PhoneNumber ?></td>
			<td><?php echo $loan_customer_first_drafr->Village ?></td>
			<td><?php echo $loan_customer_first_drafr->TA ?></td>
			<td><?php echo $loan_customer_first_drafr->ClubName ?></td>
			<td><?php echo $loan_customer_first_drafr->City ?></td>
			<td><?php echo $loan_customer_first_drafr->MarritalStatus ?></td>
			<td><?php echo $loan_customer_first_drafr->Country ?></td>
			<td><?php echo $loan_customer_first_drafr->ResidentialStatus ?></td>
			<td><?php echo $loan_customer_first_drafr->Profession ?></td>
			<td><?php echo $loan_customer_first_drafr->SourceOfIncome ?></td>
			<td><?php echo $loan_customer_first_drafr->GrossMonthlyIncome ?></td>
			<td><?php echo $loan_customer_first_drafr->CreatedOnCustomer ?></td>
			<td><?php echo $loan_customer_first_drafr->loan_number ?></td>
			<td><?php echo $loan_customer_first_drafr->loan_product ?></td>
			<td><?php echo $loan_customer_first_drafr->loan_effectve_date ?></td>
			<td><?php echo $loan_customer_first_drafr->loan_principal ?></td>
			<td><?php echo $loan_customer_first_drafr->loan_period ?></td>
			<td><?php echo $loan_customer_first_drafr->period_type ?></td>
			<td><?php echo $loan_customer_first_drafr->loan_interest ?></td>
			<td><?php echo $loan_customer_first_drafr->next_payment_number ?></td>
			<td><?php echo $loan_customer_first_drafr->loan_added_by ?></td>
			<td><?php echo $loan_customer_first_drafr->loan_status ?></td>
			<td><?php echo $loan_customer_first_drafr->loan_added_date ?></td>
			<td><?php echo $loan_customer_first_drafr->Totalrepaid ?></td>
			<td><?php echo $loan_customer_first_drafr->PrincipalPaid ?></td>
			<td><?php echo $loan_customer_first_drafr->InteresrPaid ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('loan_customer_first_drafr/read/'.$loan_customer_first_drafr->),'Read'); 
				echo ' | '; 
				echo anchor(site_url('loan_customer_first_drafr/update/'.$loan_customer_first_drafr->),'Update'); 
				echo ' | '; 
				echo anchor(site_url('loan_customer_first_drafr/delete/'.$loan_customer_first_drafr->),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
				?>
			</td>
		</tr>
                <?php
            }
            ?>
        </table>
        <div class="row">
            <div class="col-md-6">
                <a href="#" class="btn btn-primary">Total Record : <?php echo $total_rows ?></a>
	    </div>
            <div class="col-md-6 text-right">
                <?php echo $pagination ?>
            </div>
        </div>
    </body>
</html>