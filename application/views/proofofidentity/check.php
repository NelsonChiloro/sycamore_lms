<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">customer KYC</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Customer KYC</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
						<a href="<?php echo base_url('Individual_customers/read/'.$this->uri->segment(3))?>" class="btn btn-success">View details</a>	<a href="<?php echo base_url('Individual_customers/products/'.$this->uri->segment(3))?>" class="btn btn-warning">Products</a>	<a href="<?php echo base_url('Proofofidentity/check/').$this->uri->segment(3)?>" class="btn btn-primary" >Customer KYC</a>	<a href="<?php echo base_url('Individual_customers/update/').$this->uri->segment(3) ?>" class="btn btn-success">Edit Customer</a><a href="" class="btn btn-warning">Deactivate Customer</a>
						<br/>
						<input type="text" id="cid" value="<?php echo $ClientId?>" hidden>
						<?php
						if($id ==""){
							echo "<font color='red'>No KYC added </font> <a href='#' id='add_kyc'>click here</a> <font color='red'>To add</font> ";
						}else{


						?>
							<table class="table">
								<tr><td>IDType</td><td><?php echo $IDType; ?></td></tr>
								<tr><td>IDNumber</td><td><?php echo $IDNumber; ?></td></tr>
								<tr><td>IssueDate</td><td><?php echo $IssueDate; ?></td></tr>
								<tr><td>ExpiryDate</td><td><?php echo $ExpiryDate; ?></td></tr>
								<tr><td>Date Added </td><td><?php echo $Stamp; ?></td></tr>
								<tr><td>ClientId</td><td><?php echo $ClientId; ?></td></tr>
								<tr><td>Photograph</td><td><img src="<?php echo base_url('uploads/').$photograph?>" alt="" height="200" width="200"></td></tr>
								<tr><td>Signature</td><td><img src="<?php echo base_url('uploads/').$signature?>" alt="" height="200" width="200"></td></tr>
								<tr><td>Id Back</td><td><img src="<?php echo base_url('uploads/').$Id_back?>" alt="" height="200" width="200"></td></tr>
								<tr><td>Id Front</td><td><img src="<?php echo base_url('uploads/').$id_front?>" alt="" height="200" width="200"></td></tr>

							</table>
						<?php
						}
						?>
		</div>
	</div>
</div>
