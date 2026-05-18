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
        <h2 style="margin-top:0px">Loan_customer_first_drafr <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Title <?php echo form_error('Title') ?></label>
            <input type="text" class="form-control" name="Title" id="Title" placeholder="Title" value="<?php echo $Title; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Firstname <?php echo form_error('Firstname') ?></label>
            <input type="text" class="form-control" name="Firstname" id="Firstname" placeholder="Firstname" value="<?php echo $Firstname; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Middlename <?php echo form_error('Middlename') ?></label>
            <input type="text" class="form-control" name="Middlename" id="Middlename" placeholder="Middlename" value="<?php echo $Middlename; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Lastname <?php echo form_error('Lastname') ?></label>
            <input type="text" class="form-control" name="Lastname" id="Lastname" placeholder="Lastname" value="<?php echo $Lastname; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Gender <?php echo form_error('Gender') ?></label>
            <input type="text" class="form-control" name="Gender" id="Gender" placeholder="Gender" value="<?php echo $Gender; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">DateOfBirth <?php echo form_error('DateOfBirth') ?></label>
            <input type="text" class="form-control" name="DateOfBirth" id="DateOfBirth" placeholder="DateOfBirth" value="<?php echo $DateOfBirth; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">PhoneNumber <?php echo form_error('PhoneNumber') ?></label>
            <input type="text" class="form-control" name="PhoneNumber" id="PhoneNumber" placeholder="PhoneNumber" value="<?php echo $PhoneNumber; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Village <?php echo form_error('Village') ?></label>
            <input type="text" class="form-control" name="Village" id="Village" placeholder="Village" value="<?php echo $Village; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">TA <?php echo form_error('TA') ?></label>
            <input type="text" class="form-control" name="TA" id="TA" placeholder="TA" value="<?php echo $TA; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">ClubName <?php echo form_error('ClubName') ?></label>
            <input type="text" class="form-control" name="ClubName" id="ClubName" placeholder="ClubName" value="<?php echo $ClubName; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">City <?php echo form_error('City') ?></label>
            <input type="text" class="form-control" name="City" id="City" placeholder="City" value="<?php echo $City; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">MarritalStatus <?php echo form_error('MarritalStatus') ?></label>
            <input type="text" class="form-control" name="MarritalStatus" id="MarritalStatus" placeholder="MarritalStatus" value="<?php echo $MarritalStatus; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Country <?php echo form_error('Country') ?></label>
            <input type="text" class="form-control" name="Country" id="Country" placeholder="Country" value="<?php echo $Country; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">ResidentialStatus <?php echo form_error('ResidentialStatus') ?></label>
            <input type="text" class="form-control" name="ResidentialStatus" id="ResidentialStatus" placeholder="ResidentialStatus" value="<?php echo $ResidentialStatus; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Profession <?php echo form_error('Profession') ?></label>
            <input type="text" class="form-control" name="Profession" id="Profession" placeholder="Profession" value="<?php echo $Profession; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">SourceOfIncome <?php echo form_error('SourceOfIncome') ?></label>
            <input type="text" class="form-control" name="SourceOfIncome" id="SourceOfIncome" placeholder="SourceOfIncome" value="<?php echo $SourceOfIncome; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">GrossMonthlyIncome <?php echo form_error('GrossMonthlyIncome') ?></label>
            <input type="text" class="form-control" name="GrossMonthlyIncome" id="GrossMonthlyIncome" placeholder="GrossMonthlyIncome" value="<?php echo $GrossMonthlyIncome; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">CreatedOnCustomer <?php echo form_error('CreatedOnCustomer') ?></label>
            <input type="text" class="form-control" name="CreatedOnCustomer" id="CreatedOnCustomer" placeholder="CreatedOnCustomer" value="<?php echo $CreatedOnCustomer; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Number <?php echo form_error('loan_number') ?></label>
            <input type="text" class="form-control" name="loan_number" id="loan_number" placeholder="Loan Number" value="<?php echo $loan_number; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Product <?php echo form_error('loan_product') ?></label>
            <input type="text" class="form-control" name="loan_product" id="loan_product" placeholder="Loan Product" value="<?php echo $loan_product; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Effectve Date <?php echo form_error('loan_effectve_date') ?></label>
            <input type="text" class="form-control" name="loan_effectve_date" id="loan_effectve_date" placeholder="Loan Effectve Date" value="<?php echo $loan_effectve_date; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Principal <?php echo form_error('loan_principal') ?></label>
            <input type="text" class="form-control" name="loan_principal" id="loan_principal" placeholder="Loan Principal" value="<?php echo $loan_principal; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Period <?php echo form_error('loan_period') ?></label>
            <input type="text" class="form-control" name="loan_period" id="loan_period" placeholder="Loan Period" value="<?php echo $loan_period; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Period Type <?php echo form_error('period_type') ?></label>
            <input type="text" class="form-control" name="period_type" id="period_type" placeholder="Period Type" value="<?php echo $period_type; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Interest <?php echo form_error('loan_interest') ?></label>
            <input type="text" class="form-control" name="loan_interest" id="loan_interest" placeholder="Loan Interest" value="<?php echo $loan_interest; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Next Payment Number <?php echo form_error('next_payment_number') ?></label>
            <input type="text" class="form-control" name="next_payment_number" id="next_payment_number" placeholder="Next Payment Number" value="<?php echo $next_payment_number; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Added By <?php echo form_error('loan_added_by') ?></label>
            <input type="text" class="form-control" name="loan_added_by" id="loan_added_by" placeholder="Loan Added By" value="<?php echo $loan_added_by; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Status <?php echo form_error('loan_status') ?></label>
            <input type="text" class="form-control" name="loan_status" id="loan_status" placeholder="Loan Status" value="<?php echo $loan_status; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Loan Added Date <?php echo form_error('loan_added_date') ?></label>
            <input type="text" class="form-control" name="loan_added_date" id="loan_added_date" placeholder="Loan Added Date" value="<?php echo $loan_added_date; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Totalrepaid <?php echo form_error('Totalrepaid') ?></label>
            <input type="text" class="form-control" name="Totalrepaid" id="Totalrepaid" placeholder="Totalrepaid" value="<?php echo $Totalrepaid; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">PrincipalPaid <?php echo form_error('PrincipalPaid') ?></label>
            <input type="text" class="form-control" name="PrincipalPaid" id="PrincipalPaid" placeholder="PrincipalPaid" value="<?php echo $PrincipalPaid; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">InteresrPaid <?php echo form_error('InteresrPaid') ?></label>
            <input type="text" class="form-control" name="InteresrPaid" id="InteresrPaid" placeholder="InteresrPaid" value="<?php echo $InteresrPaid; ?>" />
        </div>
	    <input type="hidden" name="" value="<?php echo $; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('loan_customer_first_drafr') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>