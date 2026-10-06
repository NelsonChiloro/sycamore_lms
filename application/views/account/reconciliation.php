<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">account Reconciliation</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">Cashier Account Reconciliation</a>

            </nav>
        </div>
    </div>
	<div class="card">
        <div class="card-body" style="border: thick #24C16B solid;border-radius: 14px;">
            <!--			<a href="--><?php //echo base_url('account/create')?><!--" class="btn btn-sm btn-primary"><i class="anticon anticon-plus-square" style="color: white; font-size: 20px;"></i>Add Savings account</a>-->
            <?php
            $te = $this->Tellering_model->get_all2();

            $data =  $this->Cashier_vault_pends_model->get_all();
            ?>
            <div style="border:  thin solid black; border-radius: 15px; padding: 2em;">
                <form action="<?php echo base_url('Account/get_teller_transaction_reconciliation') ?>" method="post" >

                    <input hidden type="text" name="account2" id="teller_account">
                    <div class="form-group">
                        <label for=""> select Teller Account</label>
                        <select name="account" >
                            <option value="">--select--</option>
                            <?php

                            foreach ($te as $value){
                                ?>
                                <option value="<?php  echo $value->account_number ?>"><?php echo $value->Firstname." ".$value->Lastname."(-".$value->account_name." ".$value->account_number.")"?></option>
                                <?php
                            }
                            ?>

                        </select>
                        Date from : <input type="date" name="from">
                        Date to : <input type="date" name="to">
                        <input type="submit" name="submit" value="filter">
                        <input type="submit" name="submit" value="Export excel">
                    </div>


                </form>
            </div>
            <div class="m-t-25" id="reconci_data">
               <table class="table table-bordered">
                  <thead class="bg-primary text-white">
                    <tr>
                        <td>Trans ID</td>
                        <td>System Time</td>
                        <td>Teller Account No</td>
                        <td>Credit</td>
                        <td>Debit</td>
                        <td>Balance</td>

                        <td>Action</td>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    $tcredit = 0;
                    $tdebit = 0;
                    if(!empty($re)){

                    foreach ($re as $dd){
                    $tcredit += $dd->credit1;
                    $tdebit += $dd->debit1;
                    ?>


                    <tr>
                        <td><?php echo $dd->transaction_id  ?></td>
                        <td><?php  echo $dd->system_time  ?></td>
                        <td><?php echo $dd->teller_account  ?></td>
                        <td><?php echo $dd->credit1  ?></td>
                        <td><?php echo $dd->debit1  ?></td>
                        <td><?php echo $dd->balance1  ?></td>

                        <td><a href="<?php echo base_url('account/print_receipt/').$dd->tid ?>" target="_blank" > Print deposit receipt</a>
                            <a href="<?php echo base_url('account/print_loan_receipt/').$dd->tid ?>"  target="_blank"> Print loan payment receipt</a></td>
                    </tr>

                   <?php
                    }
                    }

                 ?>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><?php echo number_format($tcredit,2) ?></td>
                        <td><?php echo number_format($tdebit,2) ?></td>
                        <td><?php echo number_format($tcredit-$tdebit,2) ?></td>
                        <td></td>

                    </tr>
                    </tfoot>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
