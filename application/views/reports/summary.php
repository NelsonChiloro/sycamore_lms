<?php
$logs = isset($logs) ? $logs : get_logs('activity_logger','user_id',$this->session->userdata('user_id'));
$settings = isset($settings) ? $settings : get_by_id('settings','settings_id','1');
$summary_stats = isset($summary_stats) && is_array($summary_stats) ? $summary_stats : array();
$product_balances = isset($product_balances) && is_array($product_balances) ? $product_balances : array();
$currency = isset($settings->currency) ? $settings->currency : '';

$paid_interest = isset($summary_stats['paid_interest']) ? (float) $summary_stats['paid_interest'] : 0;
$paid_lc = isset($summary_stats['paid_lc']) ? (float) $summary_stats['paid_lc'] : 0;
$paid_af = isset($summary_stats['paid_af']) ? (float) $summary_stats['paid_af'] : 0;
$outstanding_interest = isset($summary_stats['outstanding_interest']) ? (float) $summary_stats['outstanding_interest'] : 0;
$outstanding_lc = isset($summary_stats['outstanding_lc']) ? (float) $summary_stats['outstanding_lc'] : 0;
$outstanding_af = isset($summary_stats['outstanding_af']) ? (float) $summary_stats['outstanding_af'] : 0;
$total_unpaid = isset($summary_stats['total_unpaid']) ? (float) $summary_stats['total_unpaid'] : 0;
$total_arrears = isset($summary_stats['total_arrears']) ? (float) $summary_stats['total_arrears'] : 0;
$one_day_arrears = isset($summary_stats['one_day_arrears']) ? (float) $summary_stats['one_day_arrears'] : 0;
$three_day_arrears = isset($summary_stats['three_day_arrears']) ? (float) $summary_stats['three_day_arrears'] : 0;
$week_arrears = isset($summary_stats['week_arrears']) ? (float) $summary_stats['week_arrears'] : 0;
$month_arrears = isset($summary_stats['month_arrears']) ? (float) $summary_stats['month_arrears'] : 0;
$two_month_arrears = isset($summary_stats['two_month_arrears']) ? (float) $summary_stats['two_month_arrears'] : 0;
$three_month_arrears = isset($summary_stats['three_month_arrears']) ? (float) $summary_stats['three_month_arrears'] : 0;
$payments_today_total = isset($summary_stats['payments_today']) ? (float) $summary_stats['payments_today'] : 0;
$payments_week_total = isset($summary_stats['payments_week']) ? (float) $summary_stats['payments_week'] : 0;
$payments_month_total = isset($summary_stats['payments_month']) ? (float) $summary_stats['payments_month'] : 0;
?>
<div class="main-content">
    <div class="page-header no-gutters has-tab" style="margin-bottom: 2px !important;">
        <h2 class="font-weight-normal">DASHBOARD SUMMARY</h2>

    </div>
    <?php

    $show = false;
    foreach ($this->session->userdata('access') as $r) {
        if ($r->controllerid == 113) {
            $show = true;
            break;
        }
    }
    ?>
    <?php
    if($show){
        ?>
        <div class="row">
            <div class="col-lg-12">
                <h2 class="heading">Revenue</h2>
                <hr class="dash" >
            </div>
        </div>
        <div class="row">

            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <a class="dashboard-stat green" href="<?php echo base_url('Loan/loan_revenue') ?>">
                    <div class="visual">

                    </div>
                    <div class="details">
                        <div class="number">
                        <span><?php echo $currency ?> <?php echo number_format(round($paid_interest),2); ?></span>
                        </div>
                        <div class="desc">Total Paid/collected Interests on all Loans</div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <a class="dashboard-stat green" href="<?php echo base_url('Loan/loan_revenue') ?>">
                    <div class="visual">
                        <i class="fa fa-bar-chart-o"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                        <span><?php echo $currency ?> <?php echo number_format(round($paid_lc),2); ?></span>
                        </div>
                        <div class="desc">Total paid/collected loan cover  on all Loans</div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <a class="dashboard-stat green" href="<?php echo base_url('Loan/loan_revenue') ?>">
                    <div class="visual">
                        <i class="fa fa-bar-chart"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                        <span><?php echo $currency ?> <?php echo number_format(round($paid_af),2); ?></span>
                        </div>
                        <div class="desc">Total paid/collected loan administration fees on all Loans <br/></div>


                    </div>
                </a>
            </div>


        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2 class="heading">Balances</h2>
                <hr class="dash" >
            </div>
        </div>
        <div class="row">

            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <a class="dashboard-stat hoki" href="<?php echo base_url('Loan/balances') ?>">
                    <div class="visual">
                        <i class="fa fa-bar-chart-o"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                        <span><?php echo $currency ?> <?php echo number_format(round($outstanding_interest),2); ?></span>
                        </div>
                        <div class="desc">Total outstanding Interests on all Loans</div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <a class="dashboard-stat hoki" href="<?php echo base_url('Loan/balances') ?>">
                    <div class="visual">
                        <i class="fa fa-bar-chart-o"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                        <span><?php echo $currency ?> <?php echo number_format(round($outstanding_lc),2); ?></span>
                        </div>
                        <div class="desc">Total outstanding loan cover balances on all Loans</div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <a class="dashboard-stat hoki" href="<?php echo base_url('Loan/balances') ?>">
                    <div class="visual">
                        <i class="fa fa-bar-chart-o"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                        <span><?php echo $currency ?> <?php echo number_format(round($outstanding_af),2); ?></span>
                        </div>
                        <div class="desc">Total outstanding loan administration balance on all Loans</div>
                    </div>
                </a>
            </div>


        </div>
        <div class="row">
            <div class="col-lg-12">
                <h2 class="heading">Loan Product-wise- outstanding balances</h2>
                <hr class="dash" >
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <a class="dashboard-stat purple" href="<?php echo base_url('Loan/balances') ?>">
                    <div class="visual">
                        <i class="fa fa-usd"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                        <span><?php echo $currency ?> <?php echo number_format(round($total_unpaid),2); ?></span>
                        </div>
                        <div class="desc">Total Institutional Portfolio-outstanding balances</div>
                    </div>
                </a>
            </div>
            <?php
            foreach ($product_balances as $product){
                ?>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                    <a class="dashboard-stat blue" href="<?php echo base_url('Loan/balances?product=').$product->loan_product_id; ?>">
                        <div class="visual">
                            <i class="fa fa-usd"></i>
                        </div>
                        <div class="details">
                            <div class="number">
                        <span><?php echo $currency ?> <?php echo number_format(round((float) $product->outstanding_principal),2); ?></span>
                            </div>
                            <div class="desc"><?php echo $product->product_name. " (".$product->product_code.")"; ?></div>
                        </div>
                    </a>
                </div>
                <?php
            }
            ?>

        </div>

        <div class="row">
            <div class="col-lg-12">
                <h2 class="heading float-left">Arrears</h2> <h2 class="heading float-right">Payments Due</h2>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 border-right border-success">

                <div class="row">
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat hoki" href="<?php echo base_url('Reports/arrears?by_date=All&loan=All')?>">
                            <div class="visual">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($total_arrears),2); ?></span>
                                </div>
                                <div class="desc">Total Arrears</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat red" href="<?php echo base_url('Reports/arrears?by_date=one_day&loan=All')?>">
                            <div class="visual">
                                <i class="fa fa-usd"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($one_day_arrears),2); ?></span>
                                </div>
                                <div class="desc">One day arrears</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat red" href="<?php echo base_url('Reports/arrears?by_date=three_days&loan=All')?>">
                            <div class="visual">
                                <i class="fa fa-bar-chart-o"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($three_day_arrears),2); ?></span>
                                </div>
                                <div class="desc">Three days Arrears</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat red" href="<?php echo base_url('Reports/arrears?by_date=week&loan=All')?>">
                            <div class="visual">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($week_arrears),2); ?></span>
                                </div>
                                <div class="desc">One week Arrears</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat red" href="<?php echo base_url('Reports/arrears?by_date=month&loan=All')?>">
                            <div class="visual">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($month_arrears),2); ?></span>
                                </div>
                                <div class="desc">One Month Arrears</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat red" href="<?php echo base_url('Reports/arrears?by_date=2month&loan=All')?>">
                            <div class="visual">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($two_month_arrears),2); ?></span>
                                </div>
                                <div class="desc">Two Months Arrears</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat red" href="<?php echo base_url('Reports/arrears?by_date=3month&loan=All')?>">
                            <div class="visual">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($three_month_arrears),2); ?></span>
                                </div>
                                <div class="desc">Three Months Arrears</div>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
            <div class="col-lg-6">

                <div class="row">
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat orange" href="<?php echo base_url('Reports/to_pay_today')?>">
                            <div class="visual">
                                <i class="fa fa-usd"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($payments_today_total),2); ?></span>
                                </div>
                                <div class="desc">Payments due today</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat orange" href="<?php echo base_url('Reports/to_pay_week')?>">
                            <div class="visual">
                                <i class="fa fa-bar-chart-o"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($payments_week_total),2); ?></span>
                                </div>
                                <div class="desc">Payment due this week</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a class="dashboard-stat orange" href="<?php echo base_url('Reports/to_pay_month')?>">
                            <div class="visual">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <div class="details">
                                <div class="numberr">
                        <span><?php echo $currency ?> <?php echo number_format(round($payments_month_total),2); ?></span>
                                </div>
                                <div class="desc">Payment due this month</div>
                            </div>
                        </a>
                    </div>


                </div>
            </div>
        </div>





        <?php
