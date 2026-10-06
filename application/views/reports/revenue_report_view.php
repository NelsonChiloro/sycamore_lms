
    <!-- Include Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.js" integrity="sha512-L0Shl7nXXzIlBSUUPpxrokqq4ojqgZFQczTYlGjzONGTDAcLremjwaWv5A+EDLnxhQzY5xUZPWLOLqYRkY0Cbw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <style>
        .report-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .page-title {
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .export-buttons {
            margin-bottom: 15px;
        }
        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }
        .stats-card {
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
        }
        .total-revenue {
            background-color: #2ecc71;
        }
        .interest-income {
            background-color: #3498db;
        }
        .fee-income {
            background-color: #9b59b6;
        }
        .other-income {
            background-color: #e67e22;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
            margin-bottom: 30px;
        }
        .total-row {
            background-color: #eaf4fd;
            font-weight: bold;
        }
        .kwacha {
            position: relative;
        }
        .kwacha:before {
            content: "K";
            position: absolute;
            left: 0;
        }
        .kwacha-value {
            padding-left: 10px;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-4 mb-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-title">
                <i class="fa fa-chart-line"></i> Revenue Analysis Report
            </h1>

            <div class="report-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Report Parameters:</h5>
                        <p><strong>Date Range:</strong> <?php echo $date_range_display; ?></p>
                        <p><strong>Loan Officer:</strong> <?php echo $officer_name; ?></p>
                        <p><strong>Branch:</strong> <?php echo $branch_name; ?></p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p><strong>Generated on:</strong> <?php echo date('d M Y H:i:s'); ?></p>
                    </div>
                </div>
            </div>

            <div class="export-buttons text-right">

                <a href="<?php echo site_url('revenue_report'); ?>" class="btn btn-secondary">
                    <i class="fa fa-filter"></i> Change Filters
                </a>
            </div>

            <?php if (empty($revenue_data)): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No revenue data found for the selected criteria.
                </div>
            <?php else: ?>
                <!-- Summary stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card total-revenue">
                            <h5><i class="fa fa-money-bill"></i> Total Revenue</h5>
                            <h2>K<?php echo number_format($totals['total'], 2); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card interest-income">
                            <h5><i class="fa fa-percentage"></i> Interest Income</h5>
                            <h2>K<?php echo number_format($totals['interest'], 2); ?></h2>
                            <p><?php echo number_format(($totals['interest'] / $totals['total']) * 100, 1); ?>% of total</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card fee-income">
                            <h5><i class="fa fa-file-invoice"></i> Fee Income</h5>
                            <?php
                            $fee_income = $totals['admin_fees'] + $totals['processing_fees'] + $totals['loan_cover'];
                            ?>
                            <h2>K<?php echo number_format($fee_income, 2); ?></h2>
                            <p><?php echo number_format(($fee_income / $totals['total']) * 100, 1); ?>% of total</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card other-income">
                            <h5><i class="fa fa-coins"></i> Other Income</h5>
                            <?php
                            $other_income = $totals['penalties'] + $totals['write_off'];
                            ?>
                            <h2>K<?php echo number_format($other_income, 2); ?></h2>
                            <p><?php echo number_format(($other_income / $totals['total']) * 100, 1); ?>% of total</p>
                        </div>
                    </div>
                </div>

                <!-- Revenue Distribution Chart -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Revenue Composition</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueCompositionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Revenue by Branch</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueBranchChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Detailed Revenue Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table  table-bordered table-hover" id="data-table1">
                                <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Product</th>
                                    <th>Interest</th>
                                    <th>Admin Fees</th>
                                    <th>Loan Cover</th>
                                    <th>Processing Fees</th>
                                    <th>Penalties</th>
                                    <th>Write Off Income</th>
                                    <th>Total</th>
                                    <th>Loans Count</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($revenue_data as $item): ?>
                                    <tr>
                                        <td><?php echo $item['branch_name']; ?></td>
                                        <td><?php echo $item['product_name']; ?></td>
                                        <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($item['interest'], 2); ?></span></td>
                                        <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($item['admin_fees'], 2); ?></span></td>
                                        <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($item['loan_cover'], 2); ?></span></td>
                                        <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($item['processing_fees'], 2); ?></span></td>
                                        <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($item['penalties'], 2); ?></span></td>
                                        <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($item['write_off'], 2); ?></span></td>
                                        <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($item['total'], 2); ?></span></td>
                                        <td class="text-center"><?php echo $item['loans_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                <tr class="total-row">
                                    <td colspan="2"><strong>TOTALS</strong></td>
                                    <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($totals['interest'], 2); ?></span></td>
                                    <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($totals['admin_fees'], 2); ?></span></td>
                                    <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($totals['loan_cover'], 2); ?></span></td>
                                    <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($totals['processing_fees'], 2); ?></span></td>
                                    <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($totals['penalties'], 2); ?></span></td>
                                    <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($totals['write_off'], 2); ?></span></td>
                                    <td class="text-right kwacha"><span class="kwacha-value"><?php echo number_format($totals['total'], 2); ?></span></td>
                                    <td class="text-center"><?php echo array_sum(array_column($revenue_data, 'loans_count')); ?></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>





