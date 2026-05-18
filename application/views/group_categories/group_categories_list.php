
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Group</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">groups categories</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
        <table id="data-table" class="table table-bordered" style="margin-bottom: 10px">
			<head>
            <tr>

		<th>Group Category Name</th>
		<th>Action</th>
            </tr>
			</head>
			<body>
				<?php
            foreach ($group_categories_data as $group_categories)
            {
                ?>
                <tr>

			<td><?php echo $group_categories->group_category_name ?></td>
			<td style="text-align:center" width="200px">
				<?php 

				echo anchor(site_url('group_categories/update/'.$group_categories->group_category_id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('group_categories/delete/'.$group_categories->group_category_id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
				?>
			</td>
		</tr>
                <?php
            }
            ?>
			</body>
        </table>
		</div>
	</div>
</div>
