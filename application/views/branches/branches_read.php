<div class="main-content">
    <div class="page-header"><h2 class="header-title">Branch Details</h2></div>
    <div class="card"><div class="card-body">
        <table class="table">
            <?php foreach(array('BranchCode'=>'Branch code','BranchName'=>'Branch name','AddressLine1'=>'Address line 1','AddressLine2'=>'Address line 2','City'=>'City','Stamp'=>'Last updated') as $field=>$label): ?>
            <tr><th><?php echo $label; ?></th><td><?php echo htmlspecialchars((string)($$field??''),ENT_QUOTES,'UTF-8'); ?></td></tr>
            <?php endforeach; ?>
        </table>
        <a href="<?php echo site_url('branches/update/'.(int)$id); ?>" class="btn btn-primary">Update Branch</a>
        <a href="<?php echo site_url('branches'); ?>" class="btn btn-default">Back to Branches</a>
    </div></div>
</div>