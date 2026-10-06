<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">My Account</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Account</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
<div class="row">
	<div class="col-lg-6">
		<h3>My Profile</h3>
		<center>
			<img src="<?php echo base_url('uploads')?>/avatar-3.png" alt="" style="border-radius: 50px; border:  thick solid black;" height="100" width="100">

		</center>
		<table class="table">
			<tr>
				<td>First Name</td>
				<td>
					<?php


					echo $this->session->userdata('Firstname');?></td>
			</tr>
			<tr>
				<td>Last Name</td>
				<td><?php echo $this->session->userdata('Lastname');?></td>
			</tr>
			<tr>
				<td>Username</td>
				<td><?php echo $this->session->userdata('username');?></td>
			</tr>
			<tr>
				<td>Role</td>
				<td><?php echo $this->session->userdata('RoleName');?></td>
			</tr>
			<tr>
				<td>Date Joined</td>
				<td><?php echo $this->session->userdata('stamp');?></td>
			</tr>
		</table>
	</div>
	<div class="col-lg-6">
		<div style="border: thick solid green; border-radius: 15px;">
			<h2>Change Password</h2>
			<div style="margin-top: 8px; color: green;" id="message">
				<?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
			</div>
			<hr>
			<form action="<?php echo base_url('user_access/change_password_update')?>" method="post" style="padding: 1em;">
				<label for=""><?php echo form_error('old_pass') ?></label>
				<input type="password" name="old_pass" placeholder="Enter old password" style="border: thin solid blue;" size="40">
				<br>
				<br>
				<label for=""><?php echo form_error('new_pass') ?></label>
				<input type="password" name="new_pass" placeholder="Enter new password" style="border: thin solid blue;" size="40">
				<br>
				<br>
				<label for=""><?php echo form_error('confirm_pass') ?></label>
				<input type="password"  name="confirm_pass" placeholder="Confirm new password" style="border: thin solid blue;" size="40">
				<br>
				<br>
				<input type="submit" class="btn btn-danger" value="Submit">
			</form>
		</div>
	</div>
</div>
		</div>
	</div>
</div>
