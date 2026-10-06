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
        <h2 style="margin-top:0px">Loan_customer_first_drafr Read</h2>
        <table class="table">
	    <tr><td>Title</td><td><?php echo $Title; ?></td></tr>
	    <tr><td>Firstname</td><td><?php echo $Firstname; ?></td></tr>
	    <tr><td>Middlename</td><td><?php echo $Middlename; ?></td></tr>
	    <tr><td>Lastname</td><td><?php echo $Lastname; ?></td></tr>
	    <tr><td>Gender</td><td><?php echo $Gender; ?></td></tr>
	    <tr><td>DateOfBirth</td><td><?php echo $DateOfBirth; ?></td></tr>
	    <tr><td>PhoneNumber</td><td><?php echo $PhoneNumber; ?></td></tr>
	    <tr><td>Village</td><td><?php echo $Village; ?></td></tr>
	    <tr><td>TA</td><td><?php echo $TA; ?></td></tr>
	    <tr><td>ClubName</td><td><?php echo $ClubName; ?></td></tr>
	    <tr><td>City</td><td><?php echo $City; ?></td></tr>
	    <tr><td>MarritalStatus</td><td><?php echo $MarritalStatus; ?></td></tr>
	    <tr><td>Country</td><td><?php echo $Country; ?></td></tr>
	    <tr><td>ResidentialStatus</td><td><?php echo $ResidentialStatus; ?></td></tr>
	    <tr><td>Profession</td><td><?php echo $Profession; ?></td></tr>
	    <tr><td>SourceOfIncome</td><td><?php echo $SourceOfIncome; ?></td></tr>
	    <tr><td>GrossMonthlyIncome</td><td><?php echo $GrossMonthlyIncome; ?></td></tr>
	    <tr><td>CreatedOnCustomer</td><td><?php echo $CreatedOnCustomer; ?></td></tr>
	    <tr><td>Loan Number</td><td><?php echo $loan_number; ?></td></tr>
	    <tr><td>Loan Product</td><td><?php echo $loan_product; ?></td></tr>
	    <tr><td>Loan Effectve Date</td><td><?php echo $loan_effectve_date; ?></td></tr>
	    <tr><td>Loan Principal</td><td><?php echo $loan_principal; ?></td></tr>
	    <tr><td>Loan Period</td><td><?php echo $loan_period; ?></td></tr>
	    <tr><td>Period Type</td><td><?php echo $period_type; ?></td></tr>
	    <tr><td>Loan Interest</td><td><?php echo $loan_interest; ?></td></tr>
	    <tr><td>Next Payment Number</td><td><?php echo $next_payment_number; ?></td></tr>
	    <tr><td>Loan Added By</td><td><?php echo $loan_added_by; ?></td></tr>
	    <tr><td>Loan Status</td><td><?php echo $loan_status; ?></td></tr>
	    <tr><td>Loan Added Date</td><td><?php echo $loan_added_date; ?></td></tr>
	    <tr><td>Totalrepaid</td><td><?php echo $Totalrepaid; ?></td></tr>
	    <tr><td>PrincipalPaid</td><td><?php echo $PrincipalPaid; ?></td></tr>
	    <tr><td>InteresrPaid</td><td><?php echo $InteresrPaid; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('loan_customer_first_drafr') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>