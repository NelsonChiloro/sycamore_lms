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
        <h2 style="margin-top:0px">Collateral List</h2>
        <div class="row" style="margin-bottom: 10px">
            <div class="col-md-4">
                <?php echo anchor(site_url('collateral/create'),'Create', 'class="btn btn-primary"'); ?>
            </div>
            <div class="col-md-4 text-center">
                <div style="margin-top: 8px" id="message">
                    <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                </div>
            </div>
            <div class="col-md-1 text-right">
            </div>
            <div class="col-md-3 text-right">
                <form action="<?php echo site_url('collateral/index'); ?>" class="form-inline" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="<?php echo $q; ?>">
                        <span class="input-group-btn">
                            <?php 
                                if ($q <> '')
                                {
                                    ?>
                                    <a href="<?php echo site_url('collateral'); ?>" class="btn btn-default">Reset</a>
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
		<th>Loan Id</th>
		<th>Collateral Name</th>
		<th>Collateral Type</th>
		<th>Serial</th>
		<th>Estimated Price</th>
		<th>Attachement</th>
		<th>Description</th>
		<th>Date Added</th>
		<th>Added By</th>
		<th>Action</th>
            </tr><?php
            foreach ($collateral_data as $collateral)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $collateral->loan_id ?></td>
			<td><?php echo $collateral->collateral_name ?></td>
			<td><?php echo $collateral->collateral_type ?></td>
			<td><?php echo $collateral->serial ?></td>
			<td><?php echo $collateral->estimated_price ?></td>
			<td><?php echo $collateral->attachement ?></td>
			<td><?php echo $collateral->description ?></td>
			<td><?php echo $collateral->date_added ?></td>
			<td><?php echo $collateral->added_by ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('collateral/read/'.$collateral->collateral_id),'Read'); 
				echo ' | '; 
				echo anchor(site_url('collateral/update/'.$collateral->collateral_id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('collateral/delete/'.$collateral->collateral_id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
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