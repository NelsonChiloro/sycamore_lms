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
$payments_today_total = isset($summary_stats['payments_today']) ? (float) $summary_stats['payments_today'] : 0;
$payments_week_total = isset($summary_stats['payments_week']) ? (float) $summary_stats['payments_week'] : 0;
$payments_month_total = isset($summary_stats['payments_month']) ? (float) $summary_stats['payments_month'] : 0;

$format_money = function ($amount) use ($currency) {
    return trim($currency . ' ' . number_format((float) $amount, 2));
};

$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$ratio_value = function ($value, $total) {
    if ((float) $total <= 0) {
        return 0;
    }

    return (((float) $value) / ((float) $total)) * 100;
};

$format_ratio = function ($value, $total) use ($ratio_value) {
    return number_format($ratio_value($value, $total), 1) . '%';
};

$revenue_total = $paid_interest + $paid_lc + $paid_af;
$outstanding_charges_total = $outstanding_interest + $outstanding_lc + $outstanding_af;
$product_portfolio_total = 0;

foreach ($product_balances as $product_balance_row) {
    $product_portfolio_total += (float) $product_balance_row->outstanding_principal;
}

$portfolio_reference_total = $total_unpaid > 0 ? $total_unpaid : $product_portfolio_total;
$summary_source_label = $summary_source === 'cache' ? 'Cached snapshot' : 'Live snapshot';
$summary_source_note = $summary_source === 'cache'
    ? 'Values are shown from the latest generated dashboard cache.'
    : 'Values are being served directly from the latest dashboard summary.';

$sorted_product_balances = $product_balances;
usort($sorted_product_balances, function ($left, $right) {
    $left_value = (float) $left->outstanding_principal;
    $right_value = (float) $right->outstanding_principal;

    if ($left_value === $right_value) {
        return 0;
    }

    return ($left_value < $right_value) ? 1 : -1;
});

$top_product = !empty($sorted_product_balances) ? $sorted_product_balances[0] : null;
$top_product_share = $top_product ? $format_ratio((float) $top_product->outstanding_principal, $portfolio_reference_total) : '0.0%';

$highlights = array(
    array(
        'eyebrow' => 'Core exposure',
        'title' => 'Outstanding portfolio',
        'value' => $format_money($total_unpaid),
        'note' => $total_arrears > 0
            ? $format_ratio($total_arrears, $total_unpaid) . ' of the open book is already in arrears.'
            : 'All unpaid schedules are currently sitting outside arrears buckets.',
        'href' => base_url('Loan/balances'),
        'icon' => 'fa-university',
        'variant' => 'portfolio',
        'meta' => 'See balances'
    ),
    array(
        'eyebrow' => 'Collections pressure',
        'title' => 'Total arrears',
        'value' => $format_money($total_arrears),
        'note' => $month_arrears > 0
            ? $format_ratio($month_arrears, $total_arrears) . ' sits in the last 30 days bucket.'
            : 'No material short-term arrears were detected in the current window.',
        'href' => base_url('Reports/arrears?by_date=All&loan=All'),
        'icon' => 'fa-clock-o',
        'variant' => 'arrears',
        'meta' => 'Review arrears'
    ),
    array(
        'eyebrow' => 'Collected income',
        'title' => 'Revenue realized',
        'value' => $format_money($revenue_total),
        'note' => $revenue_total > 0
            ? $format_ratio($paid_interest, $revenue_total) . ' of revenue came from interest collections.'
            : 'Revenue will appear here as paid schedules are collected.',
        'href' => base_url('Loan/loan_revenue'),
        'icon' => 'fa-line-chart',
        'variant' => 'revenue',
        'meta' => 'Open revenue'
    ),
    array(
        'eyebrow' => 'Upcoming collections',
        'title' => 'Due this month',
        'value' => $format_money($payments_month_total),
        'note' => $payments_month_total > 0
            ? $format_ratio($payments_week_total, $payments_month_total) . ' of this month is already due within the week.'
            : 'No unpaid schedules are due inside the current month window.',
        'href' => base_url('Reports/to_pay_month'),
        'icon' => 'fa-calendar-check-o',
        'variant' => 'due',
        'meta' => 'View schedule'
    )
);

