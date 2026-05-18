<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Funds Source</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="<?php echo base_url('funds_source')?>">Funds Sources</a>
				<span class="breadcrumb-item active">Funds Source Form</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<?php if ($this->session->flashdata('message')): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<?php echo $this->session->flashdata('message'); ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
			<?php endif; ?>
			
			<form action="<?php echo $action; ?>" method="post" class="form-row">
				<div class="form-group col-12">
					<label for="source_name">Source Name <span class="text-danger">*</span> <?php echo form_error('source_name') ?></label>
					<input type="text" class="form-control" name="source_name" id="source_name" placeholder="Enter source name" value="<?php echo $source_name; ?>" required />
				</div>
				
				<div class="form-group col-12">
					<label for="description">Description <?php echo form_error('description') ?></label>
					<textarea class="form-control" name="description" id="description" rows="4" placeholder="Enter description (optional)"><?php echo $description; ?></textarea>
				</div>

				<input type="hidden" name="funds_source" value="<?php echo $funds_source; ?>" /> 
				
				<div class="col-12">
					<button type="submit" class="btn btn-primary">
						<i class="anticon anticon-save"></i> <?php echo $button ?>
					</button> 
					<a href="<?php echo site_url('funds_source') ?>" class="btn btn-secondary">
						<i class="anticon anticon-arrow-left"></i> Cancel
					</a>
				</div>
			</form>
		</div>
	</div>
</div>