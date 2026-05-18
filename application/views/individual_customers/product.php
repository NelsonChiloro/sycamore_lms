<?php
$ca = $this->Chart_of_accounts_model->get_all_savings();
$account = $this->Accounts_model->getbyid($id);

?>
<div class="content-i">
	<div class="content-box">
		<div class="row">
			<div class="col-lg-12">
				<div class="element-wrapper">
					<h6 class="element-header">Customer details</h6>
					<div class="element-box">

						<br>
						<hr>
						<a href="<?php echo base_url('Individual_customers/read/'.$this->uri->segment(3))?>" class="btn btn-success">View details</a>	<a href="<?php echo base_url('Individual_customers/products/'.$this->uri->segment(3))?>" class="btn btn-warning">Products</a>	<a href="<?php echo base_url('Proofofidentity/check/').$this->uri->segment(3)?>" class="btn btn-primary" >Customer KYC</a>	<a href="<?php echo base_url('Individual_customers/update').$this->uri->segment(3) ?>" class="btn btn-success">Edit Customer</a><a href="" class="btn btn-warning">Deactivate Customer</a>
						<h2>Account Product</h2>
						<a href="<?php echo base_url('Accounts/add_account/').$id?>" class="btn btn-primary">Open Account</a>
						<ul>
							<?php

							foreach ($account as $item){
								?>
								<li><?php echo $item->AccountNumber; ?></li>
							<?php
							}
							?>




						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