$revenue_cards = array(
    array(
        'label' => 'Paid interest',
        'value' => $paid_interest,
        'href' => base_url('Loan/loan_revenue'),
        'icon' => 'fa-percent',
        'support' => $revenue_total > 0
            ? $format_ratio($paid_interest, $revenue_total) . ' of recognized revenue.'
            : 'Interest collections will appear here after payments are posted.',
        'tone' => 'revenue'
    ),
    array(
        'label' => 'Loan cover',
        'value' => $paid_lc,
        'href' => base_url('Loan/loan_revenue'),
        'icon' => 'fa-shield',
        'support' => $revenue_total > 0
            ? $format_ratio($paid_lc, $revenue_total) . ' of recognized revenue.'
            : 'Loan cover income is still waiting for paid schedules.',
        'tone' => 'revenue'
    ),
    array(
        'label' => 'Admin fees',
        'value' => $paid_af,
        'href' => base_url('Loan/loan_revenue'),
        'icon' => 'fa-briefcase',
        'support' => $revenue_total > 0
            ? $format_ratio($paid_af, $revenue_total) . ' of recognized revenue.'
            : 'Administration fee income has not been realized yet.',
        'tone' => 'revenue'
    )
);

$balance_cards = array(
    array(
        'label' => 'Outstanding interest',
        'value' => $outstanding_interest,
        'href' => base_url('Loan/balances'),
        'icon' => 'fa-area-chart',
        'support' => $outstanding_charges_total > 0
            ? $format_ratio($outstanding_interest, $outstanding_charges_total) . ' of open charges.'
            : 'No open charge balances are currently showing.',
        'tone' => 'balance'
    ),
    array(
        'label' => 'Outstanding loan cover',
        'value' => $outstanding_lc,
        'href' => base_url('Loan/balances'),
        'icon' => 'fa-life-ring',
        'support' => $outstanding_charges_total > 0
            ? $format_ratio($outstanding_lc, $outstanding_charges_total) . ' of open charges.'
            : 'Loan cover balances are currently at zero.',
        'tone' => 'balance'
    ),
    array(
        'label' => 'Outstanding admin fees',
        'value' => $outstanding_af,
        'href' => base_url('Loan/balances'),
        'icon' => 'fa-files-o',
        'support' => $outstanding_charges_total > 0
            ? $format_ratio($outstanding_af, $outstanding_charges_total) . ' of open charges.'
            : 'Administration fee balances are currently at zero.',
        'tone' => 'balance'
    )
);

$arrears_cards = array(
    array(
        'label' => 'Total arrears',
        'value' => $total_arrears,
        'href' => base_url('Reports/arrears?by_date=All&loan=All'),
        'icon' => 'fa-exclamation-triangle',
        'support' => $total_unpaid > 0
            ? $format_ratio($total_arrears, $total_unpaid) . ' of unpaid exposure.'
            : 'No unpaid exposure is available to benchmark arrears.',
        'tone' => 'arrears'
    ),
    array(
        'label' => '1 day arrears',
        'value' => $one_day_arrears,
        'href' => base_url('Reports/arrears?by_date=one_day&loan=All'),
        'icon' => 'fa-bolt',
        'support' => $total_arrears > 0
            ? $format_ratio($one_day_arrears, $total_arrears) . ' of all arrears.'
            : 'No early-stage arrears are currently showing.',
        'tone' => 'arrears'
    ),
    array(
        'label' => '3 day arrears',
        'value' => $three_day_arrears,
        'href' => base_url('Reports/arrears?by_date=three_days&loan=All'),
        'icon' => 'fa-hourglass-half',
        'support' => $total_arrears > 0
            ? $format_ratio($three_day_arrears, $total_arrears) . ' of all arrears.'
            : 'No three-day arrears are currently showing.',
        'tone' => 'arrears'
    ),
    array(
        'label' => '1 week arrears',
        'value' => $week_arrears,
        'href' => base_url('Reports/arrears?by_date=week&loan=All'),
        'icon' => 'fa-calendar',
        'support' => $total_arrears > 0
            ? $format_ratio($week_arrears, $total_arrears) . ' of all arrears.'
            : 'No one-week arrears are currently showing.',
        'tone' => 'arrears'
    ),
    array(
        'label' => '1 month arrears',
        'value' => $month_arrears,
        'href' => base_url('Reports/arrears?by_date=month&loan=All'),
        'icon' => 'fa-calendar-o',
        'support' => $total_arrears > 0
            ? $format_ratio($month_arrears, $total_arrears) . ' of all arrears.'
            : 'No one-month arrears are currently showing.',
        'tone' => 'arrears'
    ),
    array(
        'label' => '2 month arrears',
        'value' => $two_month_arrears,
        'href' => base_url('Reports/arrears?by_date=2month&loan=All'),
        'icon' => 'fa-history',
        'support' => $total_arrears > 0
            ? $format_ratio($two_month_arrears, $total_arrears) . ' of all arrears.'
            : 'No two-month arrears are currently showing.',
        'tone' => 'arrears'
    ),
    array(
        'label' => '3 month arrears',
        'value' => $three_month_arrears,
        'href' => base_url('Reports/arrears?by_date=3month&loan=All'),
        'icon' => 'fa-hourglass-end',
        'support' => $total_arrears > 0
            ? $format_ratio($three_month_arrears, $total_arrears) . ' of all arrears.'
            : 'No three-month arrears are currently showing.',
        'tone' => 'arrears'
    )
);

