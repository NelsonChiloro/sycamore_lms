<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">SMS CONFIG</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Configure when the sms y=should be sent</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
        <h2 style="margin-top:0px">Sms_settings <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
            <div class="row">
                <div class="col-lg-3">
                    <h4>Send SMS on Customer Approval</h4>
                    <div class="selector">
                        <div class="selecotr-item">
                            <input type="radio" id="radio1" name="customer_approval" class="selector-item_radio" value="Yes"  <?php echo ($customer_approval== 'Yes') ?  "checked" : "" ;  ?> >
                            <label for="radio1" class="selector-item_label">Yes</label>
                        </div>
                        <div class="selecotr-item">
                            <input type="radio" id="radio2" name="customer_approval" class="selector-item_radio" value="No" <?php echo ($customer_approval== 'No') ?  "checked" : "" ;  ?>  >
                            <label for="radio2" class="selector-item_label">No</label>
                        </div>

                    </div>
                </div>
                <div class="col-lg-3">
                    <h4>Send SMS on on Group Approval</h4>
                    <div class="selector">
                        <div class="selecotr-item">
                            <input type="radio" id="radio3" name="group_approval" class="selector-item_radio" value="Yes"  <?php echo ($group_approval== 'Yes') ?  "checked" : "" ;  ?> >
                            <label for="radio3" class="selector-item_label">Yes</label>
                        </div>
                        <div class="selecotr-item">
                            <input type="radio" id="radio4" name="group_approval" class="selector-item_radio" value="No" <?php echo ($group_approval== 'No') ?  "checked" : "" ;  ?>  >
                            <label for="radio4" class="selector-item_label">No</label>
                        </div>

                    </div>
                </div>
                <div class="col-lg-3">
                    <h4>Send SMS on Loan Disbursement</h4>
                    <div class="selector">
                        <div class="selecotr-item">
                            <input type="radio" id="radio5" name="loan_disbursement" class="selector-item_radio" value="Yes"  <?php echo ($loan_disbursement== 'Yes') ?  "checked" : "" ;  ?> >
                            <label for="radio5" class="selector-item_label">Yes</label>
                        </div>
                        <div class="selecotr-item">
                            <input type="radio" id="radio6" name="loan_disbursement" class="selector-item_radio" value="No" <?php echo ($loan_disbursement== 'No') ?  "checked" : "" ;  ?>  >
                            <label for="radio6" class="selector-item_label">No</label>
                        </div>

                    </div>
                </div>
                <div class="col-lg-3">
                    <h4>Notify Customer when loan is paid </h4>
                    <div class="selector">
                        <div class="selecotr-item">
                            <input type="radio" id="radio10" name="loan_payment_notification" class="selector-item_radio" value="Yes"  <?php echo ($loan_payment_notification== 'Yes') ?  "checked" : "" ;  ?> >
                            <label for="radio10" class="selector-item_label">Yes</label>
                        </div>
                        <div class="selecotr-item">
                            <input type="radio" id="radio20" name="loan_payment_notification" class="selector-item_radio" value="No" <?php echo ($loan_payment_notification== 'No') ?  "checked" : "" ;  ?>  >
                            <label for="radio20" class="selector-item_label">No</label>
                        </div>

                    </div>
                </div>
            </div>
            <hr>
<div class="row">
    <div class="col-lg-6">
        <h4>Send SMS notifications to pay loan</h4>
        <div class="selector">
            <div class="selecotr-item">
                <input type="radio" id="radio7" name="before_notice" class="selector-item_radio" value="Yes"  <?php echo ($before_notice== 'Yes') ?  "checked" : "" ;  ?> >
                <label for="radio7" class="selector-item_label">Yes</label>
            </div>
            <div class="selecotr-item">
                <input type="radio" id="radio8" name="before_notice" class="selector-item_radio" value="No" <?php echo ($before_notice== 'No') ?  "checked" : "" ;  ?>  >
                <label for="radio8" class="selector-item_label">No</label>
            </div>

        </div>
    </div>
    <div class="col-lg-6">
        <div class="form-group">
            <label for="int">how many days prior to send  payment notification <?php echo form_error('before_notice_period') ?></label>
            <input required type="text" class="form-control" name="before_notice_period" id="before_notice_period" placeholder="Before Notice Period" value="<?php echo $before_notice_period; ?>" />
        </div>
    </div>
