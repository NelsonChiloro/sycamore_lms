<?php
$income = get_by_id('account','is_income_account',"Yes");
$expense = get_by_id('account','is_expense',"Yes");
?>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">internal  cash account </h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">cash accounting configure</a>

            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick orange solid;border-radius: 14px;">
            <h3>Configure income</h3>
            <form action="<?php echo base_url('Internal_accounts/update_income')?>" method="post">
                <table class="table table-bordered">
                    <tr>
                        <td>Current  assigned income account</td>
                        <td>Assign new</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><?php
                            if(empty($income)){
                                echo "Not configured";
                            }else{
                                echo $income->account_number;
                            }
                            ?></td>
                        <td>
                            <select name="account_number" class="form-control" required>
                                <option value="">--select--account</option>
                                <?php
                                foreach ($all_cash as $a){
                                    ?>
                                    <option value="<?php echo $a->account_number ?>"><?php echo $a->account_number." ".$a->account_name ?></option>
                                    <?php
                                }
                                ?>
                            </select>

                        </td>
                        <td><input type="submit" value="save" class="btn btn-danger"></td>
                    </tr>
                </table>
            </form>
            <hr>
            <form action="<?php echo base_url('Internal_accounts/update_expense')?>" method="post">
                <table class="table table-bordered">
                    <tr>
                        <td>Current  assigned expense account</td>
                        <td>Assign new</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><?php
                            if(empty($expense)){
                                echo "Not configured";
                            }else{
                                echo $expense->account_number;
                            }
                            ?></td>
                        <td>
                            <select name="account_number" class="form-control" required>
                                <option value="">--select--account</option>
                                <?php
                                foreach ($all_cash as $a){
                                    ?>
                                    <option value="<?php echo $a->account_number ?>"><?php echo $a->account_number." ".$a->account_name ?></option>
                                    <?php
                                }
                                ?>
                            </select>

                        </td>
                        <td><input type="submit" value="save" class="btn btn-danger"></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>


