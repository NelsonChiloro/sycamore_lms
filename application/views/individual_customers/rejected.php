
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Rejected customers</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Rejected customers</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #24C16B solid;border-radius: 14px;">

        <table id="data-table" class="table">
            <thead>
			<tr>

		<th>ClientId</th>
		<th>Title</th>
		<th>Firstname</th>
		<th>Middle name</th>
		<th>Lastname</th>
		<th>Gender</th>
		<th>DateOfBirth</th>
		<th>status</th>
			<th>CreatedOn</th>

			</tr>
            </thead>
			<tbody>
			<?php
            foreach ($individual_customers_data as $individual_customers)
            {
                ?>
                <tr>

			<td><?php echo $individual_customers->ClientId ?></td>
			<td><?php echo $individual_customers->Title ?></td>
			<td><?php echo $individual_customers->Firstname ?></td>
			<td><?php echo $individual_customers->Middlename ?></td>
			<td><?php echo $individual_customers->Lastname ?></td>
			<td><?php echo $individual_customers->Gender ?></td>
			<td><?php echo $individual_customers->DateOfBirth ?></td>
			<td><?php echo $individual_customers->approval_status ?></td>

			<td><?php echo $individual_customers->CreatedOn ?></td>

		</tr>
                <?php
            }
            ?>
			</tbody>
        </table>
		</div>
	</div>
</div>
