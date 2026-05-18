<?php
$b = $this->Branches_model->get_all();
$group_cat = $this->Group_categories_model->get_all();

$get_c = get_all('individual_customers');
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Groups</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">groups form</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick green solid;border-radius: 14px;">
        <form action="<?php echo $action; ?>" method="post" >

			<div class="row">
				<div class="col-lg-8">
					<div class="row">
					<div class="form-group col-3">
						<label for="varchar">Group Name <?php echo form_error('group_name') ?></label>
						<input type="text" class="form-control" name="group_name" id="group_name" placeholder="Group Name" value="<?php echo $group_name; ?>" />
					</div>


					<div class="form-group col-3">
						<label for="int">Branch * <?php echo form_error('branch') ?></label>

						<select class="form-control" name="branch" id="Branch">
							<option value="">select</option>
							<?php
							foreach ($b as $br){
								?>
								<option value="<?php echo $br->id ?>" <?php if($br->id==$branch){echo "selected";}  ?>><?php echo $br->BranchName?></option>
								<?php
							}
							?>
						</select>
					</div>

					<div class="form-group col-6">

                        <p>(NB  multiple documents should be scanned into one PDF document)</p>
						<label for="id_front" id="ppp" class="custom-file-upload">Upload Group agreements file  <i class="fa fa-upload fa-flip"></i></label>
						<input type="file"  onchange="uploadprofile('id_front')"   id="id_front" accept=
						"application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint,text/plain, application/pdf" />
						<input type="text" id="id_front1"  name="file" hidden  <?php  echo $file; ?>>

					</div>

					</div>

                    <div class="row">
                        <div class="form-group col-12">
						<label for="group_description">Group Description <?php echo form_error('group_description') ?></label>
						<textarea class="form-control" rows="3" name="group_description" id="group_description" placeholder="Group Description"><?php echo $group_description; ?></textarea>
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="row" style="padding: 1em; border: thick solid black; border-radius: 12px;">
                        <p>GROUP MEMBER ADDING SECTION</p>
					<div class="form-group col-12">
						<label for="varchar">Select/search group members (You can select multiple members)</label>
						<?php
						$row = array();
						if (!empty($group_id)) {
							$dd = get_all_by_id('customer_groups','group_id', $group_id);
							foreach($dd as $single) {
								$row[] = $single->customer;
							}
						}
						?>
						<select name="customer[]" id="" class="form-control select2"  multiple required>
							<option value="">--select customer--</option>
							<?php

							foreach ($get_c as $item){
								?>
								<option value="<?php echo $item->id ?>" <?php  if(in_array($item->id,$row)){
                                        echo "selected";
                                    } ?>> <?php echo $item->Firstname.' '.$item->Lastname ?></option>
								<?php

							}
							?>
						</select>
					</div>
					</div>
				</div>
			</div>

<div class="row">
	<div class="col-lg-12">
		<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />
		<button type="submit" class="btn btn-primary"><?php echo $button ?></button>

	</div>
</div>

	</form>
		</div></div>
</div>
