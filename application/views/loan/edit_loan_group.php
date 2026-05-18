<?php
if (!isset($loan_types) || !is_array($loan_types)) {
    $loan_types = array();
}
$customers = get_all_where("groups",'group_status= "Active"');
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Loan edit for group member</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Editing loan group member</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <div class="row">
                <div class="col-lg-5 border-right">
                    <form action="<?php echo base_url('loan/edit_action')?>" method="POST" enctype="multipart/form-data">
                        <table class="table">
                            <tr>
                                <td>Added by</td>
                                <td>

                                    Officer: <select name="user" id="" class="select2">
                                        <option value="All">All officers</option>
                                        <?php
                                        $officer = get_all('employees');
                                        foreach ($officer as $item){
                                            ?>
                                            <option value="<?php  echo $item->id; ?>" <?php if($item->id==$loan_added_by){echo "selected";} ?>><?php echo $item->Firstname." ".$item->Lastname?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>

                            </tr>
                            <tr>
                                <td>Group</td>
                                <td>
                                    <input type="text" name="customer_type" value="group" hidden >
                                    <input type="text" name="loan_id" value="<?php echo $loan_id ?>" hidden>
                                    <select name="customer" id="group_c" class="select2" required>
                                        <option value="">--select Group--</option>
                                        <?php

                                        foreach ($customers as $c){
                                            ?>
                                            <option value="<?php  echo  $c->group_id;?>" <?php if($c->group_id==$customer){echo "selected";} ?>><?php echo $c->group_name. " ".$c->group_code?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>

                            </tr>

                            <tr>
                                <td>Loan number:</td>
                                <td><input type="text" name="loan_number" value="<?php echo  $loan_number; ?>" width="50" required /></td>
                            </tr>
                            <tr>
                                <td>Loan Amount:</td>
                                <td><input type="text" name="amount" value="<?php echo $loan_principal; ?>" width="50" required /></td>
                            </tr>
                            <tr>
                                <td>Loan Term:</td>
                                <td><input type="text" name="months" value="<?php echo $loan_period; ?>" required/></td>
                            </tr>
                            <tr>
                                <td>Select Loan Type:</td>
                                <td><select name="loan_type" id="" class="select2" required >
                                        <option value="">--select--</option>
                                        <?php

                                        foreach ($loan_types as $lt){
                                            ?>
                                            <option value="<?php echo $lt->loan_product_id ?>" <?php if($loan_product_id==$lt->loan_product_id ){echo "selected";} ?>><?php echo $lt->product_name; ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select></td>
                            </tr>
                            <tr>
                                <td>Loan Date:</td>
                                <td><input type="date" name="loan_date"  value="<?php echo $loan_date; ?>" required /></td>
                            </tr>



                        </table>

                            <td></td>
                            <td><input type="submit" name="submit_loan" value="Update Loan" class="btn btn-danger btn-sm btn-block"  onclick="confirm('Are you sure you want to create loan?')"/></td>
                        </tr>

                    </form>
                </div>
                <div class="col-lg-4">
                    <h3>Results</h3>

                    <div style="padding: 1em;">
                        <div class="row">
                            <div class="col-6">
                                <div >
                                    <p>Customer search results details</p>
                                    <ul id="customer_loan" style="">

                                    </ul>

                                </div>
                            </div>
                            <div class="col-6">

                            </div>
                        </div>
                    </div>
                    <div>
                        <h4>Booked loan products</h4>
                        <ul id="loandd">

                        </ul>
                    </div>
                </div>
                <div class="col-lg-3">
                    <h2>KYC</h2>
                    <hr>
                    <table class="table" id="kyc_data">

                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
<script>
    var id = "<?php echo $customer; ?>";
    document.addEventListener('DOMContentLoaded', function() {
        if (id === null || id === "") {

        } else {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "<?php echo base_url()?>Customer_groups/get_members/" + id);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var res = JSON.parse(xhr.responseText);

                        var det = "";
                        res.data.forEach(function (value, index) {
                            det += "<li>" + value.Firstname + " &nbsp; " + value.Lastname + "- (<font color=\"green\"> Member</font>)</li>";
                        });
                        document.getElementById("customer_loan").innerHTML = det;

                        var dd = '';
                        res.loan.forEach(function (value, index) {
                            var color = 'orange';
                            if (value.loan_status === 'INITIATED') {
                                color = "orange";
                            }
                            if (value.loan_status === 'ACTIVE') {
                                color = "green";
                            }
                            if (value.loan_status === 'CLOSED') {
                                color = "red";
                            }
                            dd += "<li><a href=\"<?php echo base_url('loan/view/')?>" + value.loan_id + "\">#" + value.loan_number + "-</a><span style=\"color: " + color + "\">" + value.loan_status + "</span></li>";
                        });
                        document.getElementById("loandd").innerHTML = dd;

                        var kyc = '';
                        if (isEmpty(res.group)) {
                            kyc = '';
                        } else {
                            kyc += "<tr><td>Group Code</td><td>" + res.group.group_code + "</td></tr><tr><td>Group name</td><td>" + res.group.group_name + "</td></tr><tr><td>Registered date date</td><td>" + res.group.group_registered_date + "</td></tr><tr><td>Group Business type</td><td>" + res.group.group_category + "</td></tr><tr><td>Description</td><td>" + res.group.group_description + "</td></tr>";
                        }
                        document.getElementById("kyc_data").innerHTML = kyc;
                    } else {
                        alert('Failed to interact with server. Check internet connection.');
                    }
                }
            };
            xhr.send();
        }
    });

</script>
