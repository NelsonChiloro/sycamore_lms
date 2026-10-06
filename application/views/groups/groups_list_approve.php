<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Groups Approval</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All groups to approve</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick green solid;border-radius: 14px;">

			<div class="m-t-25">
        <table class="table table-bordered" id="data-table1" >
			<thead>
            <tr>

		<th>Group Code</th>
		<th>Group Name</th>
		<th>Branch</th>
		<th>Group Description</th>
		<th>Group Added By</th>
		<th>Group Registered Date</th>
		<th>Action</th>
            </tr>
			</thead>
			<tbody>
			<?php
            foreach ($groups_data as $groups)
            {
			if($groups->group_status == 'Pending'){
                ?>
                <tr>

			<td><?php echo $groups->group_code ?></td>
			<td><?php echo $groups->group_name ?></td>
			<td><?php echo $groups->BranchName ?></td>
			<td><?php echo $groups->group_description ?></td>
			<td><?php echo $groups->Firstname.' '.$groups->Lastname ?></td>
			<td><?php echo $groups->group_registered_date ?></td>
			<td  width="500px">
				<a href="<?php echo base_url('Customer_groups/members/').$groups->group_id; ?>" class="btn btn-info" >View Group members</a>
				<a href="#" class="btn btn-danger" onclick="approve_group('<?php echo $groups->group_id; ?>','Active','Approve')">Approve group</a>
				<a href="#" onclick="approve_group('<?php echo $groups->group_id; ?>','Rejected','Reject')" class="btn btn-warning">Reject group</a>

			</td>
		</tr>
                <?php
			}
            }
            ?>
			</tbody>
        </table>

</div>
		</div>
	</div>
</div>
