<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Backups</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Back up actions</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
						<a href="<?php  echo base_url('Backup/backupdb')?>" class="btn btn-primary"><i class="os-icon os-icon-database"></i>Backup Database</a>
						<a href="<?php  echo base_url('Backup/files')?>" class="btn btn-default"><i class="os-icon os-icon-download-cloud"></i>Backup Files</a>
		</div>
	</div>
</div>
