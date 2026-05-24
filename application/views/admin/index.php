<style>
    .modern-dashboard {
        padding-bottom: 1.5rem;
        font-family: "Segoe UI", "Helvetica Neue", sans-serif;
    }

    .dashboard-hero {
        position: relative;
        overflow: hidden;
        border-radius: 14px;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
        background: linear-gradient(132deg, #0f6b43 0%, #1f8a59 55%, #2cab70 100%);
        color: #fff;
        box-shadow: 0 10px 24px rgba(8, 70, 42, 0.2);
    }

    .dashboard-hero:before,
    .dashboard-hero:after {
        content: "";
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.09);
        pointer-events: none;
    }

    .dashboard-hero:before {
        width: 190px;
        height: 190px;
        right: -45px;
        top: -55px;
    }

    .dashboard-hero:after {
        width: 120px;
        height: 120px;
        right: 140px;
        bottom: -50px;
    }

    .dashboard-hero h2 {
        color: #fff;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .dashboard-hero p {
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.85);
    }

    .hero-metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .hero-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border-radius: 999px;
        padding: 0.35rem 0.75rem;
        background-color: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.25);
        font-size: 0.83rem;
        color: #fff;
    }

    .stats-grid {
        margin-bottom: 1.35rem;
    }

    .stat-card {
        position: relative;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        transition: transform 0.22s ease, box-shadow 0.22s ease;
        height: 100%;
        overflow: hidden;
    }

    .stat-card:before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #0ea5e9, #0284c7);
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
    }

    .stat-card.stat-success:before { background: linear-gradient(90deg, #22c55e, #16a34a); }
    .stat-card.stat-warning:before { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .stat-card.stat-info:before { background: linear-gradient(90deg, #6366f1, #4f46e5); }

    .stat-body {
        padding: 1.05rem 1.15rem 1rem;
    }

    .stat-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.7rem;
    }

    .stat-title {
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        margin: 0;
    }

    .stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.96rem;
        background: #e0f2fe;
        color: #0c4a6e;
    }

    .stat-success .stat-icon { background: #dcfce7; color: #14532d; }
    .stat-warning .stat-icon { background: #fef3c7; color: #78350f; }
    .stat-info .stat-icon { background: #e0e7ff; color: #312e81; }

    .stat-value {
        font-size: 1.75rem;
        line-height: 1.15;
        color: #0f172a;
        font-weight: 800;
        margin-bottom: 0.25rem;
    }

    .stat-foot {
        font-size: 0.81rem;
        color: #64748b;
        margin: 0;
    }

    .dashboard-panel {
        border: 0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 7px 22px rgba(16, 24, 40, 0.08);
        height: 100%;
    }

    .dashboard-panel .card-header {
        background: #fff;
        border-bottom: 1px solid #edf1f5;
        padding: 0.95rem 1.1rem;
    }

    .dashboard-panel .card-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #172554;
    }

    .activity-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .activity-item {
        display: flex;
        gap: 0.8rem;
        padding: 0.9rem 1.1rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .activity-item:last-child {
        border-bottom: 0;
    }

    .activity-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #0f766e;
        background: #ccfbf1;
        flex-shrink: 0;
    }

    .activity-text {
        color: #334155;
        font-size: 0.92rem;
        line-height: 1.4;
        margin-bottom: 0.15rem;
    }

    .activity-time {
        font-size: 0.78rem;
        color: #94a3b8;
    }

    .reports-table {
        margin-bottom: 0;
    }

    .reports-table thead th {
        border-top: 0;
        border-bottom: 1px solid #e2e8f0;
        color: #475569;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .reports-table tbody td {
        vertical-align: middle;
    }

    .status-badge {
        border-radius: 999px;
        padding: 0.28rem 0.62rem;
        font-size: 0.74rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-completed {
        color: #166534;
        background: #dcfce7;
    }

    .status-pending {
        color: #92400e;
        background: #fef3c7;
    }

    .status-failed {
        color: #991b1b;
        background: #fee2e2;
    }

    .empty-state {
        padding: 1.4rem;
        text-align: center;
        color: #94a3b8;
    }

    @media (max-width: 767.98px) {
        .dashboard-hero {
            padding: 1.2rem;
        }

        .stat-value {
            font-size: 1.45rem;
        }
    }
</style>

<?php
$today = date('Y-m-d');

$total_reports = (int) $this->db->count_all('reports');

$completed_reports = (int) $this->db
    ->where('status', 'completed')
    ->from('reports')
    ->count_all_results();

$pending_reports = (int) $this->db
    ->where('status !=', 'completed')
    ->from('reports')
    ->count_all_results();

$my_today_activities = (int) $this->db
    ->where('user_id', $this->session->userdata('user_id'))
    ->where('DATE(server_time) =', $today)
    ->from('activity_logger')
    ->count_all_results();

$latest_report = $this->db
    ->select('generated_time')
    ->order_by('generated_time', 'DESC')
    ->limit(1)
    ->get('reports')
    ->row();

$latest_report_date = !empty($latest_report) ? date('M d, Y', strtotime($latest_report->generated_time)) : 'No report yet';

$this->db->order_by('server_time', 'DESC');
$this->db->where('user_id', $this->session->userdata('user_id'));
$this->db->limit(5);
$recent_activities = $this->db->get('activity_logger')->result();

$this->db->order_by('generated_time', 'DESC');
$this->db->limit(5);
$recent_reports = $this->db->get('reports')->result();
?>

<div class="main-content modern-dashboard">
    <div class="container-fluid">
        <div class="dashboard-hero">
            <h2>Welcome back, <?php echo $this->session->userdata('Firstname'); ?></h2>
            <p>Your operations summary for <?php echo date('l, M d, Y'); ?>.</p>
            <div class="hero-metrics">
                <span class="hero-chip"><i class="anticon anticon-calendar"></i> Reporting Day</span>
                <span class="hero-chip"><i class="anticon anticon-file-text"></i> Last Report: <?php echo $latest_report_date; ?></span>
                <span class="hero-chip"><i class="anticon anticon-clock-circle"></i> Live Dashboard</span>
            </div>
        </div>

        <div class="row stats-grid">
            <div class="col-xl-3 col-md-6 m-b-15">
                <div class="stat-card">
                    <div class="stat-body">
                        <div class="stat-head">
                            <p class="stat-title">Total Reports</p>
                            <span class="stat-icon"><i class="anticon anticon-bar-chart"></i></span>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_reports); ?></div>
                        <p class="stat-foot">Across all report types</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 m-b-15">
                <div class="stat-card stat-success">
                    <div class="stat-body">
                        <div class="stat-head">
                            <p class="stat-title">Completed</p>
                            <span class="stat-icon"><i class="anticon anticon-check-circle"></i></span>
                        </div>
                        <div class="stat-value"><?php echo number_format($completed_reports); ?></div>
                        <p class="stat-foot">Ready for download</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 m-b-15">
                <div class="stat-card stat-warning">
                    <div class="stat-body">
                        <div class="stat-head">
                            <p class="stat-title">Pending</p>
                            <span class="stat-icon"><i class="anticon anticon-sync"></i></span>
                        </div>
                        <div class="stat-value"><?php echo number_format($pending_reports); ?></div>
                        <p class="stat-foot">Awaiting completion</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 m-b-15">
                <div class="stat-card stat-info">
                    <div class="stat-body">
                        <div class="stat-head">
                            <p class="stat-title">My Activities Today</p>
                            <span class="stat-icon"><i class="anticon anticon-profile"></i></span>
                        </div>
                        <div class="stat-value"><?php echo number_format($my_today_activities); ?></div>
                        <p class="stat-foot">Tracked in activity logger</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-5 m-b-20">
                <div class="card dashboard-panel">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Recent Activities</h4>
                        <span class="text-muted small">Latest 5 entries</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_activities)) { ?>
                            <div class="empty-state">No recent activities found.</div>
                        <?php } else { ?>
                            <ul class="activity-list">
                                <?php foreach ($recent_activities as $activity) { ?>
                                    <li class="activity-item">
                                        <span class="activity-icon"><i class="anticon anticon-check"></i></span>
                                        <div>
                                            <div class="activity-text"><?php echo $activity->activity; ?></div>
                                            <div class="activity-time"><?php echo timeago($activity->server_time); ?></div>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 m-b-20">
                <div class="card dashboard-panel">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Recent Reports</h4>
                        <a href="<?php echo base_url('report'); ?>" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_reports)) { ?>
                            <div class="empty-state">No recent reports available.</div>
                        <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table reports-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_reports as $report) {
                                            $status_name = strtolower(trim($report->status));
                                            $status_class = 'status-pending';
                                            if ($status_name === 'completed') {
                                                $status_class = 'status-completed';
                                            } elseif ($status_name === 'failed') {
                                                $status_class = 'status-failed';
                                            }

<<<<<<< HEAD
                                            $preview_url = report_preview_url($report->download_link);
                                            $preview_btn = ($preview_url && $report->status === 'completed')
                                                ? '<a href="' . htmlspecialchars($preview_url) . '" target="_blank" rel="noopener" class="btn btn-sm btn-primary" title="Preview"><i class="anticon anticon-eye"></i></a>'
                                                : '<button type="button" disabled class="btn btn-sm btn-default" title="Preview unavailable"><i class="anticon anticon-eye"></i></button>';
=======
                                            $download_btn = ($report->download_link && $report->status === 'completed')
                                                ? '<a href="' . base_url('report/download/' . $report->id) . '" class="btn btn-sm btn-primary"><i class="anticon anticon-download"></i></a>'
                                                : '<button type="button" disabled class="btn btn-sm btn-default"><i class="anticon anticon-download"></i></button>';
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                                            ?>
                                            <tr>
                                                <td><?php echo $report->report_type; ?></td>
                                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $report->status; ?></span></td>
                                                <td><?php echo date('M d, Y', strtotime($report->generated_time)); ?></td>
<<<<<<< HEAD
                                                <td><?php echo $preview_btn; ?></td>
=======
                                                <td><?php echo $download_btn; ?></td>
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php
// Helper function for showing time ago
function timeago($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d >= 1) {
        return date('M d', strtotime($datetime));
    } elseif ($diff->h >= 1) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i >= 1) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'just now';
    }
}
?>