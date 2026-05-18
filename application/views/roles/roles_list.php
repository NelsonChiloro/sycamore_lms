
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Employee roles</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Details</a>
				<span class="breadcrumb-item active">Employee roles</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">


			<a href="<?php echo base_url('roles/create')?>" class="btn btn-sm btn-primary"><i class="anticon anticon-plus-square" style="color: white; font-size: 20px;"></i>Add role</a>

			<div class="m-t-25">
				<table id="data-table" class="table">
					<thead>
            <tr>

		<th>RoleName</th>
		<th>Action</th>
            </tr>
					</thead>
					<tbody>
			<?php
            foreach ($roles_data as $roles)
            {
                ?>
                <tr>

			<td><?php echo $roles->RoleName ?></td>
			<td style="text-align:center" width="200px">
				<?php 

				echo anchor(site_url('roles/update/'.$roles->id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('roles/delete/'.$roles->id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
				?>
			</td>
		</tr>
                <?php
            }
            ?>
					</tbody>
        </table>
		</div>
	</div>
</div>