$due_cards = array(
    array(
        'label' => 'Due today',
        'value' => $payments_today_total,
        'href' => base_url('Reports/to_pay_today'),
        'icon' => 'fa-sun-o',
        'support' => $payments_week_total > 0
            ? $format_ratio($payments_today_total, $payments_week_total) . ' of this week is due today.'
            : 'No unpaid schedules are due today.',
        'tone' => 'due'
    ),
    array(
        'label' => 'Due this week',
        'value' => $payments_week_total,
        'href' => base_url('Reports/to_pay_week'),
        'icon' => 'fa-calendar-check-o',
        'support' => $payments_month_total > 0
            ? $format_ratio($payments_week_total, $payments_month_total) . ' of this month is due this week.'
            : 'No unpaid schedules are due this week.',
        'tone' => 'due'
    ),
    array(
        'label' => 'Due this month',
        'value' => $payments_month_total,
        'href' => base_url('Reports/to_pay_month'),
        'icon' => 'fa-calendar-plus-o',
        'support' => $payments_month_total > 0
            ? 'Use this as the broadest forward-looking cash collection target.'
            : 'No unpaid schedules are due this month.',
        'tone' => 'due'
    )
);

$show = false;
foreach ($this->session->userdata('access') as $r) {
    if ($r->controllerid == 113) {
        $show = true;
        break;
    }
}
?>

