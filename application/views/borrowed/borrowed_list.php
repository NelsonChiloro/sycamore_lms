<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Borrowed Money</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All Borrowed Money</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<a href="<?php echo base_url('Borrowed/create') ?>" class="btn btn-primary">Add Borrowed money</a>
			<table class="table table-bordered" id="data-table" >
				<thead>
            <tr>
                <th>No</th>
		<th>Amount</th>
		<th>Total Interest</th>
		<th>Borrowed From</th>
		<th>Date Borrowed</th>
		<th>Stamp</th>
		<th>Action</th>
            </tr>
				</thead>
				<body>
			<?php
			$start = 1;
            foreach ($borrowed_data as $borrowed)
            {
                ?>
                <tr>
			<td width="80px"><?php echo $start ?></td>
			<td><?php echo number_format( $borrowed->amount,2 ) ?></td>
			<td><?php echo number_format($borrowed->total_interest,2) ?></td>
			<td><?php echo $borrowed->borrowed_from ?></td>
			<td><?php echo $borrowed->date_borrowed ?></td>
			<td><?php echo $borrowed->stamp ?></td>
			<td style="text-align:center" width="200px">
				<?php
				echo anchor(site_url('Borrowed_repayements/index/'.$borrowed->borrowed_id),'manage');
				echo ' | ';
				echo anchor(site_url('borrowed/update/'.$borrowed->borrowed_id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('borrowed/delete/'.$borrowed->borrowed_id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
				?>
			</td>
		</tr>
                <?php
				$start ++;
            }
            ?>
				</body>
        </table>
		</div>
	</div>
</div>
