<?php
$customers = get_all_by_id('individual_customers','approval_status','Approved')
?>


<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Customer access config create</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Customer access config create</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick orange solid;border-radius: 14px;">
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Customer  <?php echo form_error('customer_id') ?></label>
            <select name="customer_id" required  >
                <option value="">--select customer</option>
                <?php

                foreach ($customers as $c){
                    ?>
                    <option value="<?php  echo  $c->id;?>"><?php echo $c->Firstname. " ".$c->Lastname ?></option>
                    <?php
                }
                ?>

        </div>

        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to give system access to this user?')">Create access</button>

	</form>
        </div>
    </div>
</div>
