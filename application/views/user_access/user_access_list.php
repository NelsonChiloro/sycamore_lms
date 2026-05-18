<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">User access</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Access</a>
				<span class="breadcrumb-item active">User access list</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #24C16B  solid;border-radius: 14px;">

                <?php echo anchor(site_url('user_access/create'),'Configure user', 'class="btn btn-primary"'); ?>

			<table id="data-table" class="table">
			<thead>
            <tr>

				<th>Username</th>
				<th>Email</th>
		<th>Employee name</th>
		<th>In Session</th>
		<th>Status</th>
		<th>Action</th>
            </tr>
			</thead>
			<tbody>
			<?php
            foreach ($user_access_data as $user_access)
            {
                ?>
                <tr>

					<td><?php echo $user_access->AccessCode ?></td>
			<td><?php echo $user_access->EmailAddress ?></td>
			<td><?php echo $user_access->Firstname ." ".$user_access->Firstname ?></td>
			<td><?php if($user_access->is_logged_in=="Yes"){ echo "<font color='green'>Yes</font>";}else{echo "<font color='red'>No</font>";} ?></td>
			<td><?php echo $user_access->Status ?></td>
			<td style="text-align:center" width="400px">
				<?php

				if($user_access->is_logged_in=="Yes"){

					?>
					<a href="<?php echo base_url('user_access/cancel/'.$user_access->Employee)?>" class="btn btn-danger" onclick="return confirm(' Are you sure you want to close this session, all activity in progress will be lost')"><i class="os-icon os-icon-cancel-circle"></i>Close Session</a>
				<?php
				}
				?>
                <?php if($user_access->Status=="AUTHORIZED"){

					?>
					<a href="<?php echo base_url('user_access/reset_pass/'.$user_access->Employee)?>" class="btn btn-warning" onclick="return confirm(' Are you sure you want to reset the password? The new password will be sent to users Email')"><i class="fa fa-key"></i>Reset Password</a>
				<?php
				}
				?> <?php if($user_access->Status=="BLOCKED"){

					?>
					<a href="<?php echo base_url('user_access/unblock/'.$user_access->Employee)?>" class="btn btn-warning" style="background-color: orange !important; color: white;" ><i class="os-icon os-icon-check"></i>Unblock user</a>
					<?php
				}else{
					?>
					<a href="<?php echo base_url('user_access/block/'.$user_access->Employee)?>" class="btn btn-danger" ><i class="os-icon os-icon-trash"></i>Block user</a>


					<?php


				}
				?>
					

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
