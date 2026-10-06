<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Funds Source Details</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="<?php echo base_url('funds_source')?>">Funds Sources</a>
				<span class="breadcrumb-item active">View Details</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<div class="row">
				<div class="col-md-8">
					<table class="table table-striped">
						<tr>
							<td><strong>ID</strong></td>
							<td><?php echo $funds_source; ?></td>
						</tr>
						<tr>
							<td><strong>Source Name</strong></td>
							<td><?php echo $source_name; ?></td>
						</tr>
						<tr>
							<td><strong>Description</strong></td>
							<td><?php echo $description ? $description : '<em>No description provided</em>'; ?></td>
						</tr>
						<tr>
							<td><strong>Date Added</strong></td>
							<td><?php echo date('d/m/Y H:i:s', strtotime($date_added)); ?></td>
						</tr>
						<tr>
							<td><strong>Added By</strong></td>
							<td><?php echo $added_by; ?></td>
						</tr>
					</table>
				</div>
			</div>
			
			<div class="row mt-3">
				<div class="col-12">
					<a href="<?php echo site_url('funds_source/update/'.$funds_source) ?>" class="btn btn-warning">
						<i class="anticon anticon-edit"></i> Edit
					</a>
					<a href="<?php echo site_url('funds_source') ?>" class="btn btn-secondary">
						<i class="anticon anticon-arrow-left"></i> Back to List
					</a>
					<a href="<?php echo site_url('funds_source/delete/'.$funds_source) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this funds source?')">
						<i class="anticon anticon-delete"></i> Delete
					</a>
				</div>
			</div>
		</div>
	</div>
</div>