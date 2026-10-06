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
        <h2 style="margin-top:0px">Proofofidentity <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="enum">IDType <?php echo form_error('IDType') ?></label>
            <input type="text" class="form-control" name="IDType" id="IDType" placeholder="IDType" value="<?php echo $IDType; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">IDNumber <?php echo form_error('IDNumber') ?></label>
            <input type="text" class="form-control" name="IDNumber" id="IDNumber" placeholder="IDNumber" value="<?php echo $IDNumber; ?>" />
        </div>
	    <div class="form-group">
            <label for="date">IssueDate <?php echo form_error('IssueDate') ?></label>
            <input type="text" class="form-control" name="IssueDate" id="IssueDate" placeholder="IssueDate" value="<?php echo $IssueDate; ?>" />
        </div>
	    <div class="form-group">
            <label for="date">ExpiryDate <?php echo form_error('ExpiryDate') ?></label>
            <input type="text" class="form-control" name="ExpiryDate" id="ExpiryDate" placeholder="ExpiryDate" value="<?php echo $ExpiryDate; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">DocImageURL <?php echo form_error('DocImageURL') ?></label>
            <input type="text" class="form-control" name="DocImageURL" id="DocImageURL" placeholder="DocImageURL" value="<?php echo $DocImageURL; ?>" />
        </div>
	    <div class="form-group">
            <label for="timestamp">Stamp <?php echo form_error('Stamp') ?></label>
            <input type="text" class="form-control" name="Stamp" id="Stamp" placeholder="Stamp" value="<?php echo $Stamp; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">ClientId <?php echo form_error('ClientId') ?></label>
            <input type="text" class="form-control" name="ClientId" id="ClientId" placeholder="ClientId" value="<?php echo $ClientId; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Photograph <?php echo form_error('photograph') ?></label>
            <input type="text" class="form-control" name="photograph" id="photograph" placeholder="Photograph" value="<?php echo $photograph; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Signature <?php echo form_error('signature') ?></label>
            <input type="text" class="form-control" name="signature" id="signature" placeholder="Signature" value="<?php echo $signature; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Id Back <?php echo form_error('Id_back') ?></label>
            <input type="text" class="form-control" name="Id_back" id="Id_back" placeholder="Id Back" value="<?php echo $Id_back; ?>" />
        </div>
	    <div class="form-group">
            <label for="int">Id Front <?php echo form_error('id_front') ?></label>
            <input type="text" class="form-control" name="id_front" id="id_front" placeholder="Id Front" value="<?php echo $id_front; ?>" />
        </div>
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('proofofidentity') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>