<?php

$b = $this->Branches_model->get_all();
$users = get_all('employees');;
$countryd = $this->Geo_countries_model->get_all();
?>

<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Individual customers</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All individual customers form</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #24C16B solid;border-radius: 14px;">
        <h2 style="margin-top:0px">Individual customer <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post" class="form-row">

	    <div class="form-group col-3">
            <label for="varchar">Title <?php echo form_error('Title') ?></label>
			<select class="form-control" name="Title" id="Title">
				<option value="">Select</option>
				<option value="Mr" <?php if($Title=="Mr"){echo "selected";} ?>>Mr</option>
				<option value="Mrs" <?php if($Title=="Mrs"){echo "selected";} ?>>Mrs</option>
				<option value="Miss" <?php if($Title=="Miss"){echo "selected";} ?>>Miss</option>
				<option value="Dr" <?php if($Title=="Dr"){echo "selected";} ?>>Dr</option>
				<option value="Rev" <?php if($Title=="Rev"){echo "selected";} ?>>Rev</option>
			</select>

        </div>
	    <div class="form-group col-3">
            <label for="varchar">Firstname <?php echo form_error('Firstname') ?></label>
            <input type="text" class="form-control" name="Firstname" id="Firstname" placeholder="Firstname" value="<?php echo $Firstname; ?>" />
        </div>
	    <div class="form-group col-3">
            <label for="varchar">Middlename <?php echo form_error('Middlename') ?></label>
            <input type="text" class="form-control" name="Middlename" id="Middlename" placeholder="Middlename" value="<?php echo $Middlename; ?>" />
        </div>
	    <div class="form-group col-3">
            <label for="varchar">Lastname <?php echo form_error('Lastname') ?></label>
            <input type="text" class="form-control" name="Lastname" id="Lastname" placeholder="Lastname" value="<?php echo $Lastname; ?>" />
        </div>
	    <div class="form-group col-4">
            <label for="enum">Gender <?php echo form_error('Gender') ?></label>
			<select class="form-control" name="Gender" id="Gender">
				<option value="">Select</option>
				<option value="MALE" <?php if($Gender=="MALE"){echo "selected";} ?>>MALE</option>
				<option value="FEMALE" <?php if($Gender=="FEMALE"){echo "selected";} ?>>FEMALE</option>
				<option value="OTHER" <?php if($Gender=="OTHER"){echo "selected";} ?>>OTHER</option>
			</select>

        </div>
            <div class="form-group col-4">
                <label for="enum">Marital Status <?php echo form_error('Marital_status') ?></label>
                <select class="form-control" name="Marital_status" id="Marital_status">
                    <option value="">Select</option>
                    <option value="Married" <?php if($Marital_status=="Married"){echo "selected";} ?>>Married</option>
                    <option value="Single" <?php if($Marital_status=="Single"){echo "selected";} ?>>Single</option>
                    <option value="Widow" <?php if($Marital_status=="Widow"){echo "selected";} ?>>Widow</option>
                    <option value="Widower" <?php if($Marital_status=="Widower"){echo "selected";} ?>>Widower</option>
                </select>

            </div>
	    <div class="form-group col-4">
            <label for="date">Date Of Birth <?php echo form_error('DateOfBirth') ?></label>
            <input type="date" class="form-control" name="DateOfBirth" id="DateOfBirth" placeholder="DateOfBirth" value="<?php echo $DateOfBirth; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="varchar">EmailAddress <?php echo form_error('EmailAddress') ?></label>
            <input type="email" class="form-control" name="EmailAddress" id="EmailAddress" placeholder="EmailAddress" value="<?php echo $EmailAddress; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="varchar">PhoneNumber <?php echo form_error('PhoneNumber') ?></label>
            <input type="text" class="form-control" name="PhoneNumber" id="PhoneNumber" placeholder="PhoneNumber" value="<?php echo $PhoneNumber; ?>" />
        </div>
	    <div class="form-group col-4">
            <label for="varchar">Postal Address <?php echo form_error('AddressLine1') ?></label>
			<textarea class="form-control" name="AddressLine1" id="AddressLine1" placeholder="Address" cols="30" rows="10"><?php echo $AddressLine1; ?></textarea>

        </div>
	    <div class="form-group col-4">
            <label for="varchar">Physical Address <?php echo form_error('AddressLine2') ?></label>
			<textarea type="text" class="form-control" name="AddressLine2" id="AddressLine2" placeholder="Physical Address "cols="30" rows="10"><?php echo $AddressLine2; ?></textarea>

        </div>
	    <div class="form-group col-4">
            <label for="varchar">Next of Kin Address <?php echo form_error('AddressLine3') ?></label>
			<textarea type="text" class="form-control" name="AddressLine3" id="AddressLine3" placeholder="Next of Keen Address "cols="30" rows="10"><?php echo $AddressLine3; ?></textarea>

	 </div>
	    <div class="form-group col-4">
            <label for="varchar">Township<?php echo form_error('Province') ?></label>
            <input type="text" class="form-control" name="Province" id="Province" placeholder="Township" value="<?php echo $Province; ?>" />
        </div>
	    <div class="form-group col-4">
            <label for=" ">District <?php echo form_error('City') ?></label>
            <input type="text" class="form-control" name="City" id="City" placeholder="District" value="<?php echo $City; ?>" />
        </div>
	    <div class="form-group col-4">
            <label for="varchar">Country <?php echo form_error('Country'); ?></label>
			<select class="form-control" name="Country">
				<option value="">--select--</option>
				<?php

				foreach ($countryd as $item){
					?>
					<option value="<?php echo $item->code; ?>" <?php if($item->code==$Country){echo "selected";} ?>><?php echo $item->name?></option>

				<?php
				}
				?>
			</select>

        </div>
	    <div class="form-group col-6">
            <label for="enum">Residential Status <?php echo form_error('ResidentialStatus') ?></label>
            <select name="ResidentialStatus" id="ResidentialStatus" class="form-control">
                <option value="">--select--</option>

                <option value="Owned" <?php  if($ResidentialStatus=='Owned'){ echo "selected"; } ?>>Owned</option>
                <option value="Rented" <?php  if($ResidentialStatus=='Rented'){ echo "selected"; } ?>>Rented</option>
            </select>
