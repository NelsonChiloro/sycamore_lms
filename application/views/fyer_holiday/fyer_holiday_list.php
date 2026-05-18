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
        <h2 style="margin-top:0px">Fyer_holiday List</h2>
        <div class="row" style="margin-bottom: 10px">
            <div class="col-md-4">
                <?php echo anchor(site_url('fyer_holiday/create'),'Create', 'class="btn btn-primary"'); ?>
            </div>
            <div class="col-md-4 text-center">
                <div style="margin-top: 8px" id="message">
                    <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                </div>
            </div>
            <div class="col-md-1 text-right">
            </div>
            <div class="col-md-3 text-right">
                <form action="<?php echo site_url('fyer_holiday/index'); ?>" class="form-inline" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="<?php echo $q; ?>">
                        <span class="input-group-btn">
                            <?php 
                                if ($q <> '')
                                {
                                    ?>
                                    <a href="<?php echo site_url('fyer_holiday'); ?>" class="btn btn-default">Reset</a>
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
		<th>Fyr Id</th>
		<th>Date</th>
		<th>Holiday Description</th>
		<th>Date Added</th>
		<th>Action</th>
            </tr><?php
            foreach ($fyer_holiday_data as $fyer_holiday)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $fyer_holiday->fyr_id ?></td>
			<td><?php echo $fyer_holiday->date ?></td>
			<td><?php echo $fyer_holiday->holiday_description ?></td>
			<td><?php echo $fyer_holiday->date_added ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('fyer_holiday/read/'.$fyer_holiday->fyer_id),'Read'); 
				echo ' | '; 
				echo anchor(site_url('fyer_holiday/update/'.$fyer_holiday->fyer_id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('fyer_holiday/delete/'.$fyer_holiday->fyer_id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
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