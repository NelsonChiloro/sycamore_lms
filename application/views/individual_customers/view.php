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
						<tr><td style="text-align: right;padding-right: 10px;" >Group Membership</td><td>
							<?php if (!empty($customer_groups)): ?>
								<?php foreach ($customer_groups as $cg): ?>
									<span class="badge badge-info"><?php echo htmlspecialchars($cg->group_name); ?> (<?php echo htmlspecialchars($cg->group_code); ?>)</span>
									<?php if ($cg->date_joined): ?> - Joined <?php echo $cg->date_joined; ?><?php endif; ?>
									<br>
								<?php endforeach; ?>
							<?php else: ?>
								<em>None</em>
							<?php endif; ?>
						</td></tr>
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
						<input type="text" value="<?php echo $ClientId?>" id="cid" hidden > <button id="add_kyc">Add KYC</button>
							<?php
					}else{

						?>
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
                            </tr>
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
