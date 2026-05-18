<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Groups assigned amount to approve</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All groups</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
        <table class="table table-bordered" style="margin-bottom: 10px" id="d2">
			<thead>
            <tr>
                <th>No</th>
		<th>Group name/code</th>
		<th>Amount</th>
		<th>Status</th>

		<th>Disbursed By</th>
		<th>Date Disbursed</th>
		<th>Action</th>
            </tr>
			</thead>
			<tbody>

			<?php
			$start = 0;
            foreach ($group_assigned_amount_data as $group_assigned_amount)
            {
			if($group_assigned_amount->status=='Pending') {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><a href="<?php echo base_url('groups/read/').$group_assigned_amount->group_id?>"><?php echo $group_assigned_amount->group_name." (".$group_assigned_amount->group_code.")" ?></a></td>
			<td><?php echo number_format($group_assigned_amount->amount,2) ?></td>
			<td><?php echo $group_assigned_amount->status ?></td>

			<td><?php echo $group_assigned_amount->Firstname." ".$group_assigned_amount->Lastname ?></td>
			<td><?php echo $group_assigned_amount->date_disbursed ?></td>
					<td  width="500px">

						<a href="#" class="btn btn-danger" onclick="approve_group_amount('<?php echo $group_assigned_amount->gid; ?>','Active','Approve')">Approve group</a>
						<a href="#" onclick="approve_group_amount('<?php echo $group_assigned_amount->gid; ?>','Rejected','Reject')" class="btn btn-warning">Reject group</a>

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
