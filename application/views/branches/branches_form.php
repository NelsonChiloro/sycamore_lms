<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Branch</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Branche form</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
        <form action="<?php echo $action; ?>" method="post" class="form-row">
	    <div class="form-group col-6">
            <label for="int">BranchCode <?php echo form_error('BranchCode') ?></label>
            <input type="text" class="form-control" name="BranchCode" id="BranchCode" placeholder="BranchCode" value="<?php echo $BranchCode; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="varchar">BranchName <?php echo form_error('BranchName') ?></label>
            <input type="text" class="form-control" name="BranchName" id="BranchName" placeholder="BranchName" value="<?php echo $BranchName; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="varchar">AddressLine1 <?php echo form_error('AddressLine1') ?></label>
            <input type="text" class="form-control" name="AddressLine1" id="AddressLine1" placeholder="AddressLine1" value="<?php echo $AddressLine1; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="varchar">AddressLine2 <?php echo form_error('AddressLine2') ?></label>
            <input type="text" class="form-control" name="AddressLine2" id="AddressLine2" placeholder="AddressLine2" value="<?php echo $AddressLine2; ?>" />
        </div>
	    <div class="form-group col-12">
            <label for="varchar">City <?php echo form_error('City') ?></label>
            <input type="text" class="form-control" name="City" id="City" placeholder="City" value="<?php echo $City; ?>" />
        </div>

	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('branches') ?>" class="btn btn-default">Cancel</a>
	</form>
		</div>
	</div>
</div>
