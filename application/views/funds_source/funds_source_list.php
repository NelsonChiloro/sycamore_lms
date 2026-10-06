<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Funds Sources</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Funds Sources</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<?php if ($this->session->flashdata('message')): ?>
				<div class="alert alert-info alert-dismissible fade show" role="alert">
					<?php echo $this->session->flashdata('message'); ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
			<?php endif; ?>
			
			<a href="<?php echo base_url('funds_source/create') ?>" class="btn btn-primary">
				<i class="anticon anticon-plus"></i> Create Funds Source
			</a>
			
			<!-- Search Form -->
			<form action="<?php echo site_url('funds_source'); ?>" method="get" style="margin-top: 10px;">
				<div class="row">
					<div class="col-md-4">
						<input type="text" class="form-control" name="q" value="<?php echo $q; ?>" placeholder="Search...">
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-secondary">Search</button>
					</div>
				</div>
			</form>
			
			<table id="data-table" class="table table-bordered" style="margin-top: 15px;">
				<thead>
					<tr>
						<th>No</th>
						<th>Source Name</th>
						<th>Description</th>
						<th>Date Added</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($funds_source_data as $funds_source)
					{
						?>
						<tr>
							<td width="80px"><?php echo ++$start ?></td>
							<td><?php echo $funds_source->source_name ?></td>
							<td><?php echo $funds_source->description ?></td>
							<td><?php echo date('d/m/Y H:i', strtotime($funds_source->date_added)) ?></td>
							<td style="text-align:center" width="200px">
								<?php 
								echo anchor(site_url('funds_source/read/'.$funds_source->funds_source),'<i class="anticon anticon-eye"></i> Read', 'class="btn btn-info btn-sm"'); 
								echo ' '; 
								echo anchor(site_url('funds_source/update/'.$funds_source->funds_source),'<i class="anticon anticon-edit"></i> Update', 'class="btn btn-warning btn-sm"'); 
								echo ' '; 
								echo anchor(site_url('funds_source/delete/'.$funds_source->funds_source),'<i class="anticon anticon-delete"></i> Delete', 'class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this funds source?\')"'); 
								?>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			
			<div class="row">
				<div class="col-md-6">
					<div class="pagination-info">
						Total: <?php echo $total_rows; ?> records
					</div>
				</div>
				<div class="col-md-6 text-right">
					<?php echo $pagination; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
    $('#data-table').DataTable({
        "paging": false,
        "searching": false,
        "info": false
    });
});
</script>