<?php
$logs = isset($logs) ? $logs : get_logs('activity_logger','user_id',$this->session->userdata('user_id'));
$settings = isset($settings) ? $settings : get_by_id('settings','settings_id','1');
$summary_stats = isset($summary_stats) && is_array($summary_stats) ? $summary_stats : array();
$product_balances = isset($product_balances) && is_array($product_balances) ? $product_balances : array();
$currency = isset($settings->currency) ? $settings->currency : '';
$summary_source = isset($summary_source) ? $summary_source : 'default';
$summary_needs_refresh = !empty($summary_needs_refresh);

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
$par_percentage = isset($summary_stats['par_percentage']) ? (float) $summary_stats['par_percentage'] : 0;
$payments_today_total = isset($summary_stats['payments_today']) ? (float) $summary_stats['payments_today'] : 0;
$payments_week_total = isset($summary_stats['payments_week']) ? (float) $summary_stats['payments_week'] : 0;
$payments_month_total = isset($summary_stats['payments_month']) ? (float) $summary_stats['payments_month'] : 0;
$total_collected = $paid_interest + $paid_lc + $paid_af;

if ($par_percentage <= 0 && $total_unpaid > 0) {
    $par_percentage = ($total_arrears / $total_unpaid) * 100;
}

$format_amount = function ($amount) use ($currency) {
    return trim($currency . ' ' . number_format(round((float) $amount, 2), 2));
};

$overview_cards = array(
    array(
        'eyebrow' => 'Collections booked',
        'title' => 'Total revenue collected',
        'value' => $total_collected,
        'note' => 'Interest, cover and admin fees already received.',
        'link' => base_url('Loan/loan_revenue'),
        'tone' => 'emerald',
    ),
    array(
        'eyebrow' => 'Open portfolio',
        'title' => 'Outstanding principal',
        'value' => $total_unpaid,
        'note' => 'Institution-wide balance still active on the books.',
        'link' => base_url('Loan/balances'),
        'tone' => 'navy',
    ),
    array(
        'eyebrow' => 'Attention required',
        'title' => 'Current arrears',
        'value' => $total_arrears,
        'note' => 'Delinquent amount that needs recovery follow-up.',
        'link' => base_url('Reports/arrears?by_date=All&loan=All'),
        'tone' => 'rose',
    ),
    array(
        'eyebrow' => 'Portfolio quality',
        'title' => 'PAR',
        'value' => $par_percentage,
        'value_type' => 'percent',
        'note' => 'Portfolio at risk based on overdue exposure versus active unpaid portfolio.',
        'link' => base_url('Reports/par_reports'),
        'tone' => 'amber',
    ),
);

$revenue_cards = array(
    array(
        'title' => 'Paid interest',
        'value' => $paid_interest,
        'note' => 'Interest collected across all loans.',
        'link' => base_url('Loan/loan_revenue'),
        'tone' => 'emerald',
    ),
    array(
        'title' => 'Loan cover paid',
        'value' => $paid_lc,
        'note' => 'Loan cover charges already recovered.',
        'link' => base_url('Loan/loan_revenue'),
        'tone' => 'teal',
    ),
    array(
        'title' => 'Admin fees paid',
        'value' => $paid_af,
        'note' => 'Administration fees collected from loans.',
        'link' => base_url('Loan/loan_revenue'),
        'tone' => 'sky',
    ),
);

$balance_cards = array(
    array(
        'title' => 'Interest balance',
        'value' => $outstanding_interest,
        'note' => 'Interest still outstanding on active loans.',
        'link' => base_url('Loan/balances'),
        'tone' => 'navy',
    ),
    array(
        'title' => 'Loan cover balance',
        'value' => $outstanding_lc,
        'note' => 'Unpaid loan cover across the portfolio.',
        'link' => base_url('Loan/balances'),
        'tone' => 'violet',
    ),
    array(
        'title' => 'Admin fee balance',
        'value' => $outstanding_af,
        'note' => 'Administration charges still pending.',
        'link' => base_url('Loan/balances'),
        'tone' => 'stone',
    ),
);

