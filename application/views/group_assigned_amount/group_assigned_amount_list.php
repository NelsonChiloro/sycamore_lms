<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Groups assigned amount</h2>
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
			<ul class="nav nav-tabs" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" data-toggle="tab" href="#tabs-1" role="tab">Pending approval Group amount</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#tabs-2" role="tab">Approved/active assigned Group amount</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#tabs-3" role="tab">Rejected Group amounts</a>
				</li>
			</ul><!-- Tab panes -->
			<div class="tab-content">
				<div class="tab-pane active m-t-25" id="tabs-1" role="tabpanel">
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
			$start =0;
            foreach ($group_assigned_amount_data as $group_assigned_amount)
            {
			if($group_assigned_amount->status=='Pending') {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $group_assigned_amount->group_name." (".$group_assigned_amount->group_code.")" ?></td>
			<td><?php echo number_format($group_assigned_amount->amount,2) ?></td>
			<td><?php echo $group_assigned_amount->status ?></td>

			<td><?php echo $group_assigned_amount->Firstname." ".$group_assigned_amount->Lastname ?></td>
			<td><?php echo $group_assigned_amount->date_disbursed ?></td>
			<td style="text-align:center" width="200px">
				<?php 

				echo anchor(site_url('group_assigned_amount/delete/'.$group_assigned_amount->id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
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
					<p style="font-weight: bolder; color: green;">Approved/Active group amount</p>
					<table class="table table-bordered" style="margin-bottom: 10px" id="d2">
						<thead>
						<tr>
							<th>No</th>
							<th>Group name/code</th>
							<th>Amount</th>
							<th>Status</th>
							<th>Comment</th>

							<th>Disbursed By</th>
							<th>Date Disbursed</th>

						</tr>
						</thead>
						<tbody>

						<?php
						$start =0;
						foreach ($group_assigned_amount_data as $group_assigned_amount)
						{
						if($group_assigned_amount->status=='Active') {
							?>
							<tr>
								<td width="80px"><?php echo ++$start ?></td>
								<td><?php echo $group_assigned_amount->group_name." (".$group_assigned_amount->group_code.")" ?></td>
								<td><?php echo number_format($group_assigned_amount->amount,2) ?></td>
								<td><?php echo $group_assigned_amount->status ?></td>
								<td><?php echo $group_assigned_amount->approval_comment ?></td>

								<td><?php echo $group_assigned_amount->Firstname." ".$group_assigned_amount->Lastname ?></td>
								<td><?php echo $group_assigned_amount->date_disbursed ?></td>

							</tr>
							<?php
						}
						}
						?>
						</tbody>
					</table>
				</div>
				<div class="tab-pane m-t-25" id="tabs-3" role="tabpanel">
					<p style="font-weight: bolder; color: green;">Rejected  group amount </p>
					<table class="table table-bordered" style="margin-bottom: 10px" id="d2">
						<thead>
						<tr>
							<th>No</th>
							<th>Group name/code</th>
							<th>Amount</th>
							<th>Status</th>
							<th>Comment</th>

							<th>Disbursed By</th>
							<th>Date Disbursed</th>

						</tr>
						</thead>
						<tbody>

						<?php
						$start =0;
						foreach ($group_assigned_amount_data as $group_assigned_amount)
						{
							if($group_assigned_amount->status=='Rejected') {
							?>
							<tr>
								<td width="80px"><?php echo ++$start ?></td>
								<td><?php echo $group_assigned_amount->group_name." (".$group_assigned_amount->group_code.")" ?></td>
								<td><?php echo number_format($group_assigned_amount->amount,2) ?></td>
								<td><?php echo $group_assigned_amount->status ?></td>
								<td><?php echo $group_assigned_amount->reject_comment ?></td>

								<td><?php echo $group_assigned_amount->Firstname." ".$group_assigned_amount->Lastname ?></td>
								<td><?php echo $group_assigned_amount->date_disbursed ?></td>

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
