<div class="content-i">
	<div class="content-box">
		<div class="element-wrapper">
			<h6 class="element-header">Customers</h6>
			<div class="element-box">
        <h2 style="margin-top:0px">Corporate_customers List</h2>
        <div class="row" style="margin-bottom: 10px">
            <div class="col-md-4">
                <?php echo anchor(site_url('corporate_customers/create'),'Create', 'class="btn btn-primary"'); ?>
            </div>
            <div class="col-md-4 text-center">
                <div style="margin-top: 8px" id="message">
                    <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                </div>
            </div>
            <div class="col-md-1 text-right">
            </div>
            <div class="col-md-3 text-right">
                <form action="<?php echo site_url('corporate_customers/index'); ?>" class="form-inline" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="<?php echo $q; ?>">
                        <span class="input-group-btn">
                            <?php 
                                if ($q <> '')
                                {
                                    ?>
                                    <a href="<?php echo site_url('corporate_customers'); ?>" class="btn btn-default">Reset</a>
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
		<th>EntityName</th>

		<th>Status</th>
		<th>DoesQualify</th>

		<th>CreatedOn</th>
		<th>Action</th>
            </tr><?php
            foreach ($corporate_customers_data as $corporate_customers)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $corporate_customers->EntityName ?></td>

			<td><?php echo $corporate_customers->Status ?></td>
			<td><?php echo $corporate_customers->DoesQualify ?></td>

			<td><?php echo $corporate_customers->CreatedOn ?></td>
			<td style="text-align:center" width="200px">
				<a href="<?php echo base_url('corporate_customers/read/'.$corporate_customers->id) ?>" class="btn btn-info"><i class="os-icon os-icon-eye"></i> More</a>
				<a href="<?php echo base_url('corporate_customers/update/'.$corporate_customers->id) ?>" class="btn btn-success"><i class="os-icon os-icon-pencil-12"></i>Edit</a>

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
			</div>
		</div>
	</div>
</div>
