<!-- Content Wrapper START -->
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Audit trail</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">Audit trail</a>
                <span class="breadcrumb-item active">System users trails</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick green solid;border-radius: 14px;">
            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body bg-light">
                    <form method="GET" action="<?php echo base_url('Activity_logger'); ?>" class="form-inline">
                        <div class="row w-100">
                            <div class="col-md-3 mb-2">
                                <label for="employee_id" class="sr-only">Employee</label>
                                <select name="employee_id" id="employee_id" class="form-control w-100">
                                    <option value="">All Employees</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee->id; ?>"
                                                <?php echo (isset($_GET['employee_id']) && $_GET['employee_id'] == $employee->id) ? 'selected' : ''; ?>>
                                            <?php echo $employee->Firstname . ' ' . $employee->Lastname; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="date_from" class="sr-only">Date From</label>
                                <input type="date" name="date_from" id="date_from" class="form-control w-100"
                                       placeholder="Date From" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="date_to" class="sr-only">Date To</label>
                                <input type="date" name="date_to" id="date_to" class="form-control w-100"
                                       placeholder="Date To" value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="anticon anticon-search"></i> Filter
                                </button>
                                <a href="<?php echo base_url('Activity_logger'); ?>" class="btn btn-secondary mr-2">
                                    <i class="anticon anticon-reload"></i> Reset
                                </a>
                            </div>
                        </div>
                        <div class="row w-100 mt-2">
                            <div class="col-md-12 mb-2">
                                <a href="<?php
                                    $export_url = base_url('Activity_logger/export_excel');
                                    $export_params = array();
                                    if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) $export_params[] = 'employee_id=' . urlencode($_GET['employee_id']);
                                    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) $export_params[] = 'date_from=' . urlencode($_GET['date_from']);
                                    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) $export_params[] = 'date_to=' . urlencode($_GET['date_to']);
                                    if (isset($_GET['search']) && !empty($_GET['search'])) $export_params[] = 'search=' . urlencode($_GET['search']);
                                    if (!empty($export_params)) {
                                        $export_url .= '?' . implode('&', $export_params);
                                    }
                                    echo $export_url;
                                ?>" class="btn btn-success">
                                    <i class="anticon anticon-file-excel"></i> Export to Excel
                                </a>
                            </div>
                        </div>
                        <div class="row w-100 mt-2">
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control"
                                       placeholder="Search activities..."
                                       value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-info">
                                    <i class="anticon anticon-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Pagination Info -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="text-muted">
                        Showing <?php echo $start; ?> to <?php echo $end; ?> of <?php echo $total_rows; ?> entries
                        <?php if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])): ?>
                            <span class="badge badge-info">Filtered by Employee</span>
                        <?php endif; ?>
                        <?php if (isset($_GET['date_from']) && !empty($_GET['date_from'])): ?>
                            <span class="badge badge-warning">Date Filtered</span>
                        <?php endif; ?>
                        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <span class="badge badge-success">Search: "<?php echo htmlspecialchars($_GET['search']); ?>"</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6 text-right">
                    <small class="text-muted">10 records per page</small>
                </div>
            </div>

            <div class="m-t-25">
                <table class="table table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Activity</th>
                        <th>Date & Time</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (!empty($activity_logger_data)) {
                        $serial = $start;
                        foreach ($activity_logger_data as $activity_logger)
                        {
                            ?>
                            <tr>
                                <td><?php echo $serial; ?></td>
                                <td>
                                    <strong><?php echo $activity_logger->Firstname ." ".$activity_logger->Lastname?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo $activity_logger->EmailAddress ?: 'N/A'; ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $activity_logger->role_name ?: 'No Role'; ?></span>
                                </td>
                                <td>
                                    <span class="text-primary"><?php echo $activity_logger->activity ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php
                                        if (!empty($activity_logger->server_time)) {
                                            echo date('M d, Y g:i A', strtotime($activity_logger->server_time));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </small>
                                </td>
                            </tr>
                            <?php
                            $serial++;
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="6" class="text-center">No activity logs found</td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <div id="pagination-container">
                        <?php echo $pagination_links; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


