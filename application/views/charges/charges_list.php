<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Charges</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All charges</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<table class="table table-bordered" id="data-table" >
				<thead>
            <tr>
                <th>No</th>
		<th>Name</th>
		<th>Charge Type</th>
		<th>Fixed Amount</th>
		<th>Variable Value percentage</th>
		<th>Action</th>
            </tr>
				</thead>
				<tbody>

			<?php
			$start = 0;
            foreach ($charges_data as $charges)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $charges->name ?></td>
			<td><?php echo $charges->charge_type ?></td>
			<td class="<?php if($charges->charge_type=='Fixed'){echo 'btn-success';}  ?>"><?php echo $charges->fixed_amount ?></td>
			<td class="<?php if($charges->charge_type=='Variable'){echo 'btn-success';}  ?>"><?php echo $charges->variable_value ?></td>
			<td style="text-align:center" width="200px">
				<?php 

				echo anchor(site_url('charges/update/'.$charges->charge_id),'Update'); 

				?>
			</td>
		</tr>
                <?php
            }
            ?>
				</tbody>

			</table>