<!--            <input type="text" class="form-control" name="ResidentialStatus" id="ResidentialStatus" placeholder="ResidentialStatus" value="--><?php //echo $ResidentialStatus; ?><!--" />-->
        </div>
	    <div class="form-group col-6">
            <label for="varchar">Profession <?php echo form_error('Profession') ?></label>
            <input type="text" class="form-control" name="Profession" id="Profession" placeholder="Profession" value="<?php echo $Profession; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="varchar">SourceOfIncome <?php echo form_error('SourceOfIncome') ?></label>
            <input type="text" class="form-control" name="SourceOfIncome" id="SourceOfIncome" placeholder="SourceOfIncome" value="<?php echo $SourceOfIncome; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="decimal">GrossMonthlyIncome <?php echo form_error('GrossMonthlyIncome') ?></label>
            <input type="text" class="form-control" name="GrossMonthlyIncome" id="GrossMonthlyIncome" placeholder="GrossMonthlyIncome" value="<?php echo $GrossMonthlyIncome; ?>" />
        </div>
	    <div class="form-group col-12">
            <label for="int">Branch <?php echo form_error('Branch') ?></label>
			<select class="form-control" name="Branch" id="Branch">
				<option value="">select</option>
				<?php
				foreach ($b as $br){
				?>
				<option value="<?php echo $br->Code ?>" <?php if($br->Code==$Branch){echo "selected";}  ?>><?php echo $br->BranchName?></option>
				<?php
				}
				?>
			</select>

        </div>
 <div class="form-group col-12">
            <label for="int">Notes <?php echo form_error('narration') ?></label>
	 <textarea name="narration" id="narration" cols="30" rows="10"><?php  echo $narration; ?></textarea>

        </div>
			<div class="form-group col-12">
            <label for="int">Officer <?php echo form_error('added_by') ?></label>
			<select class="form-control" name="added_by" id="added_by" required>
				<option value="">select</option>
				<?php
				foreach ($users as $user){
				?>
				<option value="<?php echo $user->id ?>" <?php if($user->id==$added_by){echo "selected";}  ?>><?php echo $user->Firstname.' '.$user->Lastname ?></option>
				<?php
				}
				?>
			</select>

        </div>

			
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 

	</form>
		</div>
	</div>
</div>
