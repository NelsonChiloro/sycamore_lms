<?php
$linkk = base_url('admin_assets/images/pattern.png');
$imgg = 'data:image;base64,'.base64_encode(file_get_contents($linkk))
?>
<style>
	#pattern-style-a
	{
		font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
		font-size: 12px;
		width: 100%;
		text-align: left;
		border-collapse: collapse;
		background: url('<?php echo $imgg; ?>');;
	}
</style>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Groups members</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All groups</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<a href="#" class="btn btn-primary" onclick="add_group_member('<?php echo $group_id ?>')">Add member to this group</a><a href="<?php  echo base_url('Customer_groups/print_group/').$group_id?>" class="btn btn-danger" ><i class="fa fa-file-pdf"></i>Export</a>
			<div class="m-t-25">
                <table id="pattern-style-a">
                    <tr>
                        <td colspan="2">
                            <table>
                                <tr><td width="40%">Group Name:</td><td><strong><?php  echo  $group->group_name;?></strong></td></tr>
                                <tr><td width="40%">Group Code:</td><td><strong><?php  echo  $group->group_code;?></strong></td></tr>
                                <tr><td>Business type:</td><td><strong><?php echo $group->group_category; ?></strong></td></tr>
                               

                            </table>
                        </td>
                        <td colspan="4">
                        </td>



                        <td colspan="2">
                            <table>
                                <tr><td>Group Description :</td><td><strong><?php echo $group->group_description; ?></strong></td></tr>
                                <tr><td>Registered date:</td><td><strong><?php echo $group->group_registered_date; ?></strong></td></tr>
                                <tr><td>Branch:</td><td><strong><?php echo $group->BranchName; ?></strong></td></tr>
                                <tr><td>Added by:</td><td><strong><?php echo $group->Firstname." ".$group->Lastname; ?></strong></td></tr>

                            </table>
                        </td>
                    </tr>
                </table>
				<br>
				<br>
				<br>
				<hr>
				<table class="table table-bordered" id="data-table" >
					<thead>
            <tr>
                <th>No</th>
		<th>Customer</th>

		<th>Date Joined</th>
		<th>Action</th>
            </tr>
					</thead>
					<tbody>
			<?php
			$start = 1;
            foreach ($customer_groups_data as $customer_groups)
            {
                ?>
                <tr>
			<td width="80px"><?php echo $start ?></td>
			<td><a href="<?php  echo base_url('individual_customers/view/').$customer_groups->id?>"><?php echo $customer_groups->Firstname.' '.$customer_groups->Lastname ?></a></td>

			<td><?php echo $customer_groups->date_joined ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('customer_groups/delete/'.$customer_groups->customer_group_id),'Remove','onclick="javasciprt: return confirm(\'Are You Sure ?\')"');
				?>
			</td>
		</tr>
                <?php
				$start ++;
            }
            ?>
					</tbody>
        </table>

			</div>
		</div>
	</div>
</div>
