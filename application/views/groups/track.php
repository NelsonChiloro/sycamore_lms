<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Groups track and export</h2>
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
			<div>
				<?php
				$products = get_all('geo_countries');
				$officer = get_all('employees');

				?>
				<form action="<?php echo base_url('Groups/track') ?>" method="get">


					Status: <select name="status" id="">
						<option value="All">Any Status</option>
						<option value="Active" <?php if($this->input->get('status')=='Active'){echo "selected";} ?>>Active</option>
						<option value="Pending' <?php if($this->input->get('status')=='Pending'){echo "selected";} ?>">Pending</option>
						<option value="Approved" <?php if($this->input->get('status')=='Approved'){echo "selected";} ?>>Approved</option>
						<option value="Rejected" <?php if($this->input->get('status')=='Rejected'){echo "selected";} ?>>Rejected</option>
						<option value="Closed" <?php if($this->input->get('status')=='Closed'){echo "selected";} ?>>Closed</option>


					</select>
					Officer: <select name="user" id="" class="select2">
						<option value="All">All officers</option>
						<?php

						foreach ($officer as $item){
							?>
							<option value="<?php  echo $item->id; ?>" <?php if($this->input->get('user')==$item->id){echo "selected";} ?>><?php echo $item->Firstname." ".$item->Lastname ?></option>
							<?php
						}
						?>
					</select> Registered from:
					<input type="date" name="from" value="<?php  echo $this->input->get('from'); ?>"> Registered to: <input type="date" name="to" value="<?php  echo $this->input->get('to'); ?>"> <input type="submit" value="filter" name="search">
				</form>
			</div>
			<div class="m-t-25">
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
		</div>
	</div>
</div>
