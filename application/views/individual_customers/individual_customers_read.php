
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">customer details</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Customer details</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
						<a href="<?php echo base_url('Individual_customers/read/'.$this->uri->segment(3))?>" class="btn btn-success">View details</a>	<a href="<?php echo base_url('Individual_customers/products/'.$this->uri->segment(3))?>" class="btn btn-warning">Products</a>	<a href="<?php echo base_url('Proofofidentity/check/').$this->uri->segment(3)?>" class="btn btn-primary" >Customer KYC</a>	<a href="<?php echo base_url('Individual_customers/update/').$this->uri->segment(3) ?>" class="btn btn-success">Edit Customer</a><a href="" class="btn btn-warning">Deactivate Customer</a>
						<table class="table">
	    <tr><td>ClientId</td><td><?php echo $ClientId; ?></td></tr>
	    <tr><td>Title</td><td><?php echo $Title; ?></td></tr>
	    <tr><td>Firstname</td><td><?php echo $Firstname; ?></td></tr>
	    <tr><td>Middle name</td><td><?php echo $Middlename; ?></td></tr>
	    <tr><td>Lastname</td><td><?php echo $Lastname; ?></td></tr>
	    <tr><td>Gender</td><td><?php echo $Gender; ?></td></tr>
	    <tr><td>DateOfBirth</td><td><?php echo $DateOfBirth; ?></td></tr>
	    <tr><td>EmailAddress</td><td><?php echo $EmailAddress; ?></td></tr>
	    <tr><td>PhoneNumber</td><td><?php echo $PhoneNumber; ?></td></tr>
	    <tr><td>AddressLine1</td><td><?php echo $AddressLine1; ?></td></tr>
	    <tr><td>AddressLine2</td><td><?php echo $AddressLine2; ?></td></tr>
	    <tr><td>AddressLine3</td><td><?php echo $AddressLine3; ?></td></tr>
	    <tr><td>Province</td><td><?php echo $Province; ?></td></tr>
	    <tr><td>City</td><td><?php echo $City; ?></td></tr>
	    <tr><td>Country</td><td><?php echo $Country; ?></td></tr>
	    <tr><td>ResidentialStatus</td><td><?php echo $ResidentialStatus; ?></td></tr>
	    <tr><td>Profession</td><td><?php echo $Profession; ?></td></tr>
	    <tr><td>SourceOfIncome</td><td><?php echo $SourceOfIncome; ?></td></tr>
	    <tr><td>GrossMonthlyIncome</td><td><?php echo $GrossMonthlyIncome; ?></td></tr>
	    <tr><td>Branch</td><td><?php echo $Branch; ?></td></tr>
	    <tr><td>Group Membership</td><td>
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
	    <tr><td>LastUpdatedOn</td><td><?php echo $LastUpdatedOn; ?></td></tr>
	    <tr><td>CreatedOn</td><td><?php echo $CreatedOn; ?></td></tr>

						</table></div>
	</div>
</div>
