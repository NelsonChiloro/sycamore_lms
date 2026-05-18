<?php
$em = $this->Employees_model->get_all();
?>
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
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Password <?php echo form_error('Password') ?></label>
            <input type="password" class="form-control" name="Password" id="Password" placeholder="Password" value="<?php echo $Password; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Employee <?php echo form_error('Employee') ?></label>
			<select class="form-control" name="Employee" id="Employee">
				<option value="">--select employee--</option>
				<?php

				foreach ($em as $value){
					?>
					<option value="<?php echo $value->empid?>"><?php echo $value->Firstname.' '. $value->Lastname."(".$value->RoleName.")"?></option>
				<?php
				}
				?>
			</select>

        </div>

	    <input type="hidden" name="AccessCode" value="<?php echo $AccessCode; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('user_access') ?>" class="btn btn-default">Cancel</a>
	</form>
		</div>
	</div>
</div>
