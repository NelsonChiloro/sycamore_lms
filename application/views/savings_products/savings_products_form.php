<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Savings products</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Savings products form</a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick green solid;border-radius: 14px;">
        <h2 style="margin-top:0px">Savings products <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Name <?php echo form_error('name') ?></label>
            <input type="text" class="form-control" name="name" id="name" placeholder="Name" value="<?php echo $name; ?>" />
        </div>

	    <div class="form-group">
            <label for="int">Interest Per Annum <?php echo form_error('interest_per_anum') ?></label>
            <input type="text" class="form-control" name="interest_per_anum" id="interest_per_anum" placeholder="Interest Per Anum" value="<?php echo $interest_per_anum; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Interest Method <?php echo form_error('interest_method') ?></label>
			<select class="form-control" name="interest_method" id="interest_method" >
				<option value="">--select--</option>
				<option value="prorata" <?php if($interest_method=='prorata'){echo "selected";} ?>>prorata</option>
				<option value="last savings balance" <?php if($interest_method=='last savings balance'){echo "selected";} ?>>Last savings balance</option>
			</select>

        </div>
	    <div class="form-group">
            <label for="enum">Interest Posting Frequency <?php echo form_error('interest_posting_frequency') ?></label>
			<select class="form-control" name="interest_posting_frequency" id="interest_posting_frequency">
				<option value="">--select--</option>
				<option value="Every 1 month" <?php  if($interest_posting_frequency=='Every 1 month'){echo "selected";} ?>>Every 1 month</option>
				<option value="Every 2 month" <?php  if($interest_posting_frequency=='Every 2 month'){echo "selected";} ?>>Every 2 month</option>
				<option value="Every 3 month" <?php  if($interest_posting_frequency=='Every 3 month'){echo "selected";} ?>>Every 3 month</option>
				<option value="Every 4 month" <?php  if($interest_posting_frequency=='Every 4 month'){echo "selected";} ?>>Every 4 month</option>
				<option value="Every 6 month" <?php  if($interest_posting_frequency=='Every 6 month'){echo "selected";} ?>>Every 6 month</option>
				<option value="Every 12 month" <?php if($interest_posting_frequency=='Every 12 month'){echo "selected";} ?>>Every 12 month</option>
			</select>

        </div>
	    <div class="form-group">
            <label for="enum">When should Interest be added to Saving Account? <?php echo form_error('when_to_post') ?></label>

			<select class="form-control" name="when_to_post" id="when_to_post">
				<option value="">--select--</option>
				<option value="day 1">day 1</option>
				<option value="day 2">day 2</option>
				<option value="day 3">day 3</option>
				<option value="day 4">day 4</option>
				<option value="day 5">day 5</option>
				<option value="day 6">day 6</option>
				<option value="day 7">day 7</option>
				<option value="day 8">day 8</option>
				<option value="day 9">day 9</option>
				<option value="day 10">day 10</option>
				<option value="day 11">day 11</option>
				<option value="day 12">day 12</option>
				<option value="day 13">day 13</option>
				<option value="day 14">day 14</option>
				<option value="day 15">day 15</option>
				<option value="day 16">day 16</option>
				<option value="day 17">day 17</option>
				<option value="day 18">day 18</option>
				<option value="day 19">day 19</option>
				<option value="day 20">day 20</option>
				<option value="day 21">day 21</option>
				<option value="day 22">day 22</option>
				<option value="day 23">day 23</option>
				<option value="day 24">day 24</option>
				<option value="day 25">day 25</option>
				<option value="day 26">day 26</option>
				<option value="day 27">day 27</option>
				<option value="day 28">day 28</option>
			</select>
        </div>
	    <div class="form-group">
            <label for="decimal">Minimum Balance For Interest rate <?php echo form_error('minimum_balance_for_interest') ?></label>
            <input type="text" class="form-control" name="minimum_balance_for_interest" id="minimum_balance_for_interest" placeholder="Minimum Balance For Interest" value="<?php echo $minimum_balance_for_interest; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Minimum Balance Withdrawal <?php echo form_error('minimum_balance_withdrawal') ?></label>
            <input type="text" class="form-control" name="minimum_balance_withdrawal" id="minimum_balance_withdrawal" placeholder="Minimum Balance Withdrawal" value="<?php echo $minimum_balance_withdrawal; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Allow savings overdrawn <?php echo form_error('overdraft') ?></label>
            <input type="radio" class="form-control" name="overdraft" id="overdraft" placeholder="Overdraft" value="Yes"  /> Yes
            <input type="radio" class="form-control" name="overdraft" id="overdraft" placeholder="Overdraft" value="No" /> No
        </div>
	    <input type="hidden" name="saviings_product_id" value="<?php echo $saviings_product_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('savings_products') ?>" class="btn btn-default">Cancel</a>
	</form>

		</div>
	</div>
</div>
