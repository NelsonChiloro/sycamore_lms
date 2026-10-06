<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Account types</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>

            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
        <h2 style="margin-top:0px">Account_types <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Account Type Name <?php echo form_error('account_type_name') ?></label>
            <input type="text" class="form-control" name="account_type_name" id="account_type_name" placeholder="Account Type Name" value="<?php echo $account_type_name; ?>" />
        </div>
	   
	    <input type="hidden" name="account_type_id" value="<?php echo $account_type_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('account_types') ?>" class="btn btn-default">Cancel</a>
	</form>
        </div>
    </div>
</div>
