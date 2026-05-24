<style>
    #supervisor-map-table { color: #212529; background: #fff; }
    #supervisor-map-table thead th {
        background-color: #153505;
        color: #fff;
        border-color: #0d2503;
    }
    #supervisor-map-table tbody tr {
        background-color: #fff !important;
        color: #212529 !important;
    }
    #supervisor-map-table tbody tr:nth-child(even) {
        background-color: #f5f7fa !important;
    }
    #supervisor-map-table tbody td,
    #supervisor-map-table tbody select {
        color: #212529 !important;
        background-color: #fff;
    }
</style>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Map Relationship Officers to Supervisors</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin'); ?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="<?php echo site_url('employees'); ?>">Employees</a>
                <span class="breadcrumb-item active">Supervisor mapping</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick orange solid; border-radius: 14px;">
            <p class="text-muted mb-3">
                Assign each <strong>Relationship officer</strong> to a <strong>Relationship supervisor</strong>.
            </p>
            <a href="<?php echo site_url('employees'); ?>" class="btn btn-default btn-sm mb-3">Back to employees</a>

            <?php if (empty($officers)) { ?>
                <div class="alert alert-warning">No employees with the Relationship officer role were found.</div>
            <?php } elseif (empty($supervisors)) { ?>
                <div class="alert alert-warning">No employees with the Relationship supervisor role were found. Create supervisors first.</div>
            <?php } else { ?>
                <form action="<?php echo site_url('employees/map_supervisors_action'); ?>" method="post">
                    <div class="form-group mb-3" style="max-width: 360px;">
                        <label for="officer-map-search">Search Relationship officer</label>
                        <input type="text" id="officer-map-search" class="form-control" placeholder="Type name to filter…" autocomplete="off">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="supervisor-map-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Relationship officer</th>
                                    <th>Current supervisor</th>
                                    <th>Assign supervisor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $n = 1; foreach ($officers as $officer) {
                                    $current = '';
                                    if (!empty($officer->supervisor_firstname) || !empty($officer->supervisor_lastname)) {
                                        $current = trim($officer->supervisor_firstname . ' ' . $officer->supervisor_lastname);
                                    } elseif (!empty($officer->Supervisor)) {
                                        $current = 'ID ' . (int) $officer->Supervisor;
                                    } else {
                                        $current = '—';
                                    }
                                    ?>
                                    <?php
                                    $officer_name = trim($officer->Firstname . ' ' . $officer->Lastname);
                                    ?>
                                    <tr data-officer-name="<?php echo htmlspecialchars($officer_name); ?>">
                                        <td class="row-num"><?php echo $n++; ?></td>
                                        <td><?php echo htmlspecialchars($officer_name); ?></td>
                                        <td><?php echo htmlspecialchars($current); ?></td>
                                        <td>
                                            <select name="supervisor[<?php echo (int) $officer->id; ?>]" class="form-control form-control-sm">
                                                <option value="">— None —</option>
                                                <?php foreach ($supervisors as $sup) {
                                                    $sel = ((int) $officer->Supervisor === (int) $sup->id) ? ' selected' : '';
                                                    ?>
                                                    <option value="<?php echo (int) $sup->id; ?>"<?php echo $sel; ?>>
                                                        <?php echo htmlspecialchars(trim($sup->Firstname . ' ' . $sup->Lastname)); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">Save mappings</button>
                </form>
            <?php } ?>
        </div>
    </div>
</div>
<script>
(function () {
    var input = document.getElementById('officer-map-search');
    var table = document.getElementById('supervisor-map-table');
    if (!input || !table) {
        return;
    }
    function filterRows() {
        var q = input.value.toLowerCase().replace(/\s+/g, ' ').trim();
        var rows = table.querySelectorAll('tbody tr');
        var n = 0;
        rows.forEach(function (tr) {
            var name = (tr.getAttribute('data-officer-name') || '').toLowerCase();
            var show = q === '' || name.indexOf(q) !== -1;
            tr.style.display = show ? '' : 'none';
            if (show) {
                n++;
                var numCell = tr.querySelector('.row-num');
                if (numCell) {
                    numCell.textContent = n;
                }
            }
        });
    }
    input.addEventListener('input', filterRows);
})();
</script>
