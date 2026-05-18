<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Borrowed Money repayments</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All Borrowed Money repayments</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<?php
			$dd = get_by_id('borrowed','borrowed_id',  $this->uri->segment(3))
			?>
			<h3>Borrowed money details</h3>
			<table class="table" style="border: thick solid red; border-radius: 12px;">
				<tr>
					<td>Borrowed From</td>
					<td><?php  echo  $dd->borrowed_from; ?></td>
				</tr>
				<tr>
					<td>Borrowed Amount</td>
					<td>MWK<?php  echo  number_format($dd->amount,2); ?></td>
				</tr>
				<tr>
					<td>Total Borrowed Amount interest to pay</td>
					<td>MWK<?php  echo  number_format($dd->total_interest,2); ?></td>
				</tr><tr>
					<td>Date Borrowed</td>
					<td><?php  echo  $dd->date_borrowed; ?></td>
				</tr>
			</table>
			<center><Button class="btn btn-danger" onclick="pay_borrowed()">Add repayment</Button></center>
			<hr>

			<table class="table table-bordered" id="data-table" >
				<thead>
            <tr>
                <th>No</th>

		<th>Interest Paid</th>
		<th>Principal Paid</th>
		<th>Facilitated By</th>
		<th>Date Of Repayment</th>
		<th>Stamp</th>
		<th>Action</th>
            </tr>
				</thead>
				<tbody>
			<?php
			$start = 1;
            foreach ($borrowed_repayements_data as $borrowed_repayements)
            {
                ?>
                <tr>
			<td width="80px"><?php echo $start ?></td>

			<td><?php echo $borrowed_repayements->interest_paid ?></td>
			<td><?php echo $borrowed_repayements->principal_paid ?></td>
			<td><?php echo $borrowed_repayements->Firstname." ".$borrowed_repayements->Lastname ?></td>
			<td><?php echo $borrowed_repayements->date_of_repaymet ?></td>
			<td><?php echo $borrowed_repayements->stamp ?></td>
			<td style="text-align:center" width="200px">
				<?php 

				echo anchor(site_url('borrowed_repayements/update/'.$borrowed_repayements->b_id),'Update'); 
				echo ' | '; 
				echo anchor(site_url('borrowed_repayements/delete/'.$borrowed_repayements->b_id),'Delete','onclick="javasciprt: return confirm(\'Are You Sure ?\')"'); 
				?>
			</td>
		</tr>
                <?php
				$start ++;
            }
            ?>
				</tbody>
        </table>
		</div>
	</div>
</div>
<div aria-hidden="true" class="onboarding-modal modal fade" id="pay_borrowed_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Add KYC to this customer</h4>
				<form action="<?php echo base_url('borrowed_repayements/create_action')?>" method="post" class="form-row" enctype="multipart/form-data">

						<input hidden type="text" class="form-control" name="borrowed_id" id="borrowed_id" placeholder="Borrowed Id" value="<?php echo $this->uri->segment(3); ?>" />

					<div class="form-group">
						<label for="decimal">Interest Paid </label>
						<input type="text" required class="form-control" name="interest_paid" id="interest_paid" placeholder="Interest Paid" />
					</div>
					<div class="form-group">
						<label for="decimal">Principal Paid </label>
						<input type="text" required class="form-control" name="principal_paid" id="principal_paid" placeholder="Principal Paid"  />
					</div>

					<div class="form-group">
						<label for="date">Date Of Repayment </label>
						<input type="date" required class="form-control" name="date_of_repaymet"  placeholder="Date Of Repayment"  />
					</div>

					<button type="submit" class="btn btn-primary">Save Changes</button>

				</form>
			</div>
		</div>
	</div>
</div>
