<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">All Account types</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>

            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <a href="<?php echo base_url('Account_types/create')?>" class="btn btn-primary">Create Account type</a>
            <br>
            <br>
            <table  id="data-table" class="table">
        <thead>
            <tr>
                <th>No</th>
		<th>Account Type Name</th>
		<th>Type</th>
		<th>Date Added</th>
		<th>Action</th>
            </tr>
        </thead>
                <tbody>
            <?php
            foreach ($account_types_data as $account_types)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $account_types->account_type_name ?></td>
			<td><?php echo $account_types->type ?></td>
			<td><?php echo $account_types->date_added ?></td>
			<td style="text-align:center" width="200px">
				<?php 

				echo anchor(site_url('account_types/update/'.$account_types->account_type_id),'Update'); 
				echo ' | ';
				if($account_types->type=='system_generated'){

                }else {
                    echo anchor(site_url('account_types/delete/' . $account_types->account_type_id), 'Delete', 'onclick="javasciprt: return confirm(\'Are You Sure ?\')"');
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
