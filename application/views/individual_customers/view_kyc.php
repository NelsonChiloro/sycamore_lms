<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Customer view</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Customer Details</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<div class="row">
				<div class="col-lg-4 border-right">
					<h2>Personal Info</h2>
					<hr>
					<table>
						<tr><td style="text-align: right;padding-right: 10px;" >ClientId</td><td><?php echo $ClientId; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >Title</td><td><?php echo $Title; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >Firstname</td><td><?php echo $Firstname; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >Middle name</td><td><?php echo $Middlename; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >Lastname</td><td><?php echo $Lastname; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >Gender</td><td><?php echo $Gender; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >DateOfBirth</td><td><?php echo $DateOfBirth; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >EmailAddress</td><td><?php echo $EmailAddress; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >PhoneNumber</td><td><?php echo $PhoneNumber; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >AddressLine1</td><td><?php echo $AddressLine1; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >AddressLine2</td><td><?php echo $AddressLine2; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >AddressLine3</td><td><?php echo $AddressLine3; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >Province</td><td><?php echo $Province; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >City</td><td><?php echo $City; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >Country</td><td><?php echo $Country; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >ResidentialStatus</td><td><?php echo $ResidentialStatus; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >Profession</td><td><?php echo $Profession; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >SourceOfIncome</td><td><?php echo $SourceOfIncome; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >GrossMonthlyIncome</td><td><?php echo $GrossMonthlyIncome; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >Branch</td><td><?php echo $Branch; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >LastUpdatedOn</td><td><?php echo $LastUpdatedOn; ?></td></tr>
						<tr><td style="text-align: right;padding-right: 10px;" >CreatedOn</td><td><?php echo $CreatedOn; ?></td></tr>

					</table>



				</div>
				<div class="col-lg-7 border-right">
					<h2>KYC</h2>
					<hr>

					<?php

					$row  = $this->Proofofidentity_model->check($ClientId);
					if(empty($row)){
						echo "<font color='red'>Sorry no kyc</font>";

						?>
						<input type="text" value="<?php echo $ClientId?>" id="cid" hidden > <button id="add_kyc" style="padding: 1em; border: thin solid green;">Add KYC</button>
							<?php
					}else{

						?>
						<input type="text" value="<?php echo $ClientId?>" id="cid" hidden > <button id="edit_kyc" style="padding: 1em; border: thin solid yellow;">Edit KYC</button>

						<br>
						<table class="table">
							<tr><td>IDType</td><td><?php echo $row->IDType; ?></td></tr>
							<tr><td>IDNumber</td><td><?php echo $row->IDNumber; ?></td></tr>
							<tr><td>IssueDate</td><td><?php echo $row->IssueDate; ?></td></tr>
							<tr><td>ExpiryDate</td><td><?php echo $row->ExpiryDate; ?></td></tr>
							<tr><td>Date Added </td><td><?php echo $row->Stamp; ?></td></tr>
							<tr><td>ClientId</td><td><?php echo $row->ClientId; ?></td></tr>


						</table>
						<table>
							<thead>
							<tr>
								<th>Photograph</th>
								<th>Signature</th>
								<th>Id Back</th>
								<th>Id Front</th>
							</tr>
							</thead>
							<tbody>
                            <tr>
                                <td>
                                    <img src="<?php echo base_url('uploads/').$row->photograph?>" alt="" height="75" width="150" onclick="image_preview('<?php echo $row->photograph?>')">
                                </td>
                                <td>
                                    <img src="<?php echo base_url('uploads/').$row->signature?>" alt="" height="75" width="150" onclick="image_preview('<?php echo $row->signature?>')">
                                </td>
                                <td>
                                    <img src="<?php echo base_url('uploads/').$row->Id_back?>" alt="" height="75" width="150" onclick="image_preview('<?php echo $row->Id_back?>')">
                                </td>
                                <td>
                                    <img src="<?php echo base_url('uploads/').$row->id_front?>" alt="" height="75" width="150" onclick="image_preview('<?php echo $row->id_front?>')">
                                </td>
                            </tr>tr>
							</tbody>
						</table>
						<?php
					}

					?>
				</div>
				<div class="col-lg-1">
					<a href="<?php echo base_url('individual_customers/report/').$id?>"><i class="fa fa-file-pdf fa-2x text-danger"></i>Report</a>
					<hr>

				</div>
			</div>
		</div>
	</div>