$arrears_cards = array(
    array(
        'title' => 'All arrears',
        'value' => $total_arrears,
        'note' => 'Full delinquent exposure.',
        'link' => base_url('Reports/arrears?by_date=All&loan=All'),
        'tone' => 'navy',
    ),
    array(
        'title' => '1 day',
        'value' => $one_day_arrears,
        'note' => 'Recently slipped loans.',
        'link' => base_url('Reports/arrears?by_date=one_day&loan=All'),
        'tone' => 'rose',
    ),
    array(
        'title' => '3 days',
        'value' => $three_day_arrears,
        'note' => 'Early follow-up queue.',
        'link' => base_url('Reports/arrears?by_date=three_days&loan=All'),
        'tone' => 'rose',
    ),
    array(
        'title' => '1 week',
        'value' => $week_arrears,
        'note' => 'Collections now require escalation.',
        'link' => base_url('Reports/arrears?by_date=week&loan=All'),
        'tone' => 'rose',
    ),
    array(
        'title' => '1 month',
        'value' => $month_arrears,
        'note' => 'Persistent overdue balances.',
        'link' => base_url('Reports/arrears?by_date=month&loan=All'),
        'tone' => 'rose',
    ),
    array(
        'title' => '2 months',
        'value' => $two_month_arrears,
        'note' => 'High-priority recoveries.',
        'link' => base_url('Reports/arrears?by_date=2month&loan=All'),
        'tone' => 'rose',
    ),
    array(
        'title' => '3 months',
        'value' => $three_month_arrears,
        'note' => 'Deeply overdue accounts.',
        'link' => base_url('Reports/arrears?by_date=3month&loan=All'),
        'tone' => 'rose',
    ),
);