//        $p=0;
//        $p1=0;
//        $totaldisb=0;
//        $gt=$this->Loan_model->sum_total_par();
//        $gt2=$this->Loan_model->sum_total2($this->session->userdata('officerid'));
//        foreach ($gt as $tamt){
//            $totaldisb +=$tamt->lm;
//        }
//        foreach ($gt2 as $tamt2){
//            if($tamt2->paid_amount >=$tamt2->principal){
////       $p = $tamt2->principal;
//                $p=0;
//                $p1 +=$p;
//
//            }elseif($tamt2->paid_amount < $tamt2->principal){
//                $p = $tamt2->principal-$tamt2->paid_amount;
//                $p1 +=$p;
//
//            }
//
//        }

        ?>




        <!--        <div class="row">-->
        <!--            <div class="col-lg-12">-->
        <!--                <h2 class="heading">Portfolio At Risk</h2>-->
        <!--                <hr class="dash" >-->
        <!--            </div>-->
        <!--        </div>-->
        <!--        <div class="row">-->
        <!--            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">-->
        <!--                <a class="dashboard-stat hoki" href="#">-->
        <!--                    <div class="visual">-->
        <!--                        <i class="fa fa-usd"></i>-->
        <!--                    </div>-->
        <!--                    <div class="details">-->
        <!--                        <div class="number">-->
        <!--                        <span>--><?php //echo $settings->currency?><!-- --><?php
