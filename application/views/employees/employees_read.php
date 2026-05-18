<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Employee</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Details</a>
				<span class="breadcrumb-item active">Employee details</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
        <table class="table">
	    <tr><td>Firstname</td><td><?php echo $Firstname; ?></td></tr>
	    <tr><td>Middlename</td><td><?php echo $Middlename; ?></td></tr>
	    <tr><td>Lastname</td><td><?php echo $Lastname; ?></td></tr>
	    <tr><td>Gender</td><td><?php echo $Gender; ?></td></tr>
	    <tr><td>DateOfBirth</td><td><?php echo $DateOfBirth; ?></td></tr>
	    <tr><td>EmailAddress</td><td><?php echo $EmailAddress; ?></td></tr>
	    <tr><td>PhoneNumber</td><td><?php echo $PhoneNumber; ?></td></tr>
	    <tr><td>AddressLine1</td><td><?php echo $AddressLine1; ?></td></tr>
	    <tr><td>AddressLine2</td><td><?php echo $AddressLine2; ?></td></tr>
	    <tr><td>Province</td><td><?php echo $Province; ?></td></tr>
	    <tr><td>City</td><td><?php echo $City; ?></td></tr>
	    <tr><td>Country</td><td><?php echo $Country; ?></td></tr>
	    <tr><td>Role</td><td><?php echo $Role; ?></td></tr>
		<tr><td>Branch</td><td><?php echo $Branch; ?></td></tr>
	    <tr><td>EmploymentStatus</td><td><?php echo $EmploymentStatus; ?></td></tr>
	    <tr><td>LastUpdatedOn</td><td><?php echo $LastUpdatedOn; ?></td></tr>
	    <tr><td>CreatedOn</td><td><?php echo $CreatedOn; ?></td></tr>

	    <tr><td></td><td><a href="<?php echo site_url('employees') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
		</div>
	</div>
</div>