<?php if ($show) { ?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

    .dashboard-summary {
        font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
        color: #16302b;
        padding-bottom: 24px;
    }

    .dashboard-summary *,
    .dashboard-summary *:before,
    .dashboard-summary *:after {
        box-sizing: border-box;
    }

    .dashboard-summary__header {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        padding: 32px;
        margin-bottom: 26px;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.42), transparent 38%),
            linear-gradient(135deg, #0e483e 0%, #16715f 52%, #d2f5d9 100%);
        color: #f7fff9;
        box-shadow: 0 22px 40px rgba(14, 72, 62, 0.18);
    }

    .dashboard-summary__header:before,
    .dashboard-summary__header:after {
        content: '';
        position: absolute;
        border-radius: 999px;
        opacity: 0.22;
        pointer-events: none;
    }

    .dashboard-summary__header:before {
        width: 280px;
        height: 280px;
        right: -90px;
        top: -110px;
        background: rgba(255, 255, 255, 0.2);
    }

    .dashboard-summary__header:after {
        width: 190px;
        height: 190px;
        right: 90px;
        bottom: -110px;
        background: rgba(255, 242, 210, 0.28);
    }

    .dashboard-summary__header-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.8fr);
        gap: 24px;
        align-items: end;
    }

    .dashboard-summary__kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        color: rgba(255, 255, 255, 0.92);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        margin-bottom: 18px;
    }

    .dashboard-summary__title {
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        font-size: 34px;
        line-height: 1.05;
        font-weight: 800;
        margin: 0 0 12px;
        color: #ffffff;
    }

    .dashboard-summary__subtitle {
        max-width: 760px;
        margin: 0;
        color: rgba(247, 255, 249, 0.86);
        font-size: 15px;
        line-height: 1.7;
    }

    .dashboard-summary__status {
        display: grid;
        gap: 12px;
        padding: 18px;
        border-radius: 24px;
        background: rgba(7, 29, 25, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.16);
        backdrop-filter: blur(8px);
    }

    .dashboard-summary__status-label {
        font-size: 11px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 700;
    }

    .dashboard-summary__status-value {
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        font-size: 24px;
        font-weight: 800;
        color: #ffffff;
    }

    .dashboard-summary__status-note {
        font-size: 13px;
        line-height: 1.6;
        color: rgba(247, 255, 249, 0.82);
        margin: 0;
    }

    .dashboard-summary__status-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding-top: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.14);
        color: rgba(247, 255, 249, 0.82);
        font-size: 12px;
        line-height: 1.5;
    }

    #summary-refresh-status {
        border: 0;
        border-radius: 18px;
        padding: 16px 18px;
        margin-bottom: 24px;
        background: #fff6df;
        color: #7b5600;
        box-shadow: 0 14px 32px rgba(123, 86, 0, 0.08);
    }

    .dashboard-summary__section {
        margin-top: 28px;
    }

    .dashboard-summary__section-head {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 16px;
        margin-bottom: 16px;
    }

    .dashboard-summary__section-title {
        margin: 0;
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        font-size: 23px;
        font-weight: 800;
        color: #17312e;
    }

    .dashboard-summary__section-copy {
        margin: 6px 0 0;
        color: #5a6f69;
        font-size: 14px;
        line-height: 1.6;
        max-width: 700px;
    }

    .dashboard-summary__section-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: #e8f3ef;
        color: #205246;
        font-size: 12px;
        font-weight: 700;
    }

    .dashboard-summary__grid {
        display: grid;
        gap: 18px;
    }

    .dashboard-summary__grid--hero {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .dashboard-summary__grid--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .dashboard-summary__grid--products {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .dashboard-summary__split {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .summary-card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 220px;
        min-width: 0;
        padding: 22px;
        border-radius: 24px;
        background: #ffffff;
        border: 1px solid rgba(19, 59, 53, 0.08);
        box-shadow: 0 20px 34px rgba(17, 44, 39, 0.08);
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        overflow: hidden;
    }

    .summary-card:hover,
    .summary-card:focus {
        transform: translateY(-4px);
        box-shadow: 0 24px 40px rgba(17, 44, 39, 0.14);
        border-color: rgba(19, 59, 53, 0.16);
        text-decoration: none;
        color: inherit;
    }

    .summary-card:before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(150deg, rgba(255, 255, 255, 0.5), transparent 48%);
        opacity: 0.7;
        pointer-events: none;
    }

    .summary-card--portfolio {
        background: linear-gradient(180deg, #effaf5 0%, #ffffff 100%);
    }

    .summary-card--arrears {
        background: linear-gradient(180deg, #fff1eb 0%, #ffffff 100%);
    }

    .summary-card--revenue {
        background: linear-gradient(180deg, #eef7ff 0%, #ffffff 100%);
    }

    .summary-card--due {
        background: linear-gradient(180deg, #fff8e8 0%, #ffffff 100%);
    }

    .summary-card--balance {
        background: linear-gradient(180deg, #f2f6fb 0%, #ffffff 100%);
    }

    .summary-card--product {
        min-height: 236px;
        background: linear-gradient(180deg, #f7fdfb 0%, #ffffff 100%);
    }

    .summary-card__head,
    .summary-card__meta {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
    }

    .summary-card__eyebrow {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #5b746c;
    }

    .summary-card__icon {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        font-size: 19px;
        color: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.24);
    }

    .summary-card--portfolio .summary-card__icon { background: linear-gradient(135deg, #0f6d57, #31a97f); }
    .summary-card--arrears .summary-card__icon { background: linear-gradient(135deg, #c44729, #f07b42); }
    .summary-card--revenue .summary-card__icon { background: linear-gradient(135deg, #1160b7, #3f8ef4); }
    .summary-card--due .summary-card__icon { background: linear-gradient(135deg, #a05c00, #ecab37); }
    .summary-card--balance .summary-card__icon { background: linear-gradient(135deg, #4d6477, #8099af); }
    .summary-card--product .summary-card__icon { background: linear-gradient(135deg, #1f6a59, #39b18d); }

    .summary-card__title {
        position: relative;
        z-index: 1;
        margin: 18px 0 8px;
        font-size: 18px;
        line-height: 1.3;
        font-weight: 700;
        color: #17312e;
    }

    .summary-card__value {
        position: relative;
        z-index: 1;
        margin: 0;
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        font-size: clamp(24px, 1.9vw, 29px);
        line-height: 1.18;
        font-weight: 800;
        color: #102825;
        letter-spacing: -0.03em;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .summary-card__note {
        position: relative;
        z-index: 1;
        margin: 14px 0 0;
        color: #5a6f69;
        font-size: 14px;
        line-height: 1.65;
        flex: 1 1 auto;
    }

    .summary-card__meta {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid rgba(19, 59, 53, 0.08);
        color: #21473f;
        font-size: 12px;
        font-weight: 700;
    }

    .summary-card__chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(21, 66, 57, 0.06);
        color: #295249;
    }

    .summary-card__product-code {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #5f7d74;
    }

    .summary-progress {
        position: relative;
        z-index: 1;
        width: 100%;
        height: 10px;
        border-radius: 999px;
        margin-top: 18px;
        background: #e5efeb;
        overflow: hidden;
    }

    .summary-progress span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #18745f, #43bd95);
    }

    .summary-panel {
        padding: 24px;
        border-radius: 28px;
        background: #ffffff;
        border: 1px solid rgba(19, 59, 53, 0.08);
        box-shadow: 0 18px 34px rgba(17, 44, 39, 0.08);
    }

    .summary-panel__head {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 16px;
        margin-bottom: 18px;
    }

    .summary-panel__title {
        margin: 0;
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        font-size: 22px;
        font-weight: 800;
        color: #17312e;
    }

    .summary-panel__copy {
        margin: 6px 0 0;
        color: #5a6f69;
        font-size: 14px;
        line-height: 1.6;
    }

    .summary-panel__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .summary-empty {
        padding: 30px;
        border-radius: 24px;
        background: linear-gradient(180deg, #f6fbf8 0%, #ffffff 100%);
        border: 1px dashed rgba(19, 59, 53, 0.18);
        color: #4e6961;
        text-align: center;
    }

    .dashboard-summary__animate {
        animation: summary-fade-up 0.5s ease both;
    }

    @keyframes summary-fade-up {
        from {
            opacity: 0;
            transform: translateY(14px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 1199px) {
        .dashboard-summary__grid--three,
        .dashboard-summary__grid--products {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dashboard-summary__header-grid,
        .dashboard-summary__split {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 1399px) {
        .dashboard-summary__grid--hero {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .summary-card {
            min-height: 236px;
        }
    }

    @media (max-width: 991px) {
        .dashboard-summary__grid--three,
        .dashboard-summary__grid--products,
        .summary-panel__grid {
            grid-template-columns: 1fr;
        }

        .summary-card__value {
            font-size: 25px;
        }
    }

    @media (max-width: 767px) {
        .dashboard-summary__header {
            padding: 24px 20px;
            border-radius: 24px;
        }

        .dashboard-summary__title {
            font-size: 28px;
        }

        .dashboard-summary__grid--hero,
        .dashboard-summary__grid--three,
        .dashboard-summary__grid--products,
        .summary-panel__grid {
            grid-template-columns: 1fr;
        }

        .summary-card {
            min-height: unset;
        }

        .summary-card__value {
            font-size: 25px;
        }

        .dashboard-summary__section-head,
        .summary-panel__head,
        .dashboard-summary__status-foot {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
<?php } ?>

<div class="main-content">
    <?php if ($show) { ?>
        <div class="dashboard-summary">
            <div class="dashboard-summary__header dashboard-summary__animate">
                <div class="dashboard-summary__header-grid">
                    <div>
                        <span class="dashboard-summary__kicker">
                            <i class="fa fa-bar-chart"></i>
                            Portfolio intelligence
                        </span>
                        <h1 class="dashboard-summary__title">Dashboard statistics</h1>
                        <p class="dashboard-summary__subtitle">
                            Revenue, exposure, arrears, and upcoming collections are grouped into cleaner cards so teams can spot what is healthy, what needs action, and where concentration is building without scanning through repetitive blocks.
                        </p>
                    </div>
                    <div class="dashboard-summary__status">
                        <span class="dashboard-summary__status-label">Data source</span>
                        <span class="dashboard-summary__status-value"><?php echo $escape($summary_source_label); ?></span>
                        <p class="dashboard-summary__status-note"><?php echo $escape($summary_source_note); ?></p>
                        <div class="dashboard-summary__status-foot">
                            <span>Largest product concentration</span>
                            <strong>
                                <?php if ($top_product) { ?>
                                    <?php echo $escape($top_product->product_name . ' (' . $top_product->product_code . ')'); ?> · <?php echo $escape($top_product_share); ?>
                                <?php } else { ?>
                                    No product exposure available
                                <?php } ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($summary_needs_refresh) { ?>
                <div class="alert alert-info dashboard-summary__animate" id="summary-refresh-status">
                    Dashboard figures are loading in the background. This page will refresh automatically when the latest numbers are ready.
                </div>
            <?php } ?>

            <section class="dashboard-summary__section dashboard-summary__animate">
                <div class="dashboard-summary__section-head">
                    <div>
                        <h2 class="dashboard-summary__section-title">Executive highlights</h2>
                        <p class="dashboard-summary__section-copy">The first row prioritizes the figures people usually ask for first: open exposure, collections risk, income already realized, and short-term cash expected.</p>
                    </div>
                    <span class="dashboard-summary__section-pill">
                        <i class="fa fa-compass"></i>
                        Modern overview
                    </span>
                </div>
                <div class="dashboard-summary__grid dashboard-summary__grid--hero">
                    <?php foreach ($highlights as $index => $highlight) { ?>
                        <a class="summary-card summary-card--<?php echo $escape($highlight['variant']); ?> dashboard-summary__animate" href="<?php echo $highlight['href']; ?>" style="animation-delay: <?php echo number_format(($index + 1) * 0.05, 2); ?>s;">
                            <div class="summary-card__head">
                                <span class="summary-card__eyebrow"><?php echo $escape($highlight['eyebrow']); ?></span>
                                <span class="summary-card__icon"><i class="fa <?php echo $escape($highlight['icon']); ?>"></i></span>
                            </div>
                            <h3 class="summary-card__title"><?php echo $escape($highlight['title']); ?></h3>
                            <p class="summary-card__value"><?php echo $escape($highlight['value']); ?></p>
                            <p class="summary-card__note"><?php echo $escape($highlight['note']); ?></p>
                            <div class="summary-card__meta">
                                <span><?php echo $escape($highlight['meta']); ?></span>
                                <span class="summary-card__chip"><?php echo $escape($summary_source_label); ?></span>
                            </div>
                        </a>
                    <?php } ?>
                </div>
            </section>

            <section class="dashboard-summary__section dashboard-summary__animate">
                <div class="dashboard-summary__section-head">
                    <div>
                        <h2 class="dashboard-summary__section-title">Revenue and charge balances</h2>
                        <p class="dashboard-summary__section-copy">Income items and open charge balances are separated so the team can compare what has already been earned against what is still sitting on the books.</p>
                    </div>
                </div>
                <div class="dashboard-summary__split">
                    <div class="summary-panel">
                        <div class="summary-panel__head">
                            <div>
                                <h3 class="summary-panel__title">Revenue breakdown</h3>
                                <p class="summary-panel__copy">Collected revenue across all paid loan schedules.</p>
                            </div>
                            <span class="summary-card__chip"><?php echo $escape($format_money($revenue_total)); ?></span>
                        </div>
                        <div class="dashboard-summary__grid dashboard-summary__grid--three">
                            <?php foreach ($revenue_cards as $index => $card) { ?>
                                <a class="summary-card summary-card--<?php echo $escape($card['tone']); ?>" href="<?php echo $card['href']; ?>">
                                    <div class="summary-card__head">
                                        <span class="summary-card__eyebrow">Revenue stream</span>
                                        <span class="summary-card__icon"><i class="fa <?php echo $escape($card['icon']); ?>"></i></span>
                                    </div>
                                    <h3 class="summary-card__title"><?php echo $escape($card['label']); ?></h3>
                                    <p class="summary-card__value"><?php echo $escape($format_money($card['value'])); ?></p>
                                    <p class="summary-card__note"><?php echo $escape($card['support']); ?></p>
                                    <div class="summary-card__meta">
                                        <span>Open revenue details</span>
                                        <span class="summary-card__chip"><?php echo $escape(number_format($index + 1)); ?></span>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="summary-panel">
                        <div class="summary-panel__head">
                            <div>
                                <h3 class="summary-panel__title">Open charge balances</h3>
                                <p class="summary-panel__copy">Outstanding charges still attached to active unpaid schedules.</p>
                            </div>
                            <span class="summary-card__chip"><?php echo $escape($format_money($outstanding_charges_total)); ?></span>
                        </div>
                        <div class="dashboard-summary__grid dashboard-summary__grid--three">
                            <?php foreach ($balance_cards as $index => $card) { ?>
                                <a class="summary-card summary-card--<?php echo $escape($card['tone']); ?>" href="<?php echo $card['href']; ?>">
                                    <div class="summary-card__head">
                                        <span class="summary-card__eyebrow">Balance watch</span>
                                        <span class="summary-card__icon"><i class="fa <?php echo $escape($card['icon']); ?>"></i></span>
                                    </div>
                                    <h3 class="summary-card__title"><?php echo $escape($card['label']); ?></h3>
                                    <p class="summary-card__value"><?php echo $escape($format_money($card['value'])); ?></p>
                                    <p class="summary-card__note"><?php echo $escape($card['support']); ?></p>
                                    <div class="summary-card__meta">
                                        <span>Inspect balances</span>
                                        <span class="summary-card__chip"><?php echo $escape(number_format($index + 1)); ?></span>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="dashboard-summary__section dashboard-summary__animate">
                <div class="dashboard-summary__section-head">
                    <div>
                        <h2 class="dashboard-summary__section-title">Portfolio by product</h2>
                        <p class="dashboard-summary__section-copy">Each card shows the outstanding product balance plus the share it contributes to the broader unpaid portfolio, making concentration easier to read.</p>
                    </div>
                    <span class="dashboard-summary__section-pill">
                        <i class="fa fa-pie-chart"></i>
                        <?php echo $escape($format_money($portfolio_reference_total)); ?> total reference
                    </span>
                </div>
                <?php if (!empty($sorted_product_balances)) { ?>
                    <div class="dashboard-summary__grid dashboard-summary__grid--products">
                        <a class="summary-card summary-card--product" href="<?php echo base_url('Loan/balances'); ?>">
                            <div class="summary-card__head">
                                <span class="summary-card__eyebrow">Institution total</span>
                                <span class="summary-card__icon"><i class="fa fa-bank"></i></span>
                            </div>
                            <h3 class="summary-card__title">All loan products combined</h3>
                            <p class="summary-card__value"><?php echo $escape($format_money($total_unpaid)); ?></p>
                            <p class="summary-card__note">This is the full unpaid portfolio across every active product currently tracked in the dashboard.</p>
                            <div class="summary-card__meta">
                                <span>View all balances</span>
                                <span class="summary-card__chip">100.0%</span>
                            </div>
                            <div class="summary-progress"><span style="width: 100%;"></span></div>
                        </a>
                        <?php foreach ($sorted_product_balances as $product) { ?>
                            <?php $product_value = (float) $product->outstanding_principal; ?>
                            <?php $product_share_value = $ratio_value($product_value, $portfolio_reference_total); ?>
                            <?php $product_share_width = $product_share_value > 100 ? 100 : $product_share_value; ?>
                            <a class="summary-card summary-card--product" href="<?php echo base_url('Loan/balances?product=') . $product->loan_product_id; ?>">
                                <div class="summary-card__head">
                                    <span class="summary-card__eyebrow">Product concentration</span>
                                    <span class="summary-card__icon"><i class="fa fa-cubes"></i></span>
                                </div>
                                <span class="summary-card__product-code"><?php echo $escape($product->product_code); ?></span>
                                <h3 class="summary-card__title"><?php echo $escape($product->product_name); ?></h3>
                                <p class="summary-card__value"><?php echo $escape($format_money($product_value)); ?></p>
                                <p class="summary-card__note"><?php echo $escape($format_ratio($product_value, $portfolio_reference_total)); ?> of the current unpaid portfolio is concentrated in this product.</p>
                                <div class="summary-card__meta">
                                    <span>Open product balances</span>
                                    <span class="summary-card__chip"><?php echo $escape($format_ratio($product_value, $portfolio_reference_total)); ?></span>
                                </div>
                                <div class="summary-progress"><span style="width: <?php echo number_format($product_share_width, 2, '.', ''); ?>%;"></span></div>
                            </a>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="summary-empty">
                        Product-level outstanding balances are not available yet. Once the dashboard cache populates them, this section will render the concentration view automatically.
                    </div>
                <?php } ?>
            </section>

            <section class="dashboard-summary__section dashboard-summary__animate">
                <div class="dashboard-summary__split">
                    <div class="summary-panel">
                        <div class="summary-panel__head">
                            <div>
                                <h3 class="summary-panel__title">Arrears ladder</h3>
                                <p class="summary-panel__copy">Delinquency buckets are grouped together so collection teams can see how risk is aging instead of scanning separate rows.</p>
                            </div>
                            <span class="summary-card__chip"><?php echo $escape($format_money($total_arrears)); ?></span>
                        </div>
                        <div class="summary-panel__grid">
                            <?php foreach ($arrears_cards as $card) { ?>
                                <a class="summary-card summary-card--<?php echo $escape($card['tone']); ?>" href="<?php echo $card['href']; ?>">
                                    <div class="summary-card__head">
                                        <span class="summary-card__eyebrow">Collections risk</span>
                                        <span class="summary-card__icon"><i class="fa <?php echo $escape($card['icon']); ?>"></i></span>
                                    </div>
                                    <h3 class="summary-card__title"><?php echo $escape($card['label']); ?></h3>
                                    <p class="summary-card__value"><?php echo $escape($format_money($card['value'])); ?></p>
                                    <p class="summary-card__note"><?php echo $escape($card['support']); ?></p>
                                    <div class="summary-card__meta">
                                        <span>Open arrears report</span>
                                        <span class="summary-card__chip">Bucket</span>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="summary-panel">
                        <div class="summary-panel__head">
                            <div>
                                <h3 class="summary-panel__title">Payments coming due</h3>
                                <p class="summary-panel__copy">These cards track the immediate collection windows that typically drive team follow-up and cash planning.</p>
                            </div>
                            <span class="summary-card__chip"><?php echo $escape($format_money($payments_month_total)); ?></span>
                        </div>
                        <div class="dashboard-summary__grid dashboard-summary__grid--three">
                            <?php foreach ($due_cards as $card) { ?>
                                <a class="summary-card summary-card--<?php echo $escape($card['tone']); ?>" href="<?php echo $card['href']; ?>">
                                    <div class="summary-card__head">
                                        <span class="summary-card__eyebrow">Due window</span>
                                        <span class="summary-card__icon"><i class="fa <?php echo $escape($card['icon']); ?>"></i></span>
                                    </div>
                                    <h3 class="summary-card__title"><?php echo $escape($card['label']); ?></h3>
                                    <p class="summary-card__value"><?php echo $escape($format_money($card['value'])); ?></p>
                                    <p class="summary-card__note"><?php echo $escape($card['support']); ?></p>
                                    <div class="summary-card__meta">
                                        <span>Open due report</span>
                                        <span class="summary-card__chip">Schedule</span>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    <?php } else { ?>
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
    <?php } ?>
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