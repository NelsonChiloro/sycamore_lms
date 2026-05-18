<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Employee roles</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">All</a>
				<span class="breadcrumb-item active">System users roles</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14
        <h2 style="margin-top:0px">Roles <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">RoleName <?php echo form_error('RoleName') ?></label>
            <input type="text" class="form-control" name="RoleName" id="RoleName" placeholder="RoleName" value="<?php echo $RoleName; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('roles') ?>" class="btn btn-default">Cancel</a>
	</form>
	</div>
</div>
