<!doctype html>
<html>
    <head>
        <title>harviacode.com - codeigniter crud generator</title>
        <link rel="stylesheet" href="<?php echo base_url('assets/bootstrap/css/bootstrap.min.css') ?>"/>
        <style>
            body{
                padding: 15px;
            }
        </style>
    </head>
    <body>
        <h2 style="margin-top:0px">Customer_access List</h2>
        <div class="row" style="margin-bottom: 10px">
            <div class="col-md-4">
                <?php echo anchor(site_url('customer_access/create'),'Create', 'class="btn btn-primary"'); ?>
            </div>
            <div class="col-md-4 text-center">
                <div style="margin-top: 8px" id="message">
                    <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                </div>
            </div>
            <div class="col-md-1 text-right">
            </div>
            <div class="col-md-3 text-right">
                <form action="<?php echo site_url('customer_access/index'); ?>" class="form-inline" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="<?php echo $q; ?>">
                        <span class="input-group-btn">
                            <?php 
                                if ($q <> '')
                                {
                                    ?>
                                    <a href="<?php echo site_url('customer_access'); ?>" class="btn btn-default">Reset</a>
                                    <?php
                                }
                            ?>
                          <button class="btn btn-primary" type="submit">Search</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
        <table class="table table-bordered" style="margin-bottom: 10px">
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
            </tr><?php
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
				<?php 
				echo anchor(site_url('customer_access/read/'.$customer_access->id),'Read'); 
				echo ' | '; 
				echo anchor(site_url('customer_access/update/'.$customer_access->id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('customer_access/delete/'.$customer_access->id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
				?>
			</td>
		</tr>
                <?php
            }
            ?>
        </table>
        <div class="row">
            <div class="col-md-6">
                <a href="#" class="btn btn-primary">Total Record : <?php echo $total_rows ?></a>
	    </div>
            <div class="col-md-6 text-right">
                <?php echo $pagination ?>
            </div>
        </div>
    </body>
</html>