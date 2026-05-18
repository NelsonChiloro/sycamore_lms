<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Customer access config to Approve</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Customer access config to approve</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick orange solid;border-radius: 14px;">

            <table id="data-table" class="table table-bordered" style="margin-bottom: 10px">
                <thead></thead>
                <tr>
                    <th>No</th>
                    <th>Customer Id</th>
                    <th>Phone Number</th>
                    <th>Created By</th>
                    <th>Approved By</th>
                    <th>Denied By</th>
                    <th>Status</th>
                    <th>Stamp</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>

                <?php
                $start =0;
                foreach ($customer_access_data as $customer_access)
                {
                    ?>
                    <tr>
                        <td width="80px"><?php echo ++$start ?></td>
                        <td><?php echo $customer_access->customer_id ?></td>
                        <td><?php echo $customer_access->phone_number ?></td>
                        <td><?php echo $customer_access->created_by ?></td>
                        <td><?php echo $customer_access->approved_by ?></td>
                        <td><?php echo $customer_access->denied_by ?></td>
                        <td><?php echo $customer_access->status ?></td>
                        <td><?php echo $customer_access->stamp ?></td>
                        <td style="text-align:center" width="200px">
                            <a href="<?php echo base_url('Customer_access/deactivate/').$customer_access->id?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to approve this?')">Deactivate</a>


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
