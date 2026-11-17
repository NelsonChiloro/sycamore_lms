<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Branch</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Branches</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<a href="<?php echo base_url('Branches/create') ?>" class="btn btn-primary">Create Branch</a>
        <div class="table-responsive">
        <table id="data-table" class="table table-bordered" style="margin-bottom: 10px;width:100%">
			<thead>
            <tr>
                <th>No</th>
		<th>BranchCode</th>
		<th>BranchName</th>
		<th>AddressLine1</th>
		<th>AddressLine2</th>
		<th>City</th>
		<th>Stamp</th>
		<th>Action</th>
            </tr>
			</thead>
			<tbody><?php
            foreach ($branches_data as $branches)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo htmlspecialchars((string)($branches->BranchCode??''),ENT_QUOTES,'UTF-8'); ?></td>
			<td><?php echo htmlspecialchars((string)($branches->BranchName??''),ENT_QUOTES,'UTF-8'); ?></td>
			<td><?php echo htmlspecialchars((string)($branches->AddressLine1??''),ENT_QUOTES,'UTF-8'); ?></td>
			<td><?php echo htmlspecialchars((string)($branches->AddressLine2??''),ENT_QUOTES,'UTF-8'); ?></td>
			<td><?php echo htmlspecialchars((string)($branches->City??''),ENT_QUOTES,'UTF-8'); ?></td>
			<td><?php echo htmlspecialchars((string)($branches->Stamp??''),ENT_QUOTES,'UTF-8'); ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('branches/read/'.$branches->id),'Read'); 
				echo ' | '; 
				echo anchor(site_url('branches/update/'.$branches->id),'Update'); 
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
</div>
