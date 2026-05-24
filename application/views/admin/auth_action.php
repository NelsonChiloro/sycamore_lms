<div class="main-content">
    <div class="page-header">
<<<<<<< HEAD
        <h2 class="header-title"> Approval preview<?php if (!empty($is_group_batch_edit)): ?> — Group batch<?php endif; ?></h2>
=======
        <h2 class="header-title"> Approval preview</h2>
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
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
<<<<<<< HEAD
            <?php if (!empty($is_group_batch_edit)): ?>
            <div class="alert alert-info">
                <strong>Group batch edit:</strong>
                <?php echo htmlspecialchars((string) ($old_info->batch ?? $new_info->batch ?? '')); ?>
                <?php if (!empty($summary)): ?> — <?php echo htmlspecialchars((string) $summary); ?><?php endif; ?>
            </div>
            <?php if (!empty($new_info->shared) && is_object($new_info->shared)): ?>
            <h5 class="mt-3">Shared settings (all members)</h5>
            <table class="table table-bordered table-sm mb-4">
                <thead class="thead-light"><tr><th>Setting</th><th>New value</th></tr></thead>
                <tbody>
                <?php foreach ($new_info->shared as $skey => $sval): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $skey); ?></td>
                        <td><?php echo approval_preview_cell($sval); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <h5>Member changes</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Loan #</th>
                            <th>Customer</th>
                            <th>Principal (old → new)</th>
                            <th>Period (old → new)</th>
                            <th>Date (old → new)</th>
                            <th>Officer (old → new)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $old_members = isset($old_info->members) && is_array($old_info->members) ? $old_info->members : array();
                    $new_members = isset($new_info->members) && is_array($new_info->members) ? $new_info->members : array();
                    $new_by_loan = array();
                    foreach ($new_members as $nm) {
                        if (is_object($nm) && !empty($nm->loan_id)) {
                            $new_by_loan[(int) $nm->loan_id] = $nm;
                        }
                    }
                    foreach ($old_members as $om):
                        if (!is_object($om)) {
                            continue;
                        }
                        $lid = (int) ($om->loan_id ?? 0);
                        $nm = isset($new_by_loan[$lid]) ? $new_by_loan[$lid] : null;
                        $fmt_pair = function ($old_v, $new_v) {
                            $o = is_scalar($old_v) ? htmlspecialchars((string) $old_v) : approval_preview_cell($old_v);
                            $n = ($new_v === null) ? '&mdash;' : (is_scalar($new_v) ? htmlspecialchars((string) $new_v) : approval_preview_cell($new_v));
                            return $o . ' &rarr; <strong>' . $n . '</strong>';
                        };
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($om->loan_number ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($om->loan_customer ?? '')); ?></td>
                            <td><?php echo $fmt_pair($om->loan_principal ?? '', $nm ? ($nm->loan_principal ?? '') : ''); ?></td>
                            <td><?php echo $fmt_pair($om->loan_period ?? '', $nm ? ($nm->loan_period ?? '') : ''); ?></td>
                            <td><?php echo $fmt_pair($om->loan_date ?? '', $nm ? ($nm->loan_date ?? '') : ''); ?></td>
                            <td><?php echo $fmt_pair($om->loan_added_by ?? '', $nm ? ($nm->added_by ?? '') : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
=======
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
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
<<<<<<< HEAD
                                $skip_old = !empty($is_group_batch_edit) ? array('members') : array();
                                foreach ((array) $old_info as $key => $value) {
                                    if (in_array($key, $skip_old, true)) {
                                        continue;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $key); ?></td>
                                        <td class="bg-warning text-white"><?php echo approval_preview_cell($value); ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
=======
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

>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                            </table>
                        </div>
                        <div class="col-lg-5">
                            <h4>New Data</h4>
                            <table class="table table-editable">
                                <thead class="btn-primary text-white">
                                <tr>
                                    <th>Key</th>
<<<<<<< HEAD
                                    <th>Value</th>
=======
                                    <th >Value</th>
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                                </tr>
                                </thead>
                                <tbody>
                                <?php
<<<<<<< HEAD
                                $prefix = 'sy_';
                                $skip_new = !empty($is_group_batch_edit) ? array('members', 'shared') : array();
                                foreach ((array) $new_info as $keys => $values) {
                                    if (strpos((string) $keys, $prefix) === 0) {
                                        continue;
                                    }
                                    if (in_array($keys, $skip_new, true)) {
                                        continue;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $keys); ?></td>
                                        <td class="bg-danger text-white"><?php echo approval_preview_cell($values); ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
=======
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

>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                            </table>
                        </div>
                        <div class="col-lg-2">
                            <h4>Auth actions</h4>
<<<<<<< HEAD
                            <?php if ($state == 'Initiated'): ?>
                            <form action="<?php echo base_url('Approval_general/') . $action_recommend; ?>" method="post">
                                <textarea required class="form-control" name="comment" cols="30" rows="5" placeholder="write comment of your action"></textarea>
                                <input type="hidden" value="<?php echo (int) $id; ?>" name="id">
                                <input type="submit" name="approval" class="btn btn-primary mt-2" onclick="return confirm('Are you sure you want to reject this?')" value="Reject">
                                <input onclick="return confirm('Are you sure you want to Recommend this?')" name="approval" type="submit" class="btn btn-success mt-2" value="recommend">
                            </form>
                            <?php endif; ?>
                            <?php if ($state == 'recommended'): ?>
                            <form action="<?php echo base_url('Approval_general/') . $action_approve; ?>" method="post">
                                <textarea required class="form-control" name="comment" cols="30" rows="5" placeholder="write comment of your action"></textarea>
                                <input type="hidden" value="<?php echo (int) $id; ?>" name="id">
                                <input type="submit" name="reject" class="btn btn-primary mt-2" onclick="return confirm('Are you sure you want to reject this?')" value="Reject">
                                <input onclick="return confirm('Are you sure you want to Approve this?')" name="approval" type="submit" class="btn btn-success mt-2" value="Approve">
                            </form>
                            <?php endif; ?>
=======
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
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<<<<<<< HEAD
</div>
=======
</div>
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
