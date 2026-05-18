
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Individual customers</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All individual customers</span>
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
		<th>First name</th>
		<th>Middle name</th>
		<th>Last name</th>
		<th>Gender</th>
		<th>DateOfBirth</th>

		<th>CreatedOn</th>
		<th>CreatedBy</th>
		<th>Action</th>
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

			<td><?php echo $individual_customers->CreatedOn ?></td>
			<td><?php
                $gbyid = get_by_id('employees','id', $individual_customers->added_by);
                echo $gbyid->Firstname.' '.$gbyid->Lastname ?></td>
			<td style="text-align:center" width="200px">
				<a href="<?php echo base_url('individual_customers/view/'.$individual_customers->id)?>" class="btn btn-info"><i class="os-icon os-icon-eye"></i>View</a>
				<a href="<?php echo base_url('individual_customers/update/'.$individual_customers->id)?>" class="btn btn-success"><i class="os-icon os-icon-pencil-12"></i>Edit</a>

			</td>
		</tr>
                <?php
            }
            ?>
			</tbody>
        </table>
		</div>
	</div>
</div>
