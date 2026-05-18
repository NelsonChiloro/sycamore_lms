<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Automatic Payment config</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Allow Automatic payment</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">

        <h2 style="margin-top:0px">Auto payment <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">


			<div class="row">
				<div class="col-4">
					<div class="selector">
						<div class="selecotr-item">
							<input type="radio" id="radio1" name="customer_approval" class="selector-item_radio" value="Yes"  <?php echo ($repayment_automatic== 'Yes') ?  "checked" : "" ;  ?> >
							<label for="radio1" class="selector-item_label">Yes</label>
						</div>
						<div class="selecotr-item">
							<input type="radio" id="radio2" name="customer_approval" class="selector-item_radio" value="No" <?php echo ($repayment_automatic== 'No') ?  "checked" : "" ;  ?>  >
							<label for="radio2" class="selector-item_label">No</label>
						</div>

					</div>
				</div>
				<div class="col-8">
					<div class="form-group">
						<label for="varchar">Cron Path <?php echo form_error('cron_path') ?></label>
						<input type="text" class="form-control" name="cron_path" id="cron_path" placeholder="Cron Path" value="<?php echo $cron_path; ?>" />
					</div>
				</div>
			</div>


	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 

	</form>
		</div>
	</div>
</div>