</div>
            <hr>
            <div class="row">
                <div class="col-lg-6">
                    <h4>Send Arrears notification</h4>
                    <div class="selector">
                        <div class="selecotr-item">
                            <input type="radio" id="radio12" name="arrears" class="selector-item_radio" value="Yes"  <?php echo ($arrears== 'Yes') ?  "checked" : "" ;  ?> >
                            <label for="radio12" class="selector-item_label">Yes</label>
                        </div>
                        <div class="selecotr-item">
                            <input type="radio" id="radio13" name="arrears" class="selector-item_radio" value="No" <?php echo ($arrears== 'No') ?  "checked" : "" ;  ?>  >
                            <label for="radio13" class="selector-item_label">No</label>
                        </div>

                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="int">how many days prior to send arrears notification <?php echo form_error('arrears_age') ?></label>
                        <input required type="text" class="form-control" name="arrears_age" id="arrears_age" placeholder="Before arrears Period" value="<?php echo $arrears_age; ?>" />
                    </div>
                </div>
            </div>
            <hr>
<div class="row">
    <div class="col-lg-4">
        <h4>Send Customer App Pass Recovery code</h4>
        <div class="selector">
            <div class="selecotr-item">
                <input type="radio" id="radio14" name="customer_app_pass_recovery" class="selector-item_radio" value="Yes"  <?php echo ($customer_app_pass_recovery== 'Yes') ?  "checked" : "" ;  ?> >
                <label for="radio14" class="selector-item_label">Yes</label>
            </div>
            <div class="selecotr-item">
                <input type="radio" id="radio15" name="customer_app_pass_recovery" class="selector-item_radio" value="No" <?php echo ($customer_app_pass_recovery== 'No') ?  "checked" : "" ;  ?>  >
                <label for="radio15" class="selector-item_label">No</label>
            </div>

        </div>
    </div>
    <div class="col-lg-4">
        <h4>Customer Birthday Notify</h4>
        <div class="selector">
            <div class="selecotr-item">
                <input type="radio" id="radio16" name="customer_birthday_notify" class="selector-item_radio" value="Yes"  <?php echo ($customer_birthday_notify== 'Yes') ?  "checked" : "" ;  ?> >
                <label for="radio16" class="selector-item_label">Yes</label>
            </div>
            <div class="selecotr-item">
                <input type="radio" id="radio17" name="customer_birthday_notify" class="selector-item_radio" value="No" <?php echo ($customer_birthday_notify== 'No') ?  "checked" : "" ;  ?>  >
                <label for="radio17" class="selector-item_label">No</label>
            </div>

        </div>
    </div>
    <div class="col-lg-4">
        <h4>Deposit Withdraw Notification</h4>
        <div class="selector">
            <div class="selecotr-item">
                <input type="radio" id="radio18" name="deposit_withdraw_notification" class="selector-item_radio" value="Yes"  <?php echo ($deposit_withdraw_notification== 'Yes') ?  "checked" : "" ;  ?> >
                <label for="radio18" class="selector-item_label">Yes</label>
            </div>
            <div class="selecotr-item">
                <input type="radio" id="radio19" name="deposit_withdraw_notification" class="selector-item_radio" value="No" <?php echo ($deposit_withdraw_notification== 'No') ?  "checked" : "" ;  ?>  >
                <label for="radio19" class="selector-item_label">No</label>
            </div>

        </div>
    </div>

</div>


            <br>
            <br>

	    <input type="hidden" name="id" value="<?php echo $id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 

	</form>
        </div>
    </div>
</div>