$payment_cards = array(
    array(
        'title' => 'Due today',
        'value' => $payments_today_total,
        'note' => 'Installments expected before close of day.',
        'link' => base_url('Reports/to_pay_today'),
        'tone' => 'amber',
    ),
    array(
        'title' => 'Due this week',
        'value' => $payments_week_total,
        'note' => 'Short-term collection target.',
        'link' => base_url('Reports/to_pay_week'),
        'tone' => 'amber',
    ),
    array(
        'title' => 'Due this month',
        'value' => $payments_month_total,
        'note' => 'Expected repayments still ahead.',
        'link' => base_url('Reports/to_pay_month'),
        'tone' => 'amber',
    ),
);
?>
<style>
    .summary-dashboard {
        --summary-bg: linear-gradient(180deg, #f7f4ee 0%, #ffffff 36%, #f4efe6 100%);
        --summary-surface: rgba(255, 255, 255, 0.9);
        --summary-border: rgba(26, 40, 57, 0.08);
        --summary-shadow: 0 18px 45px rgba(23, 29, 38, 0.08);
        --summary-text: #18212b;
        --summary-muted: #667487;
        --summary-heading: "Bahnschrift", "Aptos Display", "Segoe UI", sans-serif;
        --summary-body: "Aptos", "Segoe UI", "Trebuchet MS", sans-serif;
        background: var(--summary-bg);
        border: 1px solid rgba(26, 40, 57, 0.05);
        border-radius: 28px;
        box-shadow: var(--summary-shadow);
        color: var(--summary-text);
        font-family: var(--summary-body);
        margin-bottom: 24px;
        overflow: hidden;
        padding: 28px;
        position: relative;
    }

    .summary-dashboard:before,
    .summary-dashboard:after {
        background: radial-gradient(circle, rgba(22, 107, 92, 0.18) 0%, rgba(22, 107, 92, 0) 65%);
        content: '';
        height: 260px;
        position: absolute;
        width: 260px;
        z-index: 0;
    }

    .summary-dashboard:before {
        left: -90px;
        top: -110px;
    }

    .summary-dashboard:after {
        background: radial-gradient(circle, rgba(214, 121, 62, 0.14) 0%, rgba(214, 121, 62, 0) 68%);
        bottom: -130px;
        right: -70px;
    }

    .summary-dashboard > * {
        position: relative;
        z-index: 1;
    }

    .summary-intro {
        align-items: end;
        display: flex;
        gap: 18px;
        justify-content: space-between;
        margin-bottom: 24px;
    }

    .summary-kicker {
        color: #156f5f;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.18em;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .summary-title {
        font-family: var(--summary-heading);
        font-size: 30px;
        font-weight: 700;
        letter-spacing: 0.02em;
        line-height: 1.05;
        margin: 0;
    }

    .summary-subtitle {
        color: var(--summary-muted);
        font-size: 15px;
        margin: 10px 0 0;
        max-width: 560px;
    }

    .summary-badge {
        backdrop-filter: blur(10px);
        background: rgba(24, 33, 43, 0.88);
        border-radius: 999px;
        color: #fff6e8;
        display: inline-flex;
        font-size: 12px;
        font-weight: 600;
        gap: 8px;
        padding: 10px 16px;
        white-space: nowrap;
    }

    .summary-badge i {
        color: #f0b266;
        margin-top: 1px;
    }

    .summary-section {
        margin-top: 30px;
    }

    .summary-section-head {
        align-items: baseline;
        display: flex;
        gap: 14px;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .summary-section-head h3 {
        color: #1b2430;
        font-family: var(--summary-heading);
        font-size: 19px;
        font-weight: 700;
        margin: 0;
    }

    .summary-section-head p {
        color: var(--summary-muted);
        font-size: 13px;
        margin: 0;
    }

    .summary-grid,
    .summary-mini-grid,
    .summary-product-grid,
    .summary-split-grid {
        display: grid;
        gap: 16px;
    }

    .summary-grid {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .summary-mini-grid {
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }

    .summary-product-grid {
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    }

    .summary-split-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .summary-card,
    .summary-panel,
    .summary-product-card {
        background: var(--summary-surface);
        border: 1px solid var(--summary-border);
        border-radius: 24px;
        box-shadow: 0 12px 28px rgba(23, 29, 38, 0.05);
    }

    .summary-card,
    .summary-product-card {
        color: inherit;
        display: flex;
        flex-direction: column;
        min-height: 190px;
        padding: 18px 18px 20px;
        text-decoration: none !important;
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }

    .summary-card:hover,
    .summary-card:focus,
    .summary-product-card:hover,
    .summary-product-card:focus {
        border-color: rgba(24, 33, 43, 0.12);
        box-shadow: 0 20px 40px rgba(23, 29, 38, 0.1);
        color: inherit;
        text-decoration: none;
        transform: translateY(-4px);
    }

    .summary-card-top,
    .summary-product-top {
        margin-bottom: 18px;
    }

    .summary-eyebrow,
    .summary-chip {
        border-radius: 999px;
        display: inline-flex;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        padding: 8px 12px;
        text-transform: uppercase;
    }

    .summary-tone-emerald .summary-chip,
    .summary-chip.summary-tone-emerald,
    .summary-tone-emerald .summary-eyebrow {
        background: rgba(25, 122, 98, 0.12);
        color: #116754;
    }

    .summary-tone-teal .summary-chip,
    .summary-chip.summary-tone-teal,
    .summary-tone-teal .summary-eyebrow {
        background: rgba(16, 122, 132, 0.11);
        color: #0f6872;
    }

    .summary-tone-sky .summary-chip,
    .summary-chip.summary-tone-sky,
    .summary-tone-sky .summary-eyebrow {
        background: rgba(52, 111, 188, 0.12);
        color: #29508b;
    }

    .summary-tone-navy .summary-chip,
    .summary-chip.summary-tone-navy,
    .summary-tone-navy .summary-eyebrow {
        background: rgba(24, 33, 43, 0.1);
        color: #1b2430;
    }

    .summary-tone-violet .summary-chip,
    .summary-chip.summary-tone-violet,
    .summary-tone-violet .summary-eyebrow {
        background: rgba(110, 91, 165, 0.12);
        color: #5a4c88;
    }

    .summary-tone-rose .summary-chip,
    .summary-chip.summary-tone-rose,
    .summary-tone-rose .summary-eyebrow {
        background: rgba(171, 76, 88, 0.12);
        color: #8d3f49;
    }

    .summary-tone-amber .summary-chip,
    .summary-chip.summary-tone-amber,
    .summary-tone-amber .summary-eyebrow {
        background: rgba(214, 121, 62, 0.14);
        color: #9b5121;
    }

    .summary-tone-stone .summary-chip,
    .summary-chip.summary-tone-stone,
    .summary-tone-stone .summary-eyebrow {
        background: rgba(126, 110, 93, 0.12);
        color: #655647;
    }

    .summary-card h4,
    .summary-product-card h4 {
        color: #1c2430;
        font-family: var(--summary-heading);
        font-size: 18px;
        font-weight: 700;
        line-height: 1.2;
        margin: 0 0 12px;
    }

    .summary-value {
        color: #101820;
        font-family: var(--summary-heading);
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.05;
        margin-bottom: 10px;
        word-break: break-word;
    }

    .summary-note,
    .summary-product-card p,
    .summary-panel p {
        color: var(--summary-muted);
        font-size: 13px;
        line-height: 1.5;
        margin: 0;
    }

    .summary-panel {
        min-height: 100%;
        padding: 18px;
    }

    .summary-panel-head {
        align-items: center;
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .summary-panel-head h4 {
        color: #18212b;
        font-family: var(--summary-heading);
        font-size: 18px;
        margin: 0;
    }

    .summary-panel-head p {
        margin-top: 6px;
    }

    .summary-product-card {
        min-height: 156px;
    }

    .summary-product-card p {
        margin-top: auto;
    }

    @media (max-width: 991px) {
        .summary-dashboard {
            border-radius: 24px;
            padding: 22px;
        }

        .summary-intro,
        .summary-section-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .summary-split-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .summary-dashboard {
            padding: 18px;
        }

        .summary-title {
            font-size: 24px;
        }

        .summary-value {
            font-size: 24px;
        }

        .summary-card,
        .summary-product-card,
        .summary-panel {
            border-radius: 20px;
        }
    }
</style>
<div class="main-content">
    <div class="page-header no-gutters has-tab" style="margin-bottom: 2px !important;">
        <h2 class="font-weight-normal">DASHBOARD SUMMARY</h2>

    </div>
    <?php if ($summary_needs_refresh) { ?>
        <div class="alert alert-info" id="summary-refresh-status">
            Dashboard figures are loading in the background. This page will refresh automatically when the figures are ready.
        </div>
    <?php } ?>
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
        <div class="summary-dashboard">
            <div class="summary-intro">
                <div>
                    <div class="summary-kicker">Dashboard Statistics</div>
                </div>
                <div class="summary-badge"><i class="fa fa-clock-o"></i> Live operational totals</div>
            </div>

            <div class="summary-grid">
                <?php foreach ($overview_cards as $card) { ?>
                    <a class="summary-card summary-tone-<?php echo $card['tone']; ?>" href="<?php echo $card['link']; ?>">
                        <div class="summary-card-top">
                            <span class="summary-eyebrow"><?php echo $card['eyebrow']; ?></span>
                        </div>
                        <h4><?php echo $card['title']; ?></h4>
                        <div class="summary-value"><?php echo isset($card['value_type']) && $card['value_type'] === 'percent' ? number_format((float) $card['value'], 2) . '%' : $format_amount($card['value']); ?></div>
                        <p class="summary-note"><?php echo $card['note']; ?></p>
                    </a>
                <?php } ?>
            </div>

            <div class="summary-section">
                <div class="summary-section-head">
                    <div>
                        <h3>Revenue streams</h3>
                        <p>Collected income, separated into the three core fee components.</p>
                    </div>
                </div>
                <div class="summary-grid">
                    <?php foreach ($revenue_cards as $card) { ?>
                        <a class="summary-card summary-tone-<?php echo $card['tone']; ?>" href="<?php echo $card['link']; ?>">
                            <div class="summary-card-top">
                                <span class="summary-chip"><?php echo $card['title']; ?></span>
                            </div>
                            <h4><?php echo $card['title']; ?></h4>
                            <div class="summary-value"><?php echo $format_amount($card['value']); ?></div>
                            <p class="summary-note"><?php echo $card['note']; ?></p>
                        </a>
                    <?php } ?>
                </div>
            </div>

            <div class="summary-section">
                <div class="summary-section-head">
                    <div>
                        <h3>Outstanding balances</h3>
                        <p>Current unpaid balances across interest, cover and administration fees.</p>
                    </div>
                </div>
                <div class="summary-grid">
                    <?php foreach ($balance_cards as $card) { ?>
                        <a class="summary-card summary-tone-<?php echo $card['tone']; ?>" href="<?php echo $card['link']; ?>">
                            <div class="summary-card-top">
                                <span class="summary-chip"><?php echo $card['title']; ?></span>
                            </div>
                            <h4><?php echo $card['title']; ?></h4>
                            <div class="summary-value"><?php echo $format_amount($card['value']); ?></div>
                            <p class="summary-note"><?php echo $card['note']; ?></p>
                        </a>
                    <?php } ?>
                </div>
            </div>

            <div class="summary-section">
                <div class="summary-section-head">
                    <div>
                        <h3>Portfolio by product</h3>
                        <p>A lighter product view that keeps balances visible without overwhelming the page.</p>
                    </div>
                </div>
                <div class="summary-product-grid">
                    <a class="summary-product-card summary-tone-violet" href="<?php echo base_url('Loan/balances'); ?>">
                        <div class="summary-product-top">
                            <span class="summary-chip">Institution total</span>
                        </div>
                        <h4>Total outstanding portfolio</h4>
                        <div class="summary-value"><?php echo $format_amount($total_unpaid); ?></div>
                        <p>All active product balances combined.</p>
                    </a>
                    <?php foreach ($product_balances as $product) { ?>
                        <a class="summary-product-card summary-tone-sky" href="<?php echo base_url('Loan/balances?product=') . $product->loan_product_id; ?>">
                            <div class="summary-product-top">
                                <span class="summary-chip"><?php echo $product->product_code; ?></span>
                            </div>
                            <h4><?php echo $product->product_name; ?></h4>
                            <div class="summary-value"><?php echo $format_amount($product->outstanding_principal); ?></div>
                            <p>Outstanding principal for this product line.</p>
                        </a>
                    <?php } ?>
                </div>
            </div>

            <div class="summary-section">
                <div class="summary-section-head">
                    <div>
                        <h3>Collections watchlist</h3>
                        <p>Overdues and due-soon amounts side by side for faster daily follow-up.</p>
                    </div>
                </div>
                <div class="summary-split-grid">
                    <div class="summary-panel">
                        <div class="summary-panel-head">
                            <div>
                                <h4>Arrears ladder</h4>
                                <p>Track how quickly overdue balances are aging.</p>
                            </div>
                            <span class="summary-chip summary-tone-rose">Recovery focus</span>
                        </div>
                        <div class="summary-mini-grid">
                            <?php foreach ($arrears_cards as $card) { ?>
                                <a class="summary-card summary-tone-<?php echo $card['tone']; ?>" href="<?php echo $card['link']; ?>">
                                    <div class="summary-card-top">
                                        <span class="summary-chip"><?php echo $card['title']; ?></span>
                                    </div>
                                    <h4><?php echo $card['title']; ?></h4>
                                    <div class="summary-value"><?php echo $format_amount($card['value']); ?></div>
                                    <p class="summary-note"><?php echo $card['note']; ?></p>
                                </a>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="summary-panel">
                        <div class="summary-panel-head">
                            <div>
                                <h4>Upcoming payments</h4>
                                <p>Short-term repayment targets that shape collection planning.</p>
                            </div>
                            <span class="summary-chip summary-tone-amber">Cash-in view</span>
                        </div>
                        <div class="summary-mini-grid">
                            <?php foreach ($payment_cards as $card) { ?>
                                <a class="summary-card summary-tone-<?php echo $card['tone']; ?>" href="<?php echo $card['link']; ?>">
                                    <div class="summary-card-top">
                                        <span class="summary-chip"><?php echo $card['title']; ?></span>
                                    </div>
                                    <h4><?php echo $card['title']; ?></h4>
                                    <div class="summary-value"><?php echo $format_amount($card['value']); ?></div>
                                    <p class="summary-note"><?php echo $card['note']; ?></p>
                                </a>
                            <?php } ?>
                        </div>
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
<?php if ($summary_needs_refresh) { ?>
    <script>
        (function () {
            var statusNode = document.getElementById('summary-refresh-status');
            var request = new XMLHttpRequest();

            request.open('GET', '<?php echo base_url('Reports/summary_data'); ?>', true);
            request.onreadystatechange = function () {
                if (request.readyState !== 4) {
                    return;
                }

                if (request.status >= 200 && request.status < 300) {
                    window.location.reload();
                    return;
                }

                if (statusNode) {
                    statusNode.className = 'alert alert-warning';
                    statusNode.textContent = 'Dashboard figures could not be refreshed automatically. Reload this page in a few moments.';
                }
            };

            request.send();
        })();
    </script>
<?php } ?>