</div>
<div aria-hidden="true" class="onboarding-modal modal fade" id="kyc_modal_edit" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Update KYC to this customer</h4>
				<form action="<?php echo base_url('Proofofidentity/update_action')?>" method="post" class="form-row" enctype="multipart/form-data">
					<div class="form-group col-6">
						<input type="text" hidden name="ClientId" id="Client">
						<label for="enum">IDType </label>
						<select class="form-control" name="IDType" id="IDType" required >
							<option value="">--select--</option>
							<option value="NATIONAL_IDENTITY_CARD" <?php if($row->IDType=="NATIONAL_IDENTITY_CARD"){ echo "selected";}  ?>>NATIONAL IDENTITY CARD</option>
							<option value="DRIVING_LISENCE" <?php if($row->IDType=="DRIVING_LISENCE"){ echo "selected";}  ?>>DRIVING LICENCE</option>
							<option value="PASSPORT" <?php if($row->IDType=="PASSPORT"){ echo "selected";}  ?>>PASSPORT</option>
							<option value="WORK_PERMIT" <?php if($row->IDType=="WORK_PERMIT"){ echo "selected";}  ?>>WORK PERMIT</option>
							<option value="VOTER_REGISTRATION" <?php if($row->IDType=="VOTER_REGISTRATION"){ echo "selected";}  ?>>VOTER REGISTRATION</option>
							<option value="PUBLIC_STATE_OFFICIAL_LETTER" <?php if($row->IDType=="PUBLIC_STATE_OFFICIAL_LETTER"){ echo "selected";}  ?>>PUBLIC STATE OFFICIAL LETTER</option>

						</select>
					</div>
					<div class="form-group col-6">
						<label for="varchar">IDNumber </label>
						<input type="text" class="form-control" name="IDNumber" id="IDNumber" placeholder="IDNumber" value="<?php echo $row->IDNumber ?>" required  />
					</div>
					<div class="form-group col-6">
						<label for="date">IssueDate </label>
						<input type="date" class="form-control" name="IssueDate" id="IssueDate" placeholder="IssueDate" value="<?php echo $row->IssueDate?>" required />
					</div>
					<div class="form-group col-6">
						<label for="date">ExpiryDate * </label>
						<input type="date" class="form-control" name="ExpiryDate" id="ExpiryDate" placeholder="ExpiryDate" value="<?php echo $row->ExpiryDate ?>"  required />
					</div>
					<div class="form-group col-6">
						<label for="id_front" class="custom-file-upload"> Upload front photo of ID </label>
						<input type="file"  onchange="uploadcommon('id_front')"   id="id_front"  />
						<input type="text" id="id_front1"  name="id_front" value="<?php echo $row->id_front ?>" hidden required>
						<div id="id_front2">
							<?php
							if(!empty($row->id_front)){
								?>

								<img src="<?php echo base_url('uploads/').$row->id_front?>" alt="" height="75" width="150">
								<?php
							}else{
								?>
								<img src="<?php echo base_url('uploads/holder.png')?>" alt="" height="100" width="100">
								<?php
							}
							?>
						</div>
					</div>
					<div class="form-group col-6">
						<label for="Id_back" class="custom-file-upload"> Upload Back photo of ID * </label>
						<input type="file" class="upload-btn-wrapper"  onchange="uploadcommon('Id_back')"  id="Id_back" placeholder="Id Back"  />
						<input type="text" id="Id_back1" name="Id_back" value="<?php echo $row->Id_back ?>" hidden required>
						<div id="Id_back2">
							<?php
							if(!empty($row->Id_back)){
								?>

								<img src="<?php echo base_url('uploads/').$row->Id_back?>" alt="" height="75" width="150">
							<?php
							}else{
								?>
								<img src="<?php echo base_url('uploads/holder.png')?>" alt="" height="100" width="100">
							<?php
							}
							?>


						</div>
					</div>

					<div class="form-group col-6">
						<label for="photograph"  class="custom-file-upload">Upload Photograph </label>
						<input type="file"  onchange="uploadcommon('photograph')"    id="photograph" placeholder="Photograph"  />
						<input type="text" id="photograph1" name="photograph" value="<?php echo $row->photograph ?>" hidden required>
						<div id="photograph2">
							<?php
							if(!empty($row->photograph)){
								?>

								<img src="<?php echo base_url('uploads/').$row->photograph?>" alt="" height="75" width="150">
								<?php
							}else{
								?>
								<img src="<?php echo base_url('uploads/holder.png')?>" alt="" height="100" width="100">
								<?php
							}
							?>
						</div>
					</div>
					<div class="form-group col-6">
						<label for="signature" class="custom-file-upload"> Upload Signature </label>
						<input type="file" onchange="uploadcommon('signature')"    id="signature" placeholder="Signature" />
						<input type="text" id="signature1" name="signature" value="<?php echo $row->signature ?>" hidden required>
						<div id="signature2">
							<?php
							if(!empty($row->signature)){
								?>

								<img src="<?php echo base_url('uploads/').$row->signature?>" alt="" height="75" width="150">
								<?php
							}else{
								?>
								<img src="<?php echo base_url('uploads/holder.png')?>" alt="" height="100" width="100">
								<?php
							}
							?>
						</div>
					</div>

					<input type="text" name="id" value="<?php echo $row->id ?>" hidden>
					<button type="submit" class="btn btn-primary">Save Changes</button>

				</form>
			</div>
		</div>
	</div>
</div>
