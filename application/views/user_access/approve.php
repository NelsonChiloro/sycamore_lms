<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">User access</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">Access</a>
                <span class="breadcrumb-item active">User access list</span>
            </nav>
        </div>
    </div>
	<div class="card">
        <div class="card-body" style="border: thick #24C16B  solid;border-radius: 14px;">

            <?php echo anchor(site_url('user_access/create'),'Configure user', 'class="btn btn-primary"'); ?>

            <table id="data-table" class="table">
                <thead>
                <tr>

                    <th>Username</th>
                    <th>Employee</th>
                    <th>Session</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($user_access_data as $user_access)
                {
                    if($user_access->Status=="AUTHORIZED"){}
                    else{
                    ?>
                    <tr>

                        <td><?php echo $user_access->AccessCode ?></td>
                        <td><?php echo $user_access->Firstname ." ".$user_access->Firstname ?></td>
                        <td><?php if($user_access->is_logged_in=="Yes"){ echo "<font color='green'>Yes</font>";}else{echo "<font color='yellow'>No</font>";} ?></td>
                        <td><?php echo $user_access->Status ?></td>
                        <td style="text-align:center" width="400px">
                            <a href="<?php echo base_url('user_access/approved/'.$user_access->Employee)?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to authorise this?')"><i class="os-icon os-icon-pencil-12"></i>authorise</a>
                            <a href="<?php echo base_url('user_access/reject/'.$user_access->Employee)?>" class="btn btn-warning" onclick="return confirm('Are you sure you want to reject this?')"><i class="os-icon os-icon-pencil-12"></i>Reject</a>

                        </td>
                    </tr>
                    <?php
                    }
                }
                ?>
                </tbody>
            </table>

        </div>
    </div>
</div>
