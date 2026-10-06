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
        <h2 style="margin-top:0px">Proofofidentity List</h2>
        <div class="row" style="margin-bottom: 10px">
            <div class="col-md-4">
                <?php echo anchor(site_url('proofofidentity/create'),'Create', 'class="btn btn-primary"'); ?>
            </div>
            <div class="col-md-4 text-center">
                <div style="margin-top: 8px" id="message">
                    <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                </div>
            </div>
            <div class="col-md-1 text-right">
            </div>
            <div class="col-md-3 text-right">
                <form action="<?php echo site_url('proofofidentity/index'); ?>" class="form-inline" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="<?php echo $q; ?>">
                        <span class="input-group-btn">
                            <?php 
                                if ($q <> '')
                                {
                                    ?>
                                    <a href="<?php echo site_url('proofofidentity'); ?>" class="btn btn-default">Reset</a>
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
		<th>IDType</th>
		<th>IDNumber</th>
		<th>IssueDate</th>
		<th>ExpiryDate</th>
		<th>DocImageURL</th>
		<th>Stamp</th>
		<th>ClientId</th>
		<th>Photograph</th>
		<th>Signature</th>
		<th>Id Back</th>
		<th>Id Front</th>
		<th>Action</th>
            </tr><?php
            foreach ($proofofidentity_data as $proofofidentity)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $proofofidentity->IDType ?></td>
			<td><?php echo $proofofidentity->IDNumber ?></td>
			<td><?php echo $proofofidentity->IssueDate ?></td>
			<td><?php echo $proofofidentity->ExpiryDate ?></td>
			<td><?php echo $proofofidentity->DocImageURL ?></td>
			<td><?php echo $proofofidentity->Stamp ?></td>
			<td><?php echo $proofofidentity->ClientId ?></td>
			<td><?php echo $proofofidentity->photograph ?></td>
			<td><?php echo $proofofidentity->signature ?></td>
			<td><?php echo $proofofidentity->Id_back ?></td>
			<td><?php echo $proofofidentity->id_front ?></td>
			<td style="text-align:center" width="200px">
				<?php 
				echo anchor(site_url('proofofidentity/read/'.$proofofidentity->id),'Read'); 
				echo ' | '; 
				echo anchor(site_url('proofofidentity/update/'.$proofofidentity->id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('proofofidentity/delete/'.$proofofidentity->id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
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