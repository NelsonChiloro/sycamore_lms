<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Group view</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Group Details</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
        <table class="table">
	    <tr><td>Group Code</td><td><?php echo $group_code; ?></td></tr>
	    <tr><td>Group Name</td><td><?php echo $group_name; ?></td></tr>
	    <tr><td>Group Business type</td><td><?php echo $group_category; ?></td></tr>
	    <tr><td>Group physical Address</td><td><?php echo $physical_address; ?></td></tr>
	    <tr><td>Group contact Address</td><td><?php echo $group_category; ?></td></tr>
	    <tr><td>Branch</td><td><?php echo $branch; ?></td></tr>
	    <tr><td>Group Description</td><td><?php echo $group_description; ?></td></tr>
	    <tr><td>Group Added By</td><td><?php echo $group_added_by; ?></td></tr>
	    <tr><td>Group Registered Date</td><td><?php echo $group_registered_date; ?></td></tr>

	</table>
        </div>
    </div>
</div>