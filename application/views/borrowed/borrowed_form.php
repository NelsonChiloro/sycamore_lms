<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Borrowed Money form</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active"> Borrowed Money form</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="decimal">Amount <?php echo form_error('amount') ?></label>
            <input type="text" class="form-control" name="amount" id="amount" placeholder="Amount" value="<?php echo $amount; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Total Interest <?php echo form_error('total_interest') ?></label>
            <input type="text" class="form-control" name="total_interest" id="total_interest" placeholder="Total Interest" value="<?php echo $total_interest; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Borrowed From <?php echo form_error('borrowed_from') ?></label>
            <input type="text" class="form-control" name="borrowed_from" id="borrowed_from" placeholder="Borrowed From" value="<?php echo $borrowed_from; ?>" />
        </div>
	    <div class="form-group">
            <label for="date">Date Borrowed <?php echo form_error('date_borrowed') ?></label>
            <input type="date" class="form-control" name="date_borrowed" id="date_borrowed" placeholder="Date Borrowed" value="<?php echo $date_borrowed; ?>" />
        </div>

	    <input type="hidden" name="borrowed_id" value="<?php echo $borrowed_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('borrowed') ?>" class="btn btn-default">Cancel</a>
	</form>
		</div>
	</div>
</div>