//
//                            echo number_format($tzerotoseven,2);
//                            ?><!--</span>-->
        <!--                        </div>-->
        <!--                        <div class="desc">0 - 7 Days PAR</div>-->
        <!--                    </div>-->
        <!--                </a>-->
        <!--            </div>-->
        <!--            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">-->
        <!--                <a class="dashboard-stat hoki" href="#">-->
        <!--                    <div class="visual">-->
        <!--                        <i class="fa fa-bar-chart-o"></i>-->
        <!--                    </div>-->
        <!--                    <div class="details">-->
        <!--                        <div class="number">-->
        <!--                        <span>--><?php //echo $settings->currency?><!-- --><?php
//
//                            echo number_format($morethanseven,2);
//                            ?><!--</span>-->
        <!--                        </div>-->
        <!--                        <div class="desc">AGED 8- 30 Days PAR</div>-->
        <!--                    </div>-->
        <!--                </a>-->
        <!--            </div>-->
        <!--            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">-->
        <!--                <a class="dashboard-stat hoki" href="#">-->
        <!--                    <div class="visual">-->
        <!--                        <i class="fa fa-credit-card"></i>-->
        <!--                    </div>-->
        <!--                    <div class="details">-->
        <!--                        <div class="number">-->
        <!--                        <span>--><?php //echo $settings->currency?><!-- --><?php
//
//                            echo number_format(round($morethanthirty),2);
//                            ?><!--</span>-->
        <!--                        </div>-->
        <!--                        <div class="desc">AGED 31 - 60 Days PAR</div>-->
        <!--                    </div>-->
        <!--                </a>-->
        <!--            </div>-->
        <!--            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">-->
        <!--                <a class="dashboard-stat hoki" href="#">-->
        <!--                    <div class="visual">-->
        <!--                        <i class="fa fa-credit-card"></i>-->
        <!--                    </div>-->
        <!--                    <div class="details">-->
        <!--                        <div class="number">-->
        <!--                        <span>--><?php //echo $settings->currency?><!-- --><?php
//
//                            echo number_format(round($morethansixty),2);
//                            ?><!--</span>-->
        <!--                        </div>-->
        <!--                        <div class="desc">AGED 61 - 90 Days PAR</div>-->
        <!--                    </div>-->
        <!--                </a>-->
        <!--            </div>-->
        <!--            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">-->
        <!--                <a class="dashboard-stat hoki" href="#">-->
        <!--                    <div class="visual">-->
        <!--                        <i class="fa fa-credit-card"></i>-->
        <!--                    </div>-->
        <!--                    <div class="details">-->
        <!--                        <div class="number">-->
        <!--                        <span>--><?php //echo $settings->currency?><!-- --><?php
