<?php

$b = $this->Branches_model->get_all();
$countryd = $this->Geo_countries_model->get_all();
$sig = $this->Authorized_signitories_model->get_by_client($ClientId);

$cu = $this->Currency_model->get_all();

$chart = $this->Chart_of_accounts_model->get_all_savings();
$account = $this->Accounts_model->get_ours($ClientId);
$et = $this->Entity_type_model->get_all();

?>
<div class="content-i">
	<div class="content-box">
		<div class="element-wrapper">
			<h6 class="element-header">Corporate Customers</h6>
			<div class="element-box">


				<ul class="nav nav-tabs" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" href="#profile" role="tab" data-toggle="tab"><i class="os-icon os-icon-eye"></i>Customer Details</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#buzz" role="tab" data-toggle="tab"><i class="os-icon os-icon-pencil-12"></i>Edit Customer</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#references" role="tab" data-toggle="tab"><i class="os-icon os-icon-users"></i>Signatories</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#contact" role="tab" data-toggle="tab"><i class="os-icon  os-icon-list"></i>Accounts</a>
					</li>
				</ul>

				<!-- Tab panes -->
				<div class="tab-content">
					<div role="tabpanel" class="tab-pane fade in active" id="profile">
						<table class="table">
							<tr><td>Entity Name</td><td><?php echo $EntityName; ?></td></tr>
							<tr><td>Date Of Incorporation</td><td><?php echo $DateOfIncorporation; ?></td></tr>
							<tr><td>Registration Number</td><td><?php echo $RegistrationNumber; ?></td></tr>
							<tr><td>Entity Type</td><td><?php echo $EntityType; ?></td></tr>
							<tr><td>Client Id</td><td><?php echo $ClientId; ?></td></tr>
							<tr><td>Tax Identification Number</td><td><?php echo $TaxIdentificationNumber; ?></td></tr>
							<tr><td>Country</td><td><?php echo $Country; ?></td></tr>
							<tr><td>Branch</td><td><?php echo $Branch; ?></td></tr>
							<tr><td>Status</td><td><?php echo $Status; ?></td></tr>
							<tr><td>Does Qualify</td><td><?php echo $DoesQualify; ?></td></tr>
							<tr><td>Last UpdatedOn</td><td><?php echo $LastUpdatedOn; ?></td></tr>
							<tr><td>Created On</td><td><?php echo $CreatedOn; ?></td></tr>

						</table>
					</div>
					<div role="tabpanel" class="tab-pane fade" id="buzz">
						<form action="<?php echo base_url('corporate_customers/update_action'); ?>" method="post" class="form-row">
							<div class="form-group col-6">
								<label for="varchar">Entity Name </label>
								<input type="text" class="form-control" name="EntityName" id="EntityName" placeholder="EntityName" value="<?php echo $EntityName; ?>" />
							</div>
							<div class="form-group col-6">
								<label for="date">Date Of Incorporation </label>
								<input type="date" class="form-control" name="DateOfIncorporation" id="DateOfIncorporation" placeholder="DateOfIncorporation" value="<?php echo $DateOfIncorporation; ?>" />
							</div>
							<div class="form-group col-6">
								<label for="varchar">Registration Number <?php echo form_error('RegistrationNumber') ?></label>
								<input type="text" class="form-control" name="RegistrationNumber" id="RegistrationNumber" placeholder="RegistrationNumber" value="<?php echo $RegistrationNumber; ?>" />
							</div>
							<div class="form-group col-6">
								<label for="varchar">Entity Type <?php echo form_error('EntityType') ?></label>
								<select class="form-control" name="EntityType">
									<option value="">--select--</option>
									<?php

									foreach ($et as $i){
										?>
										<option value="<?php echo $i->name; ?>" <?php if($i->name==$EntityType) ?>><?php echo $i->name?></option>

										<?php
									}
									?>
								</select>
							</div>


							<div class="form-group col-6">
								<label for="varchar">Tax Identification Number <?php echo form_error('TaxIdentificationNumber') ?></label>
								<input type="text" class="form-control" name="TaxIdentificationNumber" id="TaxIdentificationNumber" placeholder="TaxIdentificationNumber" value="<?php echo $TaxIdentificationNumber; ?>" />
							</div>
							<div class="form-group col-6">
								<label for="varchar">Country <?php echo form_error('Country') ?></label>
								<select class="form-control" name="Country">
									<option value="">--select--</option>
									<?php

									foreach ($countryd as $item){
										?>
										<option value="<?php echo $item->code; ?>" <?php if($item->code==$Country) ?>><?php echo $item->name?></option>

										<?php
									}
									?>
								</select>

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

							<div class="form-group col-4">
								<label for="certificate" class="custom-file-upload"> Upload company certificate </label>
								<input type="file"  onchange="uploadprofile('certificate')"   id="certificate"  />
								<input type="text" id="certificate1"  name="company_certificate" value="<?php  echo $company_certificate;?>" hidden required>
								<div id="certificate2">
									<img src="<?php if($company_certificate == ""){echo base_url('uploads/holder.PNG');}else{ echo base_url('uploads/'.$company_certificate);}?>" alt="" height="100" width="100">
								</div>
							</div>
							<div class="form-group col-4">
								<label for="tax_id_doc" class="custom-file-upload"> Upload company tax clearance certificate </label>
								<input type="file"  onchange="uploadprofile('tax_id_doc')"   id="tax_id_doc"  />
								<input type="text" id="tax_id_doc1"  name="tax_id_doc" value="<?php  echo $tax_id_doc?>" hidden required>
								<div id="tax_id_doc2">
									<img src="<?php if($tax_id_doc == ""){echo base_url('uploads/holder.PNG');}else{ echo base_url('uploads/'.$tax_id_doc);}?>" alt="" height="100" width="100">
								</div>
							</div>
							<div class="form-group col-4">
								<label for="memo_doc" class="custom-file-upload"> Upload company memo of understanding </label>
								<input type="file"  onchange="uploadprofile('memo_doc')"   id="memo_doc"  />
								<input type="text" id="memo_doc1"  name="memo_doc"  value="<?php  echo $memo_doc ?>" hidden required>
								<div id="memo_doc2">
									<img src="<?php if($memo_doc == ""){echo base_url('uploads/holder.PNG');}else{ echo base_url('uploads/'.$memo_doc);}?>" alt="" height="100" width="100">
								</div>
							</div>


							<input type="hidden" name="id" value="<?php echo $id; ?>" />
							<button type="submit" class="btn btn-primary">update</button>

						</form>
					</div>
					<div role="tabpanel" class="tab-pane fade" id="references">
						<div class="row">

							<div class="col-lg-4 border-right">
								<h4>Add Signatory</h4>
								<form action="<?php echo base_url('Authorized_signitories/create_action')?>" method="post" class="form-row">
									<div class="form-group col-6">
										<label for="int">Client Id </label>
										<input type="text" readonly class="form-control" value="<?php echo $ClientId?>" name="ClientId" id="ClientId" placeholder="ClientId"  />
									</div>

									<div class="form-group col-6">
										<label for="varchar">Full Legal Name </label>
										<input type="text" class="form-control" name="FullLegalName" id="FullLegalName" placeholder="FullLegalName"  />
									</div>
									<div class="form-group col-6">

										<label for="enum">IDType </label>
										<select class="form-control" name="IDType" id="IDType" required >
											<option value="">--select--</option>
											<option value="NATIONAL_IDENTITY_CARD">NATIONAL IDENTITY CARD</option>
											<option value="DRIVING_LISENCE">DRIVING LICENCE</option>
											<option value="PASSPORT">PASSPORT</option>
											<option value="WORK_PERMIT">WORK PERMIT</option>
											<option value="VOTER_REGISTRATION">VOTER REGISTRATION</option>
											<option value="PUBLIC_STATE_OFFICIAL_LETTER">PUBLIC STATE OFFICIAL LETTER</option>

										</select>
									</div>
									<div class="form-group col-6">
										<label for="varchar">ID Number </label>
										<input type="text" class="form-control" name="IDNumber" id="IDNumber" placeholder="IDNumber"  />
									</div>
									<div class="form-group col-6">
										<label for="DocImageURL"  class="custom-file-upload">upload Id photo </label>
										<input type="file"  onchange="uploadprofile('DocImageURL')" class="form-control" id="DocImageURL" placeholder="DocImageURL"  />

										<input type="text" id="DocImageURL1"  name="DocImageURL" hidden required>
										<div id="DocImageURL2">
											<img src="<?php echo base_url('uploads/holder.PNG')?>" alt="" height="100" width="100">
										</div>
									</div>
									<div class="form-group col-6">
										<label for="SignatureImage" class="custom-file-upload"> upload Signature Image </label>
										<input type="file" class="form-control" onchange="uploadprofile('SignatureImage')" id="SignatureImage" placeholder="SignatureImageURL"  />
										<input type="text" id="SignatureImage1"  name="SignatureImageURL" hidden required>
										<div id="SignatureImage2">
											<img src="<?php echo base_url('uploads/holder.PNG')?>" alt="" height="100" width="100">
										</div>
									</div>
									<button type="submit" class="btn btn-primary">Create</button>

								</form>
							</div>

							<div class="col-lg-8">

								<?php
								if(empty($sig)){
									echo "<font color='red'>Sorry this Corporate customer has no signatories yet</font>";
								}
								else{
									?>

									<table class="table table-bordered" style="margin-bottom: 10px">
										<tr>


											<th>Full Legal Name</th>
											<th>IDT ype</th>
											<th>ID Number</th>
											<th>Doc Image </th>
											<th>Signature Image </th>

											<th>Action</th>
										</tr>
										<?php
										foreach ($sig as $authorized_signitories)
										{
											?>
											<tr>


												<td><?php echo $authorized_signitories->FullLegalName ?></td>
												<td><?php echo $authorized_signitories->IDType ?></td>
												<td><?php echo $authorized_signitories->IDNumber ?></td>
												<td><img src="<?php echo base_url('uploads/').$authorized_signitories->DocImageURL?>" alt="" height="50" width="50"></td>
												<td><img src="<?php echo base_url('uploads/').$authorized_signitories->SignatureImageURL?>" alt="" height="50" width="50"></td>

												<td style="text-align:center" width="200px">
													<?php

													echo anchor(site_url('authorized_signitories/update/'.$authorized_signitories->id),'Update');
														?>
												</td>
											</tr>
											<?php
										}
										?>
									</table>

									<?php
								}
								?>


							</div>
						</div>
					</div>
					<div role="tabpanel" class="tab-pane fade" id="contact">
						<div class="row">
							<div class="col-lg-7">
								<h2 style="margin-top:0px">Account opening</h2>
								<form action="<?php echo base_url('Accounts/finish2') ?>" method="post" class="form-row">
									<!--			<div style="background-color: darkgray; border: #0a7cf8 thick; border-radius: 15px;">-->

									<div class="form-group border-bottom col-6">
										<label for="int">Account Class </label>
										<select class="form-control" name="coc" id="coc" >
											<option value="">--select--</option>
											<?php
											foreach ($chart as $item){
												?>
												<option value="<?php  echo $item->Code ?>" ><?php  echo $item->Description ?></option>
												<?php
											}
											?>
										</select>

									</div>
									<div class="form-group border-bottom col-6">
										<label for="int">Currency </label>
										<select class="form-control" name="currency" id="currency" >
											<option value="">--select--</option>
											<?php
											foreach ($cu as $value){
												?>
												<option value="<?php  echo $value->code ?>" ><?php  echo $value->name ?></option>
												<?php
											}
											?>
										</select>

									</div>
									<!--			</div>-->
									<hr>
									<div class="form-group col-12">
										<label for="varchar">ClientID</label>
										<input readonly type="text" class="form-control" name="client" id="name" placeholder="Name" value="<?php echo $ClientId; ?>" />
									</div>

									<button type="submit" class="btn btn-primary">Open Account</button>

								</form>
							</div>
							<div class="col-lg-5">
								<h3>Account Numbers</h3>
								<ul>
									<?php

									foreach ($account as $item){
										?>
										<li><?php echo $item->AccountNumber; ?></li>
										<?php
									}
									?>




								</ul>
							</div>
						</div>
					</div>
				</div>
		</div>
	</div>
</div>
</div>
