
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Individual customers</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All individual customers</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #24C16B solid;border-radius: 14px;">

        <table id="data-table" class="table">
            <thead>
			<tr>

		<th>ClientId</th>
		<th>Title</th>
		<th>Firstname</th>

		<th>Lastname</th>
		<th>Gender</th>
		<th>DateOfBirth</th>

		<th>CreatedOn</th>
		<th>Status</th>
		<th>Action</th>
			</tr>
            </thead>
			<tbody>
			<?php
            foreach ($individual_customers_data as $individual_customers)
            {
                ?>
                <tr>

			<td><?php echo $individual_customers->ClientId ?></td>
			<td><?php echo $individual_customers->Title ?></td>
			<td><?php echo $individual_customers->Firstname ?></td>

			<td><?php echo $individual_customers->Lastname ?></td>
			<td><?php echo $individual_customers->Gender ?></td>
			<td><?php echo $individual_customers->DateOfBirth ?></td>
			<td><?php echo $individual_customers->approval_status ?></td>

			<td><?php echo $individual_customers->CreatedOn ?></td>
			<td style="text-align:center" width="300px">
				<a href="<?php echo base_url('individual_customers/view/'.$individual_customers->id)?>" class="btn btn-info"><i class="os-icon os-icon-eye"></i>View</a>
                <?php
                if($individual_customers->approval_status == "Archived"){
                    ?>
                    <a href="<?php echo base_url('individual_customers/activate/'.$individual_customers->id)?>" onclick="return confirm('Are you sure you want to restore from archive ?')" class="btn btn-success"><i class="os-icon os-icon-trash"></i>Archive Restore </a>

                    <?php
                }else{
                ?>
                <a href="<?php echo base_url('individual_customers/delete/'.$individual_customers->id)?>" onclick="return confirm('Are you sure you want to archive this?')" class="btn btn-danger"><i class="os-icon os-icon-trash"></i>Archive</a>
                <?php
                }
                ?>
                <?php
                if($individual_customers->approval_status == "Blacklisted"){
                    ?>
                    <a href="<?php echo base_url('individual_customers/activate/'.$individual_customers->id)?>" onclick="return confirm('Are you sure you want to restore from blacklist ?')" class="btn btn-success"><i class="os-icon os-icon-trash"></i>Blacklist Restore </a>

                    <?php
                }else{
                ?>
                    <a href="<?php echo base_url('individual_customers/blacklist/'.$individual_customers->id)?>" onclick="return confirm('Are you sure you want to blacklist this customer?')" class="btn btn-orange"><i class="os-icon os-icon-trash"></i>Blacklist</a>


                    <?php
                }
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
