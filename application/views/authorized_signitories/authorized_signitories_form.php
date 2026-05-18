<div class="content-i">
	<div class="content-box">
		<div class="element-wrapper">
			<h6 class="element-header">Authorized signitories <?php echo $button ?></h6>
			<div class="element-box">

        <form action="<?php echo $action; ?>" method="post" class="form-row">
	    <div class="form-group col-6">
            <label for="int">ClientId <?php echo form_error('ClientId') ?></label>
            <input type="text" class="form-control" name="ClientId" id="ClientId" placeholder="ClientId" value="<?php echo $ClientId; ?>" />
        </div>
	    <div class="form-group col-6">
            <label for="varchar">FullLegalName <?php echo form_error('FullLegalName') ?></label>
            <input type="text" class="form-control" name="FullLegalName" id="FullLegalName" placeholder="FullLegalName" value="<?php echo $FullLegalName; ?>" />
        </div>
			<div class="form-group col-6">

				<label for="enum">IDType </label>
				<select class="form-control" name="IDType" id="IDType" required >
					<option value="">--select--</option>
					<option value="NATIONAL_IDENTITY_CARD" <?php if($IDType == 'NATIONAL_IDENTITY_CARD'){echo "selected";} ?>>NATIONAL IDENTITY CARD</option>
					<option value="DRIVING_LISENCE" <?php if($IDType == 'DRIVING_LISENCE'){echo "selected";} ?>>DRIVING LICENCE</option>
					<option value="PASSPORT" <?php if($IDType == 'PASSPORT'){echo "selected";} ?>>PASSPORT</option>
					<option value="WORK_PERMIT" <?php if($IDType == 'WORK_PERMIT'){echo "selected";} ?>>WORK PERMIT</option>
					<option value="VOTER_REGISTRATION" <?php if($IDType == 'VOTER_REGISTRATION'){echo "selected";} ?>>VOTER REGISTRATION</option>
					<option value="PUBLIC_STATE_OFFICIAL_LETTER" <?php if($IDType == 'PUBLIC_STATE_OFFICIAL_LETTER'){echo "selected";} ?>>PUBLIC STATE OFFICIAL LETTER</option>

				</select>
			</div>
	    <div class="form-group col-6">
            <label for="varchar">ID Number <?php echo form_error('IDNumber') ?></label>
            <input type="text" class="form-control" name="IDNumber" id="IDNumber" placeholder="IDNumber" value="<?php echo $IDNumber; ?>" />
        </div>
			<div class="form-group col-6">
				<label for="DocImageURL"  class="custom-file-upload">upload Id photo </label>
				<input type="file"  onchange="uploadprofile('DocImageURL')" class="form-control" id="DocImageURL" placeholder="DocImageURL"  />

				<input type="text" id="DocImageURL1"  name="DocImageURL"  value="<?php echo $DocImageURL ?>" hidden required>
				<div id="DocImageURL2">
					<img src="<?php if($DocImageURL == ""){echo base_url('uploads/holder.PNG');}else{ echo base_url('uploads/'.$DocImageURL);}?>" alt="" height="100" width="100">
				</div>
			</div>
			<div class="form-group col-6">
				<label for="SignatureImage" class="custom-file-upload"> upload Signature Image </label>
				<input type="file" class="form-control" onchange="uploadprofile('SignatureImage')" id="SignatureImage" placeholder="SignatureImageURL"  />
				<input type="text" id="SignatureImage1" value="<?php echo $SignatureImageURL?>" name="SignatureImageURL" hidden required>
				<div id="SignatureImage2">
					<img src="<?php if($SignatureImageURL == ""){echo base_url('uploads/holder.PNG');}else{ echo base_url('uploads/'.$SignatureImageURL);}?>" alt="" height="100" width="100">
				</div>
			</div>

	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 

	</form>
			</div>
		</div>
	</div>
</div>
