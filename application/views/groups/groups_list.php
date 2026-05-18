<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Groups</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All groups</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick green solid;border-radius: 14px;">

			<div class="m-t-25">
				<ul class="nav nav-tabs" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" data-toggle="tab" href="#tabs-1" role="tab">Pending approval Groups</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" data-toggle="tab" href="#tabs-2" role="tab">Approved/active Groups</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" data-toggle="tab" href="#tabs-3" role="tab">Rejected Groups</a>
					</li>
				</ul><!-- Tab panes -->
				<div class="tab-content">
					<div class="tab-pane active m-t-25" id="tabs-1" role="tabpanel">
						<p style="font-weight: bolder; color: coral;">Groups Pending Approve</p>
						<table class="table table-bordered" id="data-table-arrears" >
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
									<td style="text-align:center" width="300px">
										<?php
										echo anchor(site_url('Customer_groups/manage/'.$groups->group_id),'Add group member');
										echo ' | ';
										echo anchor(site_url('groups/update/'.$groups->group_id),'Update');
										echo ' | ';
										echo anchor(site_url('groups/delete/'.$groups->group_id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"');
										?>
									</td>
								</tr>
								<?php

								}
							}
							?>
							</tbody>
						</table>
					</div>
					<div class="tab-pane m-t-25" id="tabs-2" role="tabpanel">
						<p style="font-weight: bolder; color: green;">Approved/Active groups</p>
						<table class="table table-bordered" id="data-table1" >
							<thead>
							<tr>

								<th>Group Code</th>
								<th>Group Name</th>
								<th>Branch</th>
								<th>Group Description</th>
								<th>Group Added By</th>
								<th>Group Registered Date</th>
								<th>Group Status</th>
								<th>comment</th>
								<th>Action</th>

							</tr>
							</thead>
							<tbody>
							<?php
							foreach ($groups_data as $groups)
							{
							if($groups->group_status == 'Active'){
								?>
								<tr>

									<td><?php echo $groups->group_code ?></td>
									<td><?php echo $groups->group_name ?></td>
									<td><?php echo $groups->BranchName ?></td>
									<td><?php echo $groups->group_description ?></td>
									<td><?php echo $groups->Firstname.' '.$groups->Lastname ?></td>
									<td><?php echo $groups->group_registered_date ?></td>
									<td style="color: green"><?php echo $groups->group_status ?></td>

									<td style="color: green"><?php echo $groups->approval_comment ?></td>
										<td>
											<?php
								echo anchor(site_url('Customer_groups/members/'.$groups->group_id),'View group member');

								?></td>
								</tr>
								<?php
							}
							}
							?>
							</tbody>
						</table>
					</div>
					<div class="tab-pane m-t-25" id="tabs-3" role="tabpanel">
						<p style="font-weight: bolder; color: red;">Rejected Groups</p>
						<table class="table table-bordered" id="data-table-collection" >
							<thead>
							<tr>

								<th>Group Code</th>
								<th>Group Name</th>
								<th>Branch</th>
								<th>Group Description</th>
								<th>Group Added By</th>
								<th>Group Registered Date</th>
								<th>Group Status</th>
								<th>comment</th>
								<th>Action</th>

							</tr>
							</thead>
							<tbody>
							<?php
							foreach ($groups_data as $groups)
							{
								if($groups->group_status == 'Rejected'){
									?>
									<tr>

										<td><?php echo $groups->group_code ?></td>
										<td><?php echo $groups->group_name ?></td>
										<td><?php echo $groups->BranchName ?></td>
										<td><?php echo $groups->group_description ?></td>
										<td><?php echo $groups->Firstname.' '.$groups->Lastname ?></td>
										<td><?php echo $groups->group_registered_date ?></td>
										<td style="color: red"><?php echo $groups->group_status ?></td>
										<td style="color: red"><?php echo $groups->reject_comment ?></td>
										<td><?php
											echo anchor(site_url('Customer_groups/members/'.$groups->group_id),'View group member');

											?></td>
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
	</div>
</div>
