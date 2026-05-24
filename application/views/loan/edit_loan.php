<?php
if (!isset($loan_types) || !is_array($loan_types)) {
    $loan_types = array();
}
$get_settings = get_by_id('settings','settings_id', '1');
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Loan Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Add loan</span>
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
                                            <option value="<?php  echo $item->id; ?>"  <?php if($item->id==$loan_added_by){echo "selected";} ?>><?php echo $item->Firstname." ".$item->Lastname?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>

                            </tr>
                            <tr>
                                <td>Customer</td>
                                <td>
                                    <input type="text" name="customer_type" value="individual" hidden >
                                    <input type="text" name="loan_id" value="<?php echo $loan_id ?>" hidden>
                                    <select name="customer" id="customer_loan" class="select2" required>
                                        <option value="">--select customer</option>
                                        <?php

                                        foreach ($customers as $c){
                                            ?>
                                            <option value="<?php  echo  $c->id;?>" <?php if($c->id==$customer){echo "selected";} ?>><?php echo $c->Firstname. " ".$c->Lastname?></option>
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


                            <tr>
                                <td></td>
                                <td></td>
                            </tr>
                            <?php if (validation_errors()) : ?>
                                <tr>
                                    <td>

                                    </td>
                                    <td>
                                        <?php echo validation_errors(); ?>
                                    </td>
                                </tr>
                            <?php endif;?>
                            <?php if (isset($error)) : ?>
                                <tr>
                                    <td>

                                    </td>
                                    <td>
                                        <?php echo $error; ?>
                                    </td>
                                </tr>
                            <?php endif;?>
                        </table>

                        <br>
                        <input type="submit" name="submit_loan" value="Create Loan" class="btn btn-danger btn-sm btn-block"  onclick="confirm('Are you sure you want to create loan?')"/>

                    </form>
                </div>
                <div class="col-lg-4">
                    <h3>Results</h3>

                    <div style="padding: 1em;">
                        <div class="row">
                            <div class="col-6">
                                <div id="customer-results">
                                    <p>Customer search results details</p>

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
        // Your code here


        var xhr = new XMLHttpRequest();
        xhr.open("GET", "<?php echo base_url()?>Individual_customers/view_customer/" + id, true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var res = JSON.parse(xhr.responseText);

                var det = '<table class="table">' +
                    '<tr><td>Firstname</td><td>' + res.data.Firstname + '</td></tr>' +
                    '<tr><td>Lastname</td><td>' + res.data.Lastname + '</td></tr>' +
                    '<tr><td>Gender</td><td>' + res.data.Gender + '</td></tr>' +
                    '<tr><td>Date of Birth</td><td>' + res.data.DateOfBirth + '</td></tr>' +
                    '<tr><td>Contact No</td><td>' + res.data.PhoneNumber + '</td></tr>' +
                    '<tr><td>Profession</td><td>' + res.data.Profession + '</td></tr>' +
                    '<tr><td>Source of Income</td><td>' + res.data.SourceOfIncome + '</td></tr>' +
                    '</table>';

                document.getElementById("customer-results").innerHTML = det;
                // document.getElementById("fee_amount").value = res.data.feeamount;

                var dd = '';
                res.data.loan.forEach(function (value) {
                    var color = 'orange';
                    if (value.loan_status === 'ACTIVE') {
                        color = "green";
                    } else if (value.loan_status === 'CLOSED') {
                        color = "red";
                    }
                    dd += '<li><a href="<?php echo base_url('loan/view/')?>' + value.loan_id + '">#' + value.loan_number + '-</a><span style="color: ' + color + '">' + value.loan_status + '</span></li>';
                });

                document.getElementById("loandd").innerHTML = dd;

                var kyc = '';
                if (!isEmpty(res.data.kyc)) {
                    kyc += '<tr><td>Photo</td><td><img src="' + baseURL + 'uploads/' + res.data.kyc.photograph + '" alt="" width="100" height="50"></td></tr>' +
                        '<tr><td>ID type</td><td>' + res.data.kyc.IDType + '</td></tr>' +
                        '<tr><td>ID Number</td><td>' + res.data.kyc.IDNumber + '</td></tr>' +
                        '<tr><td>ID issue date</td><td>' + res.data.kyc.IssueDate + '</td></tr>' +
                        '<tr><td>ID Expiry date</td><td>' + res.data.kyc.ExpiryDate + '</td></tr>' +
                        '<tr><td>ID front</td><td><img src="' + baseURL + 'uploads/' + res.data.kyc.id_front + '" alt="" width="100" height="50"></td></tr>' +
                        '<tr><td>ID back</td><td><img src="' + baseURL + 'uploads/' + res.data.kyc.Id_back + '" alt="" width="100" height="50"></td></tr>' +
                        '<tr><td>Sig/fingerprint</td><td><img src="' + baseURL + 'uploads/' + res.data.kyc.signature + '" alt="" width="100" height="50"></td></tr>';
                }

                document.getElementById("kyc_data").innerHTML = kyc;

                // Show spinner while loading data
                document.getElementById("image_actions").innerHTML = "<i class='fa fa-spinner fa-spin'></i>Loading data";
            } else if (xhr.readyState === 4 && xhr.status !== 200) {
                alert('Failed to interact with server. Please check internet connection.');
            }
        };

        xhr.send();

    });
</script>
