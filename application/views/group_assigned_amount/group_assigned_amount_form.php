<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Assign Groups  amount</h2>
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
			<?php

			?>
        <form action="<?php echo $action; ?>" method="post" class="form-row">
	    <div class="form-group col-6">
            <label for="int">Group Id <?php echo form_error('group_id') ?></label>
			<select class="form-control loan" name="group_id">
				<option value="">--select group--</option>
				<?php

				foreach ($groups as $group){
					?>
					<option value="<?php  echo $group->group_id ?>"><?php  echo $group->group_name. "(".$group->group_code.")" ; ?></option>
				<?php
				}
				?>
			</select>

        </div>
	    <div class="form-group col-6">
            <label for="decimal">Amount <?php echo form_error('amount') ?></label>
            <input type="text" class="form-control" name="amount" id="amount" placeholder="Amount" value="<?php echo $amount; ?>" />
        </div>


	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 

	</form>
		</div>
	</div>
</div>
