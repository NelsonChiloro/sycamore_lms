
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">All Revenue report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Revenue report</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <!--            <form action="--><?php //echo base_url('reports/period_analysis') ?><!--" method="get">-->
            <!--                <fieldset>-->
            <!--                    <legend>Report filter</legend>-->
            <!--                    <div id="controlgroup">-->
            <!---->
            <!--                        Date from:<input type="text" class="dpicker" name="from" value="--><?php // echo $this->input->get('from')?><!--" >-->
            <!--                        Date to:<input type="text" class="dpicker" name="to" value="--><?php // echo $this->input->get('to')?><!--" >-->
            <!--                        <button type="submit" name="search" value="filter">Filter</button>-->
            <!--                        <button type="submit" name="search" value="pdf"><i class="fa fa-file-pdf text-danger"></i></button>-->
            <!--                        <button type="submit" name="search" value="excel"><i class="fa fa-file-excel text-success"></i></button>-->
            <!--                    </div>-->
            <!--                </fieldset>-->
            <!--            </form>-->
            <hr>
            <p>REVENUE</p>
            <hr>
            <?php
            $products = get_all('loan_products');


            ?>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Interest</th>
                    <th>Administration Fees</th>
                    <th>Loan cover</th>
                    <th>Processing Fee</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 0;
                $af = 0;
                $lc = 0;
                $pf = 0;
                $t =0;
                $f = 0;
                foreach ($products as $product){

                    ?>
                    <tr>
                        <td><?php echo $product->product_name; ?></td>
                        <td style="text-align: left;">  <?php

                            $b =  get_all_interest_product($product->loan_product_id);
                            $round1 = round($b->total);
                            $i +=$round1;
                            echo number_format($round1,2); ?></td>
                        <td style="text-align: left;">  <?php
                            $c =  get_all_admin_fee_product($product->loan_product_id);


                            $round2 = round($c->total);
                            $af +=$round2;
                            echo number_format($round2,2); ?></td>
                        <td style="text-align: left;">  <?php
                            $d = get_all_lc_fee_product($product->loan_product_id);

                            $round3 = round($d->total);
                            $lc +=$round3;
                            echo number_format($round3,2); ?></td>
                        <td style="text-align: left;"> <?php
                            $fee = all_processing_fees($product->loan_product_id);
                            $f += $fee->total_fees;
                            echo  number_format($fee->total_fees,2);
                            ?>  </td>
                        <td style="text-align: left;">  <?php echo number_format($round1+$round2+$round3,2)?> </td>

                    </tr>
                    <?php
                    $t +=$round1+$round2+$round3;
                }
                ?>
                </tbody>
                <tfoot>
                <tr>
                    <th>TOTALS</th>
                    <th> <?php echo number_format($i,2) ;?></th>
                    <th><?php echo number_format($af,2) ;?></th>
                    <th> <?php echo number_format($lc,2); ?> </th>
                    <th> <?php echo number_format($f,2); ?> </th>
                    <th><?php echo number_format($t,2)?></th>
                </tr>
                </tfoot>

            </table>
            <hr>
            <p>BREAK DOWN</p>
            <table class="table">
                <thead>
                <tr>
                    <th>REVENUE STREAMS</th>
                    <th>CURRENT MONTH</th>
                    <th>CURRENT YEAR</th>
                    <th>PRIOR YEAR</th>
                </tr>
                </thead>
                <tr>
                    <td>LOAN INTEREST</td>
                    <td><?php
                        $mi = monthly_interest();
                        echo number_format($mi->totals,2);
                        ?>
                    </td>
                    <td><?php  $yi = yearly_interest(); echo number_format($yi->totals,2);?></td>
                    <td><?php  $pyi = pyearly_interest(); echo number_format($pyi->totals,2);?></td>
                </tr>
                <tr>

                    <td>ADMINISTRATION FEE</td>
                    <td><?php
                        $maf = monthly_af();
                        echo number_format($maf->totals,2);
                        ?>
                    </td>
                    <td><?php  $yaf = yearly_af(); echo number_format($yaf->totals,2);?></td>
                    <td><?php  $paf = pyearly_af(); echo number_format($paf->totals,2);?></td>
                </tr>
                <tr>

                    <td>LOAN COVER</td>
                    <td><?php
                        $mlc = monthly_lc();
                        echo number_format($mlc->totals,2);
                        ?>
                    </td>
                    <td><?php  $ylc = yearly_lc(); echo number_format($ylc->totals,2);?></td>
                    <td><?php  $plc = pyearly_lc(); echo number_format($plc->totals,2);?></td>
                </tr>
                <tr>

                    <td>PROCESSING FEES</td>
                    <td><?php  $pfees_m = processing_fees_month(); echo number_format($pfees_m->total_fees,2); ?></td>
                    <td><?php $pfees_y = processing_fees_year(); echo number_format($pfees_y->total_fees,2); ?></td>
                    <td><?php $pfees_py = processing_fees_pyear(); echo number_format($pfees_py->total_fees,2); ?></td>

                </tr>
                <tfoot>
                <tr>
                    <th>TOTALS</th>
                    <th><?php  echo number_format($mi->totals+$maf->totals+$mlc->totals,2)?></th>
                    <th><?php echo number_format($yi->totals+$yaf->totals+$ylc->totals, 2)?></th>
                    <th><?php  echo  number_format($pfees_m->total_fees+$pfees_y->total_fees+$pfees_py->total_fees,2)?></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
