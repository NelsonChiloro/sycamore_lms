<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Loan Collections Report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">Reports</a>
                <span class="breadcrumb-item active">Loan Collections Report</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <form action="<?php echo base_url('collections_report/collections_filter') ?>" method="post">
                <fieldset>
                    <legend>Report filter</legend>
                    <div id="controlgroup">
                        Branch: <select name="branch" id="branch" class="select2">
                            <option value="All">All Branch</option>
                            <?php
                            foreach ($branches as $branch){
                                ?>
                                <option value="<?php echo $branch->id; ?>" <?php if(isset($selected_branch) && $selected_branch==$branch->id){ echo "selected"; }?>><?php echo $branch->BranchName; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        Loan Officer:
                        <select name="officer" id="officer" class="select2">
                            <option value="All">All Officers</option>
                            <?php
                            foreach ($users as $user){
                                ?>
                                <option value="<?php echo $user->id;?>" <?php if(isset($selected_officer) && $selected_officer==$user->id){echo 'selected';}  ?>><?php echo $user->Firstname." ".$user->Lastname;?></option>
                                <?php
                            }
                            ?>
                        </select>
                        Period:
                        <select name="period" id="period" class="select2">
                            <option value="">Custom</option>
                            <option value="today" <?php if(isset($selected_period) && $selected_period=="today"){echo 'selected';}  ?>>Today</option>
                            <option value="this_week" <?php if(isset($selected_period) && $selected_period=="this_week"){echo 'selected';}  ?>>This Week</option>
                            <option value="this_month" <?php if(isset($selected_period) && $selected_period=="this_month"){echo 'selected';}  ?>>This Month</option>
                            <option value="last_month" <?php if(isset($selected_period) && $selected_period=="last_month"){echo 'selected';}  ?>>Last Month</option>
                        </select>
                        Date from:<input type="text" id="date_from" class="dpicker" name="date_from"  >
                        Date to:<input type="text" id="date_to" class="dpicker" name="date_to"  >
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </fieldset>
            </form>
            <hr>
            <div class="alert alert-info">
                <p><i class="anticon anticon-info-circle"></i> Generated reports will be available in the Reports section. You will be redirected there after submitting the form.</p>
            </div>
        </div>
    </div>
</div>
