<?php
$branches=get_all('branches');

?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Loan product</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Loan products form</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
        <form action="<?php echo $action; ?>" method="post" class="form-row">
	    <div class="form-group col-3">
            <label for="varchar">Product Name <?php echo form_error('product_name') ?></label>
            <input type="text" class="form-control" name="product_name" id="product_name" placeholder="Product Name" value="<?php echo $product_name; ?>" />
        </div>
	    <div class="form-group col-2">
            <label for="double">Interest <?php echo form_error('interest') ?></label>
            <input type="double" class="form-control" name="interest" id="interest" placeholder="Interest" value="<?php echo $interest; ?>" />
        </div>
			<div class="form-group col-2">
            <label for="double">Admin fees % <?php echo form_error('admin_fees ') ?></label>
            <input type="double" class="form-control" name="admin_fees" id="admin_fees" placeholder="admin_fees" value="<?php echo $admin_fees ; ?>" />
        </div>
			<div class="form-group col-2">
            <label for="double">Loan cover % <?php echo form_error('loan_cover') ?></label>
            <input type="double" class="form-control" name="loan_cover" id="loan_cover" placeholder="loan_cover" value="<?php echo $loan_cover; ?>" />
        </div>
        	<div class="form-group col-2">
            <label for="double">Penalty % <?php echo form_error('penalty') ?></label>
            <input type="double" class="form-control" name="penalty" id="penalty" placeholder="penalty" value="<?php echo $penalty; ?>" />
        </div>
	     <div class="form-group col-3">
            <label for="double">Frequency</label>
			 <select name="frequency" id="frequency" class="form-control">
				 <option value="">--select--</option>
				 <option value="Monthly" <?php if(isset($frequency) && $frequency==='Monthly'){echo 'selected';} ?>>Monthly</option>
				 <option value="Weekly" <?php if(isset($frequency) && $frequency==='Weekly'){echo 'selected';} ?>>Weekly</option>
				 <option value="Bi weekly" <?php if(isset($frequency) && $frequency==='Bi weekly'){echo 'selected';} ?>>Bi weekly</option>
				 <option value="2 Weeks" <?php if(isset($frequency) && $frequency==='2 Weeks'){echo 'selected';} ?>>2 Weeks</option>
			 </select>
                </div>
            <div class="form-group col-3">
                <label for="double">Branch</label>
                <select class="form-control select2" name="branch" id="branch">
                    <option value="">select</option>
                    <?php
                    foreach ($branches as $br){
                        ?>
                        <option value="<?php echo $br->Code ?>" <?php if($br->Code==$branch){echo "selected";}  ?>><?php echo $br->BranchName?></option>
                        <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-3">
                <label for="double">Product Code</label>
                <input type="text" class="form-control" name="product_code" id="product_code" placeholder="Product Code" value="<?php echo $product_code; ?>" />
            </div>
            <div class="form-group col-2">
                <label for="double">Processing fees %</label>
                <input type="text" class="form-control" name="processing_fees" id="processing_fees" placeholder="Processing fees" value="<?php echo isset($processing_fees) ? $processing_fees : ''; ?>" />
            </div>
            <div class="form-group col-2">
                <label for="varchar">Method</label>
                <select name="method" class="form-control">
                    <option value="">--select--</option>
                    <option value="Reducing balance" <?php if(isset($method) && $method==='Reducing balance'){echo 'selected';} ?>>Reducing balance</option>
                    <option value="Straight line" <?php if(isset($method) && $method==='Straight line'){echo 'selected';} ?>>Straight line</option>
                </select>
            </div>
            <div class="form-group col-2">
                <label>Min amount (MK)</label>
                <input type="text" class="form-control" name="min_amount" value="<?php echo isset($min_amount) ? $min_amount : ''; ?>" />
            </div>
            <div class="form-group col-2">
                <label>Max amount (MK)</label>
                <input type="text" class="form-control" name="max_amount" value="<?php echo isset($max_amount) ? $max_amount : ''; ?>" />
            </div>
            <div class="form-group col-2">
                <label>Min amount individual</label>
                <input type="text" class="form-control" name="min_amount_individual" value="<?php echo isset($min_amount_individual) ? $min_amount_individual : ''; ?>" />
            </div>
            <div class="form-group col-2">
                <label>Max amount individual</label>
                <input type="text" class="form-control" name="max_amount_individual" value="<?php echo isset($max_amount_individual) ? $max_amount_individual : ''; ?>" />
            </div>
            <div class="form-group col-2">
                <label>Min tenure (months)</label>
                <input type="number" class="form-control" name="min_tenure_months" value="<?php echo isset($min_tenure_months) ? $min_tenure_months : ''; ?>" />
            </div>
            <div class="form-group col-2">
                <label>Max tenure (months)</label>
                <input type="number" class="form-control" name="max_tenure_months" value="<?php echo isset($max_tenure_months) ? $max_tenure_months : ''; ?>" />
            </div>
            <div class="form-group col-4">
                <label>Allowed frequencies (optional)</label>
                <input type="text" class="form-control" name="allowed_frequencies" placeholder="Usually same as Frequency field" value="<?php echo isset($allowed_frequencies) ? $allowed_frequencies : ''; ?>" />
            </div>

	    <input type="hidden" name="loan_product_id" value="<?php echo $loan_product_id; ?>" />
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 

	</form>
		</div>
	</div>
</div>
