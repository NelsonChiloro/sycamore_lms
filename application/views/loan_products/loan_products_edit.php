<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Loan product</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Loan products</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<h5>Loan product list  editable</h5>
        <table  id="data-table" class="table table-bordered" style="margin-bottom: 10px">
			<thead>
            <tr>

		<th>Product Name</th>
		<th>Interest</th>
		<th>Admin fee %</th>
		<th>Loan cover %</th>
			<th>Penalty%</th>
		<th>Frequency</th>
		
		<th>Date Created</th>
		<th>Action</th>
            </tr>
			</thead>
			<tbody>
			<?php
            foreach ($loan_products_data as $loan_products)
            {
                ?>
                <tr>

			<td><?php echo $loan_products->product_name.'('.$loan_products->product_code.')'  ?></td>
			<td><?php echo $loan_products->interest ?></td>
			<td><?php echo $loan_products->admin_fees ?></td>
			<td><?php echo $loan_products->loan_cover ?></td>
				<td><?php echo $loan_products->penalty ?></td>
			<td><?php echo $loan_products->frequency ?></td>
			
			<td><?php echo $loan_products->date_created ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('loan_products/update/'.$loan_products->loan_product_id),'Update');
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
