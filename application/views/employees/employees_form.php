<?php
$r = $this->Roles_model->get_all();
$b = $this->Branches_model->get_all();
$countryd = $this->Geo_countries_model->get_all();
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">users</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">users</a>
				<span class="breadcrumb-item active">System users </span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<h4 style="color: red;">NB:those with * are mandatory</h4>
						<form action="<?php echo $action; ?>" method="post" class="form-row">
							<div class="form-group col-4">
								<label for="varchar">Firstname * <?php echo form_error('Firstname') ?></label>
								<input type="text" class="form-control" name="Firstname" id="Firstname" placeholder="Firstname" value="<?php echo $Firstname; ?>" />
							</div>
							<div class="form-group col-4">
								<label for="varchar">Middlename <?php echo form_error('Middlename') ?></label>
								<input type="text" class="form-control" name="Middlename" id="Middlename" placeholder="Middlename" value="<?php echo $Middlename; ?>" />
							</div>
							<div class="form-group col-4">
								<label for="varchar">Lastname * <?php echo form_error('Lastname') ?></label>
								<input type="text" class="form-control" name="Lastname" id="Lastname" placeholder="Lastname" value="<?php echo $Lastname; ?>" />
							</div>
							<div class="form-group col-6">
								<label for="enum">Gender * <?php echo form_error('Gender') ?></label>
								<select class="form-control" name="Gender" id="Gender">
									<option value="">Select</option>
									<option value="MALE" <?php if($Gender=="MALE"){echo "selected";} ?>>MALE</option>
									<option value="FEMALE" <?php if($Gender=="FEMALE"){echo "selected";} ?>>FEMALE</option>
									<option value="OTHER" <?php if($Gender=="OTHER"){echo "selected";} ?>>OTHER</option>
								</select>
							</div>
							<div class="form-group col-6">
								<label for="date">Date Of Birth * <?php echo form_error('DateOfBirth') ?></label>
								<input type="date" class="form-control" name="DateOfBirth" id="DateOfBirth" placeholder="DateOfBirth" value="<?php echo $DateOfBirth; ?>" />
							</div>
							<div class="form-group col-6">
								<label for="varchar">Email Address <?php echo form_error('EmailAddress') ?></label>
								<input type="email" class="form-control" name="EmailAddress" id="EmailAddress" placeholder="EmailAddress" value="<?php echo $EmailAddress; ?>" />
							</div>
							<div class="form-group col-6">
								<label for="varchar">Phone Number * <?php echo form_error('PhoneNumber') ?></label>
								<input type="text" class="form-control" name="PhoneNumber" id="PhoneNumber" placeholder="PhoneNumber" value="<?php echo $PhoneNumber; ?>" />
							</div>
							<div class="form-group col-6">
								<label for="varchar">AddressLine1 <?php echo form_error('AddressLine1') ?></label>
								<textarea class="form-control" name="AddressLine1" id="AddressLine1" placeholder="AddressLine1" cols="30" rows="10"><?php echo $AddressLine1; ?></textarea>

							</div>
							<div class="form-group col-6">
								<label for="varchar">AddressLine2 <?php echo form_error('AddressLine2') ?></label>
								<textarea type="text" class="form-control" name="AddressLine2" id="AddressLine2" placeholder="AddressLine2"cols="30" rows="10"><?php echo $AddressLine2; ?></textarea>

							</div>
							<div class="form-group col-4">
								<label for="varchar">Province/specific location * <?php echo form_error('Province') ?></label>
								<input type="text" class="form-control" name="Province" id="Province" placeholder="Province" value="<?php echo $Province; ?>" />
							</div>
							<div class="form-group col-4">
								<label for="varchar">City * <?php echo form_error('City') ?></label>
								<input type="text" class="form-control" name="City" id="City" placeholder="City" value="<?php echo $City; ?>" />
							</div>
							<div class="form-group col-4">


								<label for="varchar">Country * <?php echo form_error('Country') ?></label>
								<select class="form-control" name="Country">
									<option value="">--select--</option>
									<?php

									foreach ($countryd as $item){
										?>
										<option value="<?php echo $item->id; ?>" <?php if($item->id==$Country){echo "selected";} ?>><?php echo $item->name?></option>

										<?php
									}
									?>
								</select>


							</div>
							<div class="form-group col-6">
								<label for="int">Role * <?php echo form_error('Role') ?></label>
								<select class="form-control" name="Role" >
									<option value="">--select--</option>
									<?php
									foreach ($r as $item){
										?>
										<option value="<?php echo  $item->id?>" <?php if($Role==$item->id){echo "selected";} ?>><?php echo  $item->RoleName?></option>

										<?php
									}
									?>
								</select>

							</div>
							<div class="form-group col-6">
								<label for="int">Branch * <?php echo form_error('Branch') ?></label>

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
							<input type="text" name="system_date" value="<?php echo $this->session->userdata('system_date') ?>" hidden>
							<input type="hidden" name="id" value="<?php echo $id; ?>" />
							<button type="submit" class="btn btn-primary"><?php echo $button ?></button>
							<a href="<?php echo site_url('employees') ?>" class="btn btn-default">Cancel</a>
						</form>
		</div>
	</div>
</div>
