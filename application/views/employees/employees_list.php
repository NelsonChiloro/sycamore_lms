<!-- Content Wrapper START -->
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Employees</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Users</a>
				<span class="breadcrumb-item active">System Employees</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<a href="<?php echo base_url('Employees/create')?>" class="btn btn-sm btn-primary"><i class="anticon anticon-plus-square" style="color: white; font-size: 20px;"></i>Add Employee</a>
			<div class="m-t-25">
				<table id="data-table" class="table">
					<thead>

		<th>First name</th>
		<th>#</th>
		<th>Middle name</th>
		<th>Last name</th>
		<th>Gender</th>

		<th>Email Address</th>
		<th>Phone Number</th>


		<th>Role</th>


		<th>CreatedOn</th>

		<th>Action</th>
            </tr>
					</thead>
					<tbody>
		<?php
		$n = 1;
            foreach ($employees_data as $employees)
            {
                ?>
                <tr>
					<td><?php echo $n ?></td>

			<td><?php echo $employees->Firstname ?></td>
			<td><?php echo $employees->Middlename ?></td>
			<td><?php echo $employees->Lastname ?></td>
			<td><?php echo $employees->Gender ?></td>

			<td><?php echo $employees->EmailAddress ?></td>
			<td><?php echo $employees->PhoneNumber ?></td>

			<td><?php echo $employees->RoleName ?></td>



			<td><?php echo $employees->CreatedOn ?></td>

			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('employees/read/'.$employees->empid),'Read');
				echo ' | '; 
				echo anchor(site_url('employees/update/'.$employees->empid),'Update');
				echo ' | '; 
				echo anchor(site_url('employees/delete/'.$employees->empid),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"');
				?>
			</td>
		</tr>
                <?php
				$n ++;
            }
            ?>
					</tbody>
        </table>

			</div>
		</div>
	</div>
</div>
