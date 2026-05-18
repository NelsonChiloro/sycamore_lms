<?php

$b = $this->Branches_model->get_all();
$countryd = $this->Geo_countries_model->get_all();
$et = $this->Entity_type_model->get_all();
?>
<div class="content-i">
	<div class="content-box">
		<div class="element-wrapper">
			<h6 class="element-header">Corporate Customers</h6>
			<div class="element-box">
        <h2 style="margin-top:0px">Corporate_customers <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post" class="form-row">
	    <div class="form-group col-6">
            <label for="varchar">Entity Name <?php echo form_error('EntityName') ?></label>
            <input type="text" class="form-control" name="EntityName" id="EntityName" placeholder="EntityName" value="<?php echo $EntityName; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="date">Date Of Incorporation <?php echo form_error('DateOfIncorporation') ?></label>
            <input type="date" class="form-control" name="DateOfIncorporation" id="DateOfIncorporation" placeholder="DateOfIncorporation" value="<?php echo $DateOfIncorporation; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="varchar">Registration Number <?php echo form_error('RegistrationNumber') ?></label>
            <input type="text" class="form-control" name="RegistrationNumber" id="RegistrationNumber" placeholder="RegistrationNumber" value="<?php echo $RegistrationNumber; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="varchar">Entity Type <?php echo form_error('EntityType') ?></label>
			<select class="form-control" name="EntityType">
				<option value="">--select--</option>
				<?php

				foreach ($et as $i){
					?>
					<option value="<?php echo $i->name; ?>" <?php if($i->name==$EntityType) ?>><?php echo $i->name?></option>

					<?php
				}
				?>
			</select>
              </div>

	    <div class="form-group col-6">
            <label for="varchar">Tax Identification Number <?php echo form_error('TaxIdentificationNumber') ?></label>
            <input type="text" class="form-control" name="TaxIdentificationNumber" id="TaxIdentificationNumber" placeholder="TaxIdentificationNumber" value="<?php echo $TaxIdentificationNumber; ?>" />
        </div>
			<div class="form-group col-6">
				<label for="varchar">Country <?php echo form_error('Country') ?></label>
				<select class="form-control" name="Country">
					<option value="">--select--</option>
					<?php

					foreach ($countryd as $item){
						?>
						<option value="<?php echo $item->code; ?>" <?php if($item->code==$Country) ?>><?php echo $item->name?></option>

						<?php
					}
					?>
				</select>

			</div>
			<div class="form-group col-12">
				<label for="int">Branch <?php echo form_error('Branch') ?></label>
				<select class="form-control" name="Branch" id="Branch">
					<option value="">select</option>
					<?php
					foreach ($b as $br){
						?>
						<option value="<?php echo $br->Code ?>" <?php if($br->Code==$Branch){echo "selected";}  ?>><?php echo $br->BranchName?></option>
						<?php
					}
					?>
				</select>

			</div>

			<div class="form-group col-4">
				<label for="certificate" class="custom-file-upload"> Upload company certificate </label>
				<input type="file"  onchange="uploadprofile('certificate')"   id="certificate"  />
				<input type="text" id="certificate1"  name="company_certificate" value="<?php  echo $company_certificate;?>" hidden required>
				<div id="certificate2">
					<img src="<?php if($company_certificate == ""){echo base_url('uploads/holder.PNG');}else{ echo base_url('uploads/'.$company_certificate);}?>" alt="" height="100" width="100">
				</div>
			</div>
			<div class="form-group col-4">
				<label for="tax_id_doc" class="custom-file-upload"> Upload company tax clearance certificate </label>
				<input type="file"  onchange="uploadprofile('tax_id_doc')"   id="tax_id_doc"  />
				<input type="text" id="tax_id_doc1"  name="tax_id_doc" value="<?php  echo $tax_id_doc?>" hidden required>
				<div id="tax_id_doc2">
					<img src="<?php if($tax_id_doc == ""){echo base_url('uploads/holder.PNG');}else{ echo base_url('uploads/'.$tax_id_doc);}?>" alt="" height="100" width="100">
				</div>
			</div>
			<div class="form-group col-4">
				<label for="memo_doc" class="custom-file-upload"> Upload company memo of understanding </label>
				<input type="file"  onchange="uploadprofile('memo_doc')"   id="memo_doc"  />
				<input type="text" id="memo_doc1"  name="memo_doc"  value="<?php  echo $memo_doc ?>" hidden required>
				<div id="memo_doc2">
					<img src="<?php if($memo_doc == ""){echo base_url('uploads/holder.PNG');}else{ echo base_url('uploads/'.$memo_doc);}?>" alt="" height="100" width="100">
				</div>
			</div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('corporate_customers') ?>" class="btn btn-default">Cancel</a>
	</form>
			</div>
		</div>
	</div>
</div>
