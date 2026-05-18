<div class="main-content">
    <div class="page-header">
        <h2 class="header-title"> Approval preview</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active"> Preview</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <div class="row">
                <div class="col-lg-12 border-right">
                    <div class="row">
                        <div class="col-lg-5">
                            <h4>Old Data</h4>
                            <table class="table table-editable">
                                <thead class="bg-primary text-white">
                                <tr>
                                    <th>Key</th>
                                    <th>Value</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($old_info as $key=>$value){
                                    ?>
                                    <tr>
                                        <td><?php echo $key;?></td>
                                        <td class="bg-warning text-white"><?php echo $value?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                </tbody>

                            </table>
                        </div>
                        <div class="col-lg-5">
                            <h4>New Data</h4>
                            <table class="table table-editable">
                                <thead class="btn-primary text-white">
                                <tr>
                                    <th>Key</th>
                                    <th >Value</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $prefix = "sy_";
                                foreach ($new_info as $keys=>$values){
                                    if (strpos($keys, $prefix) === 0) {
                                        // Prefix exists

                                    } else {
                                        // Prefix does not exist

                                    ?>
                                    <tr>
                                        <td><?php echo $keys;?></td>
                                        <td class="bg-danger text-white"><?php echo $values?></td>
                                    </tr>
                                    <?php
                                    }
                                }
                                ?>

                                </tbody>

                            </table>
                        </div>
                        <div class="col-lg-2">
                            <h4>Auth actions</h4>
                            <?php
                            if($state=="Initiated"){
                            ?>
                            <form action="<?php echo base_url('Approval_general/').$action_recommend ?>" method="post">
                                <textarea required class="form-control" name="comment" id="" cols="30" rows="5" placeholder="write comment of your action"></textarea>
                                <input type='hidden' value="<?php echo $id ?>" name="id">

                                    <input type="submit" name="approval" class="btn btn-primary" onclick="return confirm('Are you sure you want to reject this?')" value="Reject">
                                    <input onclick="return confirm('Are you sure you want to Recommend this?')" name="approval" type="submit" class="btn btn-success" value="recommend">

                            </form>
                                <?php
                            }
                            ?>
                            <?php
                            if($state=="recommended"){
                                ?>
                                <form action="<?php echo base_url('Approval_general/').$action_approve ?>" method="post">
                                    <textarea required class="form-control" name="comment" id="" cols="30" rows="5" placeholder="write comment of your action"></textarea>
                                    <input type='hidden' value="<?php echo $id ?>" name="id">

                                    <input type="submit" name="reject" class="btn btn-primary" onclick="return confirm('Are you sure you want to reject this?')" value="Reject">
                                    <input onclick="return confirm('Are you sure you want to Recommend this?')" name="approval" type="submit" class="btn btn-success" value="Approve">




                                </form>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>