//
//                            echo number_format(round($morethanninety),2);
//                            ?><!--</span>-->
        <!--                        </div>-->
        <!--                        <div class="desc">AGED 91 - 120 Days PAR</div>-->
        <!--                    </div>-->
        <!--                </a>-->
        <!--            </div>-->
        <!--            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">-->
        <!--                <a class="dashboard-stat hoki" href="#">-->
        <!--                    <div class="visual">-->
        <!--                        <i class="fa fa-credit-card"></i>-->
        <!--                    </div>-->
        <!--                    <div class="details">-->
        <!--                        <div class="number">-->
        <!--                        <span>--><?php //echo $settings->currency?><!-- --><?php
//
//                            echo number_format(round($morethanonetwenty),2);
//                            ?><!--</span>-->
        <!--                        </div>-->
        <!--                        <div class="desc">AGED 121 - 180 Days PAR</div>-->
        <!--                    </div>-->
        <!--                </a>-->
        <!--            </div>-->
        <!--            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">-->
        <!--                <a class="dashboard-stat hoki" href="#">-->
        <!--                    <div class="visual">-->
        <!--                        <i class="fa fa-credit-card"></i>-->
        <!--                    </div>-->
        <!--                    <div class="details">-->
        <!--                        <div class="number">-->
        <!--                        <span>--><?php //echo $settings->currency?><!-- --><?php
//
//                            echo number_format(round($morethanoneeighty),2);
//                            ?><!--</span>-->
        <!--                        </div>-->
        <!--                        <div class="desc">AGED 181 - 366 Days PAR</div>-->
        <!--                    </div>-->
        <!--                </a>-->
        <!--            </div>-->
        <!--            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">-->
        <!--                <a class="dashboard-stat hoki" href="#">-->
        <!--                    <div class="visual">-->
        <!--                        <i class="fa fa-credit-card"></i>-->
        <!--                    </div>-->
        <!--                    <div class="details">-->
        <!--                        <div class="number">-->
        <!--                        <span>--><?php //echo $settings->currency?><!-- --><?php
//
//                            echo number_format(round($morethanthreesixty),2);
//                            ?><!--</span>-->
        <!--                        </div>-->
        <!--                        <div class="desc">More than 366 Days PAR</div>-->
        <!--                    </div>-->
        <!--                </a>-->
        <!--            </div>-->
        <!---->
        <!--        </div>-->

        <?php
    }else{
        ?>
        <ul class="nav nav-tabs" >
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tab-account">Account</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-network">Recent activity logs</a>
            </li>

        </ul>
        <div class="container">
            <div class="tab-content m-t-15">
                <div class="tab-pane fade show active" id="tab-account" >
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Basic Information</h4>
                        </div>
                        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
                            <div class="media align-items-center">
                                <div class="avatar avatar-image  m-h-10 m-r-15" style="height: 80px; width: 80px">
                                    <img src="<?php echo base_url('uploads')?>/avatar-3.png" alt="">
                                </div>

                            </div>
                            <hr class="m-v-25">
                            <form>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-semibold" for="userName">User Name:</label>
                                        <input type="text" class="form-control" id="userName" disabled placeholder="User Name" value="<?php  echo  $this->session->userdata('username')?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-semibold" for="email">Full name:</label>
                                        <input type="text"  disabled class="form-control" id="email" placeholder="email" value="<?php echo  $this->session->userdata('Firstname')."".$this->session->userdata('Lastname') ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-semibold" for="phoneNumber">Designation:</label>
                                        <input type="text" class="form-control" disabled id="phoneNumber" placeholder="Phone Number" value="<?php echo  $this->session->userdata('RoleName') ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-semibold" for="dob">Date Joined:</label>
                                        <input type="text" class="form-control" disabled id="dob" placeholder="<?php echo $this->session->userdata('stamp');?>">
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>


                </div>
                <div class="tab-pane fade" id="tab-network">
                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">My system logs</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table">
                                        <thead>
                                        <tr>
                                            <th>Date time</th>
                                            <th>Activity</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php

                                        foreach ($logs as $log){
                                            ?>
                                            <tr>
                                                <td><?php  echo $log->server_time; ?></td>
                                                <td><?php echo $log->activity; ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php
    }
    ?>


</div>
