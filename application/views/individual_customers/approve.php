
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Approve customers</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All individual customers to approve</span>
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
		<th>Date Of Birth</th>
		<th>Status</th>

		<th>CreatedOn</th>
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
			<td style="background-color: red;"><?php echo $individual_customers->approval_status ?></td>
					<td><?php echo $individual_customers->CreatedOn ?></td>
			<td style="text-align:center" width="300px">
                <a href="<?php echo base_url('individual_customers/view/'.$individual_customers->id)?>" class="btn btn-info"><i class="os-icon os-icon-eye"></i>View</a>

                <a href="<?php echo base_url('individual_customers/approval_action/'.$individual_customers->id)?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to approve this customer?')"><i class="os-icon os-icon-check"></i>Approve</a>
				<a href="<?php echo base_url('individual_customers/reject_action/'.$individual_customers->id)?>" class="btn btn-warning" onclick="return confirm('Are you sure you want to reject this customer?')"><i class="fa fa-recycle"></i>Reject</a>

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
