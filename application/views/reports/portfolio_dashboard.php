<?php
$settings = isset($settings) ? $settings : get_by_id('settings','settings_id','1');
$currency = isset($settings->currency) ? $settings->currency : 'K';
$node_base_url = isset($node_base_url) ? rtrim($node_base_url, '/') : 'http://localhost:4500';
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:wght@300;600;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<style>
:root{
  --pd-bg:#f6f9f6;
  --pd-s1:#ffffff;
  --pd-s2:#edf4ee;
  --pd-s3:#e4efe6;
  --pd-bord:#d6e3d8;
  --pd-g:#153505;
  --pd-g2:#24C16B;
  --pd-am:#f5a623;
  --pd-red:#e84855;
  --pd-bl:#2f76d2;
  --pd-txt:#203022;
  --pd-mut:#60745f;
  --pd-dim:#8da08e;
}
.pd-wrap{font-family:'DM Sans',sans-serif;color:var(--pd-txt);font-size:13px;}
.pd-hdr{background:linear-gradient(90deg,#e5f4e8,#f4fbf5,#e9f7ec);border:1px solid var(--pd-bord);
  border-left:4px solid var(--pd-g);padding:14px 18px;border-radius:10px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.pd-brand{display:flex;align-items:center;gap:12px;}
.pd-mark{width:36px;height:36px;background:var(--pd-g);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-family:'Fraunces',serif;}
.pd-name{font-family:'Fraunces',serif;font-weight:700;font-size:17px;line-height:1.1;}
.pd-sub{font-size:11px;color:var(--pd-mut);text-transform:uppercase;letter-spacing:1.4px;}
.pd-live{background:rgba(36,193,107,.12);color:var(--pd-g2);border:1px solid var(--pd-g2);padding:4px 10px;border-radius:16px;font-size:10px;letter-spacing:1px;}
.pd-dt{font-size:11px;color:var(--pd-mut);}
.pd-dt b{color:var(--pd-g);font-family:'DM Mono',monospace;}
.pd-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:11px;margin:14px 0;}
.pd-kpi{background:var(--pd-s1);border:1px solid var(--pd-bord);border-radius:10px;padding:12px;position:relative;overflow:hidden;}
.pd-kbar{position:absolute;top:0;left:0;right:0;height:3px;}
.pd-kl{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--pd-mut);margin-bottom:4px;}
.pd-kv{font-family:'Fraunces',serif;font-size:20px;font-weight:600;line-height:1.1;}
.pd-kv-money{font-size:13px;line-height:1.25;word-break:break-word;}
.pd-ks{font-size:10px;color:var(--pd-dim);margin-top:4px;font-family:'DM Mono',monospace;}
.pd-cg{color:var(--pd-g)} .pd-ca{color:var(--pd-am)} .pd-cr{color:var(--pd-red)} .pd-cb{color:var(--pd-bl)}
.pd-row{display:grid;gap:14px;margin-bottom:14px;}
.pd-r3{grid-template-columns:2fr 1fr 1fr;} .pd-r2{grid-template-columns:1fr 1fr;}
.pd-card{background:var(--pd-s1);border:1px solid var(--pd-bord);border-radius:10px;padding:16px;}
.pd-tit{font-family:'Fraunces',serif;font-size:14px;font-weight:600;margin-bottom:2px;}
.pd-subt{font-size:10px;color:var(--pd-mut);margin-bottom:12px;}
.pd-tw{overflow:auto;max-height:450px;}
.pd-tw table{width:100%;border-collapse:collapse;min-width:4200px;}
.pd-tw th{font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--pd-mut);padding:8px 10px;text-align:left;border-bottom:1px solid var(--pd-bord);background:#fff;position:sticky;top:0;z-index:2;}
.pd-tw td{padding:8px 10px;border-bottom:1px solid #edf3ee;font-size:12px;white-space:nowrap;}
.pd-tw tr:hover td{background:#f8fcf8;}
.pd-mono{font-family:'DM Mono',monospace;font-size:11px;}
.pd-badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:10px;font-family:'DM Mono',monospace;}
.pd-ok{background:rgba(36,193,107,.12);color:var(--pd-g2)}
.pd-warn{background:rgba(245,166,35,.15);color:var(--pd-am)}
.pd-danger{background:rgba(232,72,85,.14);color:var(--pd-red)}
.pd-info{background:rgba(47,118,210,.14);color:var(--pd-bl)}
.pd-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 14px;}
.pd-tab{padding:8px 12px;border-radius:8px;border:1px solid var(--pd-bord);background:#fff;color:var(--pd-mut);cursor:pointer;font-size:12px;}
.pd-tab.on{border-color:var(--pd-g);color:var(--pd-g);background:#f2f8f3;}
.pd-pane{display:none;} .pd-pane.on{display:block;}
.pd-actions{display:flex;gap:8px;flex-wrap:wrap;}
.pd-btn{padding:8px 12px;border-radius:8px;border:1px solid var(--pd-bord);background:#fff;color:var(--pd-mut);font-size:12px;cursor:pointer;}
.pd-btn:hover{border-color:var(--pd-g);color:var(--pd-g);}
.pd-btn-main{background:var(--pd-g);border-color:var(--pd-g);color:#fff;}
.pd-btn-main:hover{background:#1f4a0a;color:#fff;}
.pd-gfp{background:var(--pd-s1);border:1px solid var(--pd-bord);border-radius:10px;margin:0 0 14px;}
.pd-gfp-hd{padding:10px 12px;border-bottom:1px solid var(--pd-bord);display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;}
.pd-gfp-title{font-size:12px;font-weight:600;color:var(--pd-g);}
.pd-gfp-meta{font-size:11px;color:var(--pd-mut);}
.pd-gfp-body{padding:12px;display:grid;grid-template-columns:repeat(6,minmax(120px,1fr));gap:10px;}
.pd-ff label{font-size:10px;color:var(--pd-mut);text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px;display:block;}
.pd-ff input,.pd-ff select{width:100%;border:1px solid var(--pd-bord);border-radius:7px;padding:7px 8px;font-size:12px;background:#fff;color:var(--pd-txt);}
.pd-txn-hd{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px;}
.pd-txn-pgn{display:flex;gap:6px;align-items:center;justify-content:flex-end;margin-top:8px;flex-wrap:wrap;}
.pd-txn-pgn button{border:1px solid var(--pd-bord);background:#fff;color:var(--pd-mut);border-radius:6px;padding:4px 8px;font-size:11px;}
.pd-txn-pgn button.on{border-color:var(--pd-g);color:var(--pd-g);}
.pd-txn-summary{display:flex;gap:10px;flex-wrap:wrap;padding:8px 10px;margin-bottom:8px;background:#f5faf6;border:1px solid var(--pd-bord);border-radius:8px;font-size:11px;color:var(--pd-mut);}
.pd-txn-summary b{color:var(--pd-g);font-family:'DM Mono',monospace;}
.pd-off-tools{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px;}
.pd-off-tools input,.pd-off-tools select{border:1px solid var(--pd-bord);border-radius:7px;padding:7px 8px;font-size:12px;}
@media(max-width:1200px){.pd-grid{grid-template-columns:repeat(4,1fr)}.pd-r3{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.pd-grid{grid-template-columns:repeat(2,1fr)}.pd-r3,.pd-r2{grid-template-columns:1fr}}
@media(max-width:1100px){.pd-gfp-body{grid-template-columns:repeat(3,minmax(120px,1fr));}}
@media(max-width:700px){.pd-gfp-body{grid-template-columns:repeat(2,minmax(120px,1fr));}}
</style>

<div class="main-content pd-wrap">
    <div class="page-header">
        <h2 class="header-title">Portfolio Dashboard</h2>
    </div>

    <div class="pd-hdr">
        <div class="pd-brand">
            <div class="pd-mark">S</div>
            <div>
                <div class="pd-name"><?php echo isset($settings->company_name) ? htmlspecialchars($settings->company_name) : 'Sycamore Credit Limited'; ?></div>
                <div class="pd-sub">Portfolio Intelligence Dashboard</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div class="pd-dt">As at <b id="pdAsAt">loading...</b></div>
            <div class="pd-live">LIVE HOURLY SNAPSHOT</div>
            <div class="pd-actions">
                <button class="pd-btn" id="pdRefresh">Refresh View</button>
            </div>
        </div>
    </div>

    <div class="pd-grid">
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-g)"></div><div class="pd-kl">Active Loans</div><div class="pd-kv pd-cg" id="kpiLoans">0</div><div class="pd-ks">loan accounts</div></div>
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-g)"></div><div class="pd-kl">Principal Disbursed</div><div class="pd-kv pd-kv-money pd-cg" id="kpiPrincipal">0</div><div class="pd-ks">total principal</div></div>
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-bl)"></div><div class="pd-kl">Outstanding Balance</div><div class="pd-kv pd-kv-money pd-cb" id="kpiOutstanding">0</div><div class="pd-ks">scheduled balance minus paid</div></div>
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-am)"></div><div class="pd-kl">Total Arrears</div><div class="pd-kv pd-kv-money pd-ca" id="kpiArrears">0</div><div class="pd-ks" id="kpiInArrears">0 in arrears</div></div>
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-am)"></div><div class="pd-kl">PAR 30</div><div class="pd-kv pd-ca" id="kpiPar30">0%</div><div class="pd-ks">>0 days past due</div></div>
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-red)"></div><div class="pd-kl">PAR 90</div><div class="pd-kv pd-cr" id="kpiPar90">0%</div><div class="pd-ks">>90 days past due</div></div>
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-g2)"></div><div class="pd-kl">Collection Rate</div><div class="pd-kv pd-cg" id="kpiCR">0%</div><div class="pd-ks">actual vs expected</div></div>
    </div>

    <div class="pd-tabs">
        <button class="pd-tab on" data-pane="overview">Overview & Transactions</button>
        <button class="pd-tab" data-pane="branch">Branches</button>
        <button class="pd-tab" data-pane="product">Products</button>
        <button class="pd-tab" data-pane="officer">Officers</button>
    </div>

    <div class="pd-pane on" id="pane-overview">
        <div class="pd-gfp">
            <div class="pd-gfp-hd">
                <div class="pd-gfp-title">Global Filters - all KPI/cards/charts/tables update live</div>
                <div class="pd-gfp-meta" id="gfpMeta">No filters active</div>
            </div>
            <div class="pd-gfp-body">
                <div class="pd-ff"><label>Branch</label><select id="gfBranch"></select></div>
                <div class="pd-ff"><label>Product</label><select id="gfProduct"></select></div>
                <div class="pd-ff"><label>Officer</label><select id="gfOfficer"></select></div>
                <div class="pd-ff"><label>PAR Risk</label><select id="gfRisk"><option value="">All</option><option value="healthy">Healthy (&lt;10%)</option><option value="monitor">Monitor (10-19.99%)</option><option value="critical">Critical (&gt;=20%)</option></select></div>
                <div class="pd-ff"><label>Min Collection %</label><input type="number" id="gfMinCollection" min="0" max="100" step="0.1" placeholder="0 - 100"></div>
                <div class="pd-ff"><label>Search</label><input type="text" id="gfSearch" placeholder="Loan #, customer..."></div>
            </div>
        </div>
        <div class="pd-row pd-r3">
            <div class="pd-card"><div class="pd-tit">Disbursement Trend</div><div class="pd-subt">18-month history: loan count and principal (KM)</div><canvas id="chTrend" height="210"></canvas></div>
            <div class="pd-card"><div class="pd-tit">PAR Aging</div><div class="pd-subt">Outstanding by delinquency bucket</div><canvas id="chPar" height="210"></canvas></div>
            <div class="pd-card"><div class="pd-tit">Arrears Aging Detail</div><div class="pd-subt">Loan count and arrears amount</div><canvas id="chAging" height="210"></canvas></div>
        </div>

        <div class="pd-card">
            <div class="pd-txn-hd">
                <div>
                    <div class="pd-tit">Transaction List</div>
                    <div class="pd-subt">Filtered live from latest snapshot. Click header to sort.</div>
                </div>
                <div class="pd-actions">
                    <span style="font-size:11px;color:var(--pd-mut)">Results: <b id="txnCount" style="color:var(--pd-g);font-family:'DM Mono',monospace">0 loans</b></span>
                    <button class="pd-btn" id="pdResetFilters">Reset Filters</button>
                    <button class="pd-btn" id="pdExportCsv">Export CSV</button>
                </div>
            </div>
            <div class="pd-txn-summary" id="txnSummary"></div>
            <div class="pd-tw">
                <table>
                    <thead id="txnHead"></thead>
                    <tbody id="txnBody"></tbody>
                </table>
            </div>
            <div class="pd-txn-pgn" id="txnPgn"></div>
        </div>
    </div>

    <div class="pd-pane" id="pane-branch">
        <div class="pd-row pd-r2">
            <div class="pd-card"><div class="pd-tit">Branch Portfolio vs Arrears</div><div class="pd-subt">Outstanding and arrears (millions)</div><canvas id="chBranchBar" height="260"></canvas></div>
            <div class="pd-card"><div class="pd-tit">PAR30 vs PAR90 by Branch</div><div class="pd-subt">Risk comparison by branch</div><canvas id="chBranchPar" height="260"></canvas></div>
        </div>
        <div class="pd-card">
            <div class="pd-tit">Branch Scorecards</div>
            <div class="pd-subt">Performance by branch</div>
            <div class="pd-tw">
                <table>
                    <thead><tr><th>Branch</th><th>Loans</th><th>Outstanding</th><th>Arrears</th><th>PAR30</th><th>PAR90</th><th>Collection%</th></tr></thead>
                    <tbody id="branchBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="pd-pane" id="pane-product">
        <div class="pd-row pd-r2">
            <div class="pd-card"><div class="pd-tit">Product Portfolio & Arrears</div><div class="pd-subt">Outstanding and arrears (KM)</div><canvas id="chProductBar" height="300"></canvas></div>
            <div class="pd-card"><div class="pd-tit">PAR Rate by Product</div><div class="pd-subt">Red = critical, amber = monitor, green = healthy</div><canvas id="chProductPar" height="300"></canvas></div>
        </div>
        <div class="pd-card">
            <div class="pd-tit">Product Summary</div>
            <div class="pd-subt">Snapshot grouped by product</div>
            <div class="pd-tw">
                <table>
                    <thead><tr><th>Product</th><th>Loans</th><th>Outstanding (KM)</th><th>Arrears (KM)</th><th>PAR Rate</th><th>Action</th></tr></thead>
                    <tbody id="productBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="pd-pane" id="pane-officer">
        <div class="pd-off-tools">
            <input type="text" id="officerSearch" placeholder="Search officer..." style="min-width:220px;">
            <select id="officerSort">
                <option value="outstanding">Sort: Portfolio Size</option>
                <option value="par30">Sort: PAR Rate High-Low</option>
                <option value="collection_rate">Sort: Collection Rate</option>
                <option value="loans_count">Sort: Loan Count</option>
            </select>
            <span style="font-size:11px;color:var(--pd-mut)">Showing <b id="officerCount" style="color:var(--pd-g);font-family:'DM Mono',monospace">0 officers</b></span>
        </div>
        <div class="pd-row pd-r2">
            <div class="pd-card"><div class="pd-tit">Top 10 Portfolio Size</div><div class="pd-subt">Outstanding managed per officer</div><canvas id="chOfficerTop" height="280"></canvas></div>
            <div class="pd-card"><div class="pd-tit">Top 10 PAR Rate</div><div class="pd-subt">Risk by officer portfolio</div><canvas id="chOfficerPar" height="280"></canvas></div>
        </div>
        <div class="pd-card">
            <div class="pd-tit">Officer Performance Table</div>
            <div class="pd-subt">All officers from latest snapshot</div>
            <div class="pd-tw">
                <table>
                    <thead><tr><th>Officer</th><th>Loans</th><th>Outstanding</th><th>Arrears</th><th>PAR Rate</th><th>Collection%</th><th>Risk</th></tr></thead>
                    <tbody id="officerBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const dashboardDataUrl = <?php echo json_encode(isset($dashboard_data_url) ? $dashboard_data_url : base_url('reports/portfolio_dashboard_data')); ?>;
    const dashboardFilteredDataUrl = <?php echo json_encode(base_url('reports/portfolio_dashboard_filtered_data')); ?>;
    const currency = <?php echo json_encode($currency); ?>;
    const fmtN = (n) => Number(n || 0).toLocaleString();
    const fmtM = (n) => currency + ' ' + Number(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const fmtP = (n) => Number(n || 0).toFixed(2) + '%';
    const raw = { snapshot: null, loans: [], totalLoans: 0 };
    let filtered = [];
    let charts = {};
    let lastServerData = null;
    let txnSort = { key: 'outstanding', dir: 'desc' };
    let txnPage = 0;
    const pageSize = 50;
    const txnCols = [
        {k:'customer_name', h:'Customer Name', t:'text'},
        {k:'customer_group', h:'Customer Group', t:'text'},
        {k:'loan_number', h:'Loan Number', t:'mono'},
        {k:'product_name', h:'Product', t:'text'},
        {k:'branch_name', h:'Branch', t:'text'},
        {k:'loan_date', h:'Loan Date', t:'date'},
        {k:'loan_principal_amount', h:'Principal Amount', t:'money'},
        {k:'period', h:'Loan Period', t:'text'},
        {k:'interest_rate', h:'Interest Rate', t:'interest'},
        {k:'total_loan_amount', h:'Total Loan Amount', t:'money'},
        {k:'installment_amount', h:'Installment Amount', t:'money'},
        {k:'gross_loan_portfolio', h:'Unpaid Principal (MWK)', t:'money'},
        {k:'accrued_charges', h:'Accrued Charges (MWK)', t:'money'},
        {k:'outstanding', h:'Outstanding Balance (MWK)', t:'money'},
        {k:'arrears', h:'Amount in Arrears (MWK)', t:'money'},
        {k:'days_arrears', h:'Days in Arrears', t:'number'},
        {k:'par_classification', h:'PAR Classification', t:'par'},
        {k:'rbm_classification', h:'RBM Loan Classification', t:'text'},
        {k:'payments_in_arrears', h:'Payments in Arrears', t:'number'},
        {k:'collection_rate', h:'Collection Rate', t:'percent'},
        {k:'last_payment_date', h:'Last Payment Date', t:'date'},
        {k:'collateral_value', h:'Collateral Value', t:'money'},
        {k:'maturity_date', h:'Maturity Date', t:'date'},
        {k:'total_expected', h:'Expected Installments', t:'money'},
        {k:'actual_payments', h:'Actual Payments', t:'money'},
        {k:'loan_status', h:'Loan Status', t:'status'},
        {k:'officer_name', h:'Loan Officer', t:'text'},
        {k:'relationship_supervisor', h:'Relationship Supervisor', t:'text'},
        {k:'loan_added_date', h:'Date Added', t:'date'},
    ];

    function formatDate(v){
        if (!v) return '--';
        const d = new Date(String(v).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return String(v);
        return d.toLocaleDateString('en-GB');
    }

    function parClassBadge(value){
        const v = String(value || 'Current');
        let cls = 'pd-ok';
        if (v === 'PAR1' || v === 'PAR30') cls = 'pd-warn';
        if (v === 'PAR60' || v === 'PAR90' || v === 'PAR180') cls = 'pd-danger';
        return `<span class="pd-badge ${cls}">${escapeHtml(v)}</span>`;
    }

    function renderTxnCell(l, col){
        let v = l[col.k];
        if (col.k === 'gross_loan_portfolio' && (v == null || v === '')) {
            v = l.principal;
        }
        switch (col.t) {
            case 'money':
                return `<td class="pd-mono">${fmtM(v)}</td>`;
            case 'percent':
                return `<td class="pd-mono">${fmtP(v)}</td>`;
            case 'interest':
                return `<td class="pd-mono">${Number(v || 0).toFixed(2)}%</td>`;
            case 'number':
                return `<td class="pd-mono">${Number(v || 0).toLocaleString()}</td>`;
            case 'date':
                return `<td class="pd-mono">${escapeHtml(formatDate(v))}</td>`;
            case 'par':
                return `<td>${parClassBadge(v)}</td>`;
            case 'status':
                return `<td><span class="pd-badge pd-info">${escapeHtml(v || '--')}</span></td>`;
            case 'mono':
                return `<td class="pd-mono">${escapeHtml(v || '--')}</td>`;
            default:
                return `<td>${escapeHtml(v || '--')}</td>`;
        }
    }

    function riskBadge(par){
        par = Number(par || 0);
        if (par >= 20) return '<span class="pd-badge pd-danger">Critical</span>';
        if (par >= 10) return '<span class="pd-badge pd-warn">Monitor</span>';
        return '<span class="pd-badge pd-ok">Healthy</span>';
    }

    function loanParRate(loan){
        const out = Number(loan && loan.outstanding || 0);
        const arr = Number(loan && loan.arrears || 0);
        if (out <= 0) return 0;
        return (arr / out) * 100;
    }

    function formatAsAt(rawValue){
        if (!rawValue) return 'No snapshot yet';
        const d = new Date(String(rawValue).replace(' ', 'T'));
        if (!Number.isNaN(d.getTime())) return d.toLocaleString('en-GB');
        return String(rawValue);
    }

    function escapeHtml(v){
        return String(v || '').replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    function toMonthYear(v){
        const d = new Date(v);
        if (Number.isNaN(d.getTime())) return null;
        const m = String(d.getMonth() + 1).padStart(2, '0');
        return d.getFullYear() + '-' + m;
    }

    function setupFilterSelect(id, values){
        const el = document.getElementById(id);
        if (!el) return;
        const current = el.value || '';
        const options = ['<option value="">All</option>'].concat(values.map(v => '<option value="' + escapeHtml(v) + '">' + escapeHtml(v) + '</option>'));
        el.innerHTML = options.join('');
        el.value = values.includes(current) ? current : '';
    }

    function buildFilters(){
        setupFilterSelect('gfBranch', [...new Set(raw.loans.map(l => l.branch_name || 'Unknown'))].sort());
        setupFilterSelect('gfProduct', [...new Set(raw.loans.map(l => l.product_name || 'Unknown'))].sort());
        setupFilterSelect('gfOfficer', [...new Set(raw.loans.map(l => l.officer_name || 'Unknown'))].sort());
    }

    function collectFilters(){
        return {
            branch: document.getElementById('gfBranch').value,
            product: document.getElementById('gfProduct').value,
            officer: document.getElementById('gfOfficer').value,
            risk: document.getElementById('gfRisk').value,
            min_collection: document.getElementById('gfMinCollection').value || '',
            q: (document.getElementById('gfSearch').value || '').trim(),
        };
    }

    async function applyFilters(){
        const f = collectFilters();
        const qs = new URLSearchParams();
        Object.keys(f).forEach(k => { if (f[k] !== '') qs.append(k, f[k]); });
        try {
            const res = await fetch(dashboardFilteredDataUrl + (qs.toString() ? ('?' + qs.toString()) : ''), { cache: 'no-store' });
            const rawText = await res.text();
            if (!res.ok) throw new Error('HTTP ' + res.status + ' - ' + rawText.slice(0, 180));
            const data = JSON.parse(rawText);
            if (!data || data.success === false) throw new Error((data && data.message) || 'Filtered endpoint failed');
            raw.snapshot = data.snapshot || {};
            filtered = Array.isArray(data.loans) ? data.loans : [];
            lastServerData = data;
            txnPage = 0;
            updateDashboard(data);
        } catch (e) {
            console.error(e);
            alert('Failed to apply dashboard filters from DB snapshot.\n' + (e.message || 'Unknown error'));
        }
    }

    function applyFilters_old_unused_local(){
        const branch = document.getElementById('gfBranch').value;
        const product = document.getElementById('gfProduct').value;
        const officer = document.getElementById('gfOfficer').value;
        const risk = document.getElementById('gfRisk').value;
        const minCollection = Number(document.getElementById('gfMinCollection').value || 0);
        const q = (document.getElementById('gfSearch').value || '').trim().toLowerCase();

        filtered = raw.loans.filter(l => {
            const par = loanParRate(l);
            if (branch && (l.branch_name || 'Unknown') !== branch) return false;
            if (product && (l.product_name || 'Unknown') !== product) return false;
            if (officer && (l.officer_name || 'Unknown') !== officer) return false;
            if (risk === 'healthy' && par >= 10) return false;
            if (risk === 'monitor' && (par < 10 || par >= 20)) return false;
            if (risk === 'critical' && par < 20) return false;
            if (Number(l.collection_rate || 0) < minCollection) return false;
            if (q) {
                const hay = [l.loan_number, l.customer_name, l.product_name, l.branch_name, l.officer_name].join(' ').toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
        txnPage = 0;
        updateDashboard();
    }

    function summarizeLoans(loans){
        const summary = { active_loans: loans.length, total_principal:0, total_outstanding:0, total_arrears:0, loans_in_arrears:0, par30:0, par90:0, collection_rate:0 };
        let totalCollectionRate = 0;
        let par30Numerator = 0;
        let par90Numerator = 0;
        loans.forEach(l => {
            const outstanding = Number(l.outstanding || 0);
            const arrears = Number(l.arrears || 0);
            const days = Number(l.days_arrears || 0);
            summary.total_principal += Number(l.principal || 0);
            summary.total_outstanding += outstanding;
            summary.total_arrears += arrears;
            if (days > 0) summary.loans_in_arrears += 1;
            if (days > 0) par30Numerator += outstanding;
            if (days > 90) par90Numerator += outstanding;
            totalCollectionRate += Number(l.collection_rate || 0);
        });
        summary.collection_rate = loans.length ? totalCollectionRate / loans.length : 0;
        summary.par30 = summary.total_outstanding > 0 ? (par30Numerator / summary.total_outstanding) * 100 : 0;
        summary.par90 = summary.total_outstanding > 0 ? (par90Numerator / summary.total_outstanding) * 100 : 0;
        return summary;
    }

    function renderKpis(){
        const s = raw.snapshot || summarizeLoans(filtered);
        document.getElementById('pdAsAt').textContent = formatAsAt(s.snapshot_time || s.as_of_date);
        document.getElementById('kpiLoans').textContent = fmtN(s.active_loans);
        document.getElementById('kpiPrincipal').textContent = fmtM(s.total_principal);
        document.getElementById('kpiOutstanding').textContent = fmtM(s.total_outstanding);
        document.getElementById('kpiArrears').textContent = fmtM(s.total_arrears);
        document.getElementById('kpiInArrears').textContent = fmtN(s.loans_in_arrears) + ' in arrears';
        document.getElementById('kpiPar30').textContent = fmtP(s.par30);
        document.getElementById('kpiPar90').textContent = fmtP(s.par90);
        document.getElementById('kpiCR').textContent = fmtP(s.collection_rate);
        document.getElementById('gfpMeta').textContent = filtered.length + ' of ' + raw.totalLoans + ' loans in view';
    }

    function byGroup(list, keyField){
        const map = {};
        list.forEach(l => {
            const key = l[keyField] || 'Unknown';
            if (!map[key]) map[key] = { key, loans_count:0, outstanding:0, arrears:0, collection:0, parLoanSet:[] };
            map[key].loans_count++;
            map[key].outstanding += Number(l.outstanding || 0);
            map[key].arrears += Number(l.arrears || 0);
            map[key].collection += Number(l.collection_rate || 0);
            map[key].parLoanSet.push(l);
        });
        return Object.values(map).map(r => {
            const par30Numerator = r.parLoanSet.filter(x => Number(x.days_arrears || 0) > 0).reduce((s,x) => s + Number(x.outstanding || 0), 0);
            const par90Numerator = r.parLoanSet.filter(x => Number(x.days_arrears || 0) > 90).reduce((s,x) => s + Number(x.outstanding || 0), 0);
            return {
                name: r.key,
                loans_count: r.loans_count,
                outstanding: r.outstanding,
                arrears: r.arrears,
                par30: r.outstanding ? (par30Numerator / r.outstanding) * 100 : 0,
                par90: r.outstanding ? (par90Numerator / r.outstanding) * 100 : 0,
                collection_rate: r.loans_count ? (r.collection / r.loans_count) : 0
            };
        }).sort((a,b) => b.outstanding - a.outstanding);
    }

    function destroyCharts(){ Object.values(charts).forEach(c => { try { c.destroy(); } catch(_){} }); charts = {}; }

    function renderCharts(serverData){
        if (typeof Chart === 'undefined') {
            return;
        }
        destroyCharts();
        Chart.defaults.color = '#60745f';
        Chart.defaults.borderColor = '#d6e3d8';
        Chart.defaults.font.family = "'DM Sans',sans-serif";

        const trendRows = (serverData && Array.isArray(serverData.trend)) ? serverData.trend : [];
        const trendLabels = trendRows.map(t => t.month_year);
        charts.trend = new Chart('chTrend', { type:'bar', data:{ labels: trendLabels, datasets:[
            { label:'# Loans', data: trendRows.map(t => Number(t.loan_count || 0)), backgroundColor:'rgba(36,193,107,0.45)', borderColor:'#24C16B', borderWidth:1, yAxisID:'y1' },
            { label:'Principal (KM)', type:'line', data: trendRows.map(t => Number(t.principal_km || 0)), borderColor:'#f5a623', backgroundColor:'rgba(245,166,35,0.10)', tension:0.35, yAxisID:'y2', fill:true }
        ]}, options:{ responsive:true, interaction:{mode:'index',intersect:false}, scales:{ y1:{position:'left'}, y2:{position:'right',grid:{drawOnChartArea:false}} } }});

        const buckets = (serverData && Array.isArray(serverData.aging)) ? serverData.aging : [];
        charts.par = new Chart('chPar', { type:'doughnut', data:{ labels:buckets.map(x=>x.bucket), datasets:[{ data:buckets.map(x=>x.outstanding_balance), backgroundColor:['#a8d8a6','#c7e7b6','#f5a623','#e8834a','#e84855'] }]}, options:{responsive:true,cutout:'60%'} });
        charts.aging = new Chart('chAging', { type:'bar', data:{ labels:buckets.map(x=>x.bucket), datasets:[
            {label:'# Loans', data:buckets.map(x=>x.loan_count), backgroundColor:'rgba(36,193,107,0.45)', borderRadius:3, yAxisID:'y1'},
            {label:'Arrears KM', data:buckets.map(x=>x.arrears_km), backgroundColor:'rgba(232,72,85,0.45)', borderRadius:3, yAxisID:'y2'}
        ]}, options:{responsive:true,interaction:{mode:'index',intersect:false}, scales:{y1:{position:'left'},y2:{position:'right',grid:{drawOnChartArea:false}}}} });

        const branches = (serverData && Array.isArray(serverData.branches)) ? serverData.branches.map(b => ({name:b.branch_name, outstanding:Number(b.outstanding||0), arrears:Number(b.arrears||0), par30:Number(b.par30||0), par90:Number(b.par90||0)})) : [];
        charts.branchBar = new Chart('chBranchBar', { type:'bar', data:{labels:branches.map(b=>b.name), datasets:[
            {label:'Outstanding (M)', data:branches.map(b=>b.outstanding/1e6), backgroundColor:'rgba(21,53,5,0.5)', borderRadius:3},
            {label:'Arrears (M)', data:branches.map(b=>b.arrears/1e6), backgroundColor:'rgba(245,166,35,0.5)', borderRadius:3}
        ]}, options:{responsive:true,interaction:{mode:'index',intersect:false}} });
        charts.branchPar = new Chart('chBranchPar', { type:'bar', data:{labels:branches.map(b=>b.name), datasets:[
            {label:'PAR30%', data:branches.map(b=>b.par30), backgroundColor:'rgba(245,166,35,0.65)', borderRadius:3},
            {label:'PAR90%', data:branches.map(b=>b.par90), backgroundColor:'rgba(232,72,85,0.65)', borderRadius:3}
        ]}, options:{responsive:true,interaction:{mode:'index',intersect:false}} });

        const products = (serverData && Array.isArray(serverData.products)) ? serverData.products.map(p => ({name:p.product_name, outstanding:Number(p.outstanding_km||0)*1e6, arrears:Number(p.arrears_km||0)*1e6, par30:Number(p.par_rate||0)})) : [];
        charts.productBar = new Chart('chProductBar', { type:'bar', data:{labels:products.map(p=>p.name), datasets:[
            {label:'Outstanding KM', data:products.map(p=>p.outstanding/1e6), backgroundColor:'rgba(21,53,5,0.5)', borderRadius:3},
            {label:'Arrears KM', data:products.map(p=>p.arrears/1e6), backgroundColor:'rgba(232,72,85,0.5)', borderRadius:3}
        ]}, options:{responsive:true,indexAxis:'y'} });
        charts.productPar = new Chart('chProductPar', { type:'bar', data:{labels:products.map(p=>p.name), datasets:[{
            label:'PAR%', data:products.map(p=>p.par30), backgroundColor:products.map(p=>p.par30>=20?'rgba(232,72,85,0.75)':p.par30>=10?'rgba(245,166,35,0.75)':'rgba(36,193,107,0.75)'), borderRadius:3
        }]}, options:{responsive:true,indexAxis:'y',plugins:{legend:{display:false}}} });

        const officers = (serverData && Array.isArray(serverData.officers)) ? serverData.officers.map(o => ({name:o.officer_name, outstanding:Number(o.outstanding||0), par30:Number(o.par_rate||0)})) : [];
        const top = officers.slice(0, 10);
        charts.offTop = new Chart('chOfficerTop', { type:'bar', data:{labels:top.map(o=>o.name), datasets:[{label:'Outstanding (M)', data:top.map(o=>o.outstanding/1e6), backgroundColor:'rgba(21,53,5,0.5)', borderRadius:3}]}, options:{responsive:true,indexAxis:'y',plugins:{legend:{display:false}}} });
        charts.offPar = new Chart('chOfficerPar', { type:'bar', data:{labels:top.map(o=>o.name), datasets:[{label:'PAR%', data:top.map(o=>o.par30), backgroundColor:top.map(o=>o.par30>=20?'rgba(232,72,85,0.75)':o.par30>=10?'rgba(245,166,35,0.75)':'rgba(36,193,107,0.75)'), borderRadius:3}]}, options:{responsive:true,indexAxis:'y',plugins:{legend:{display:false}}} });
    }

    function renderTxn(){
        const icon = txnSort.dir === 'asc' ? '▲' : '▼';
        document.getElementById('txnHead').innerHTML = '<tr>' + txnCols.map(c => `<th style="cursor:pointer" data-sort="${c.k}">${c.h} ${txnSort.key===c.k?`<span style="font-size:9px;color:var(--pd-dim)">${icon}</span>`:''}</th>`).join('') + '</tr>';
        document.querySelectorAll('#txnHead [data-sort]').forEach(th => th.addEventListener('click', () => {
            const key = th.getAttribute('data-sort');
            if (txnSort.key === key) txnSort.dir = txnSort.dir === 'asc' ? 'desc' : 'asc';
            else txnSort = { key, dir: 'asc' };
            renderTxn();
        }));

        const sortKey = txnSort.key;
        const dir = txnSort.dir === 'asc' ? 1 : -1;
        const list = filtered.slice().sort((a,b) => {
            const av = a[sortKey], bv = b[sortKey];
            if (typeof av === 'number' || typeof bv === 'number') return (Number(av||0)-Number(bv||0))*dir;
            return String(av||'').localeCompare(String(bv||'')) * dir;
        });
        const start = txnPage * pageSize;
        const rows = list.slice(start, start + pageSize);
        document.getElementById('txnBody').innerHTML = rows.map(l => `
            <tr>${txnCols.map(col => renderTxnCell(l, col)).join('')}</tr>`).join('');
        document.getElementById('txnCount').textContent = fmtN(list.length) + ' loans';
        const sumLoanPrincipal = list.reduce((s,l)=>s+Number(l.loan_principal_amount||0),0);
        const sumGross = list.reduce((s,l)=>s+Number(l.gross_loan_portfolio||l.principal||0),0);
        const sumAccrued = list.reduce((s,l)=>s+Number(l.accrued_charges||0),0);
        const sumOutstanding = list.reduce((s,l)=>s+Number(l.outstanding||0),0);
        const sumArrears = list.reduce((s,l)=>s+Number(l.arrears||0),0);
        document.getElementById('txnSummary').innerHTML = `<span>Disbursed Principal <b>${fmtM(sumLoanPrincipal)}</b></span><span>Unpaid Principal <b>${fmtM(sumGross)}</b></span><span>Accrued Charges <b>${fmtM(sumAccrued)}</b></span><span>Outstanding <b>${fmtM(sumOutstanding)}</b></span><span>Arrears <b>${fmtM(sumArrears)}</b></span>`;

        const total = Math.max(1, Math.ceil(list.length / pageSize));
        let html = `<span>${fmtN(start+1)}-${fmtN(Math.min(start+pageSize, list.length))} of ${fmtN(list.length)}</span>`;
        html += `<button ${txnPage<=0?'disabled':''} id="txnPrev">Prev</button>`;
        for (let i = Math.max(0, txnPage-2); i < Math.min(total, txnPage+3); i++) html += `<button class="${i===txnPage?'on':''}" data-pg="${i}">${i+1}</button>`;
        html += `<button ${txnPage>=total-1?'disabled':''} id="txnNext">Next</button>`;
        const pgn = document.getElementById('txnPgn');
        pgn.innerHTML = html;
        pgn.querySelectorAll('[data-pg]').forEach(btn => btn.addEventListener('click', () => { txnPage = Number(btn.dataset.pg); renderTxn(); }));
        const prev = document.getElementById('txnPrev');
        const next = document.getElementById('txnNext');
        if (prev) prev.onclick = () => { if (txnPage > 0) { txnPage--; renderTxn(); } };
        if (next) next.onclick = () => { if (txnPage < total - 1) { txnPage++; renderTxn(); } };
    }

    function renderSummaryTables(serverData){
        const branches = (serverData && Array.isArray(serverData.branches)) ? serverData.branches.map(b => ({name:b.branch_name, loans_count:Number(b.loans_count||0), outstanding:Number(b.outstanding||0), arrears:Number(b.arrears||0), par30:Number(b.par30||0), par90:Number(b.par90||0), collection_rate:Number(b.collection_rate||0)})) : [];
        const products = (serverData && Array.isArray(serverData.products)) ? serverData.products.map(p => ({name:p.product_name, loans_count:Number(p.loans_count||0), outstanding:Number(p.outstanding_km||0)*1e6, arrears:Number(p.arrears_km||0)*1e6, par30:Number(p.par_rate||0)})) : [];
        let officers = (serverData && Array.isArray(serverData.officers)) ? serverData.officers.map(o => ({name:o.officer_name, loans_count:Number(o.loans_count||0), outstanding:Number(o.outstanding||0), arrears:Number(o.arrears||0), par30:Number(o.par_rate||0), collection_rate:Number(o.collection_rate||0)})) : [];
        const officerQ = (document.getElementById('officerSearch').value || '').trim().toLowerCase();
        const officerSort = document.getElementById('officerSort').value || 'outstanding';
        if (officerQ) officers = officers.filter(o => o.name.toLowerCase().includes(officerQ));
        officers.sort((a,b) => Number(b[officerSort]||0) - Number(a[officerSort]||0));

        document.getElementById('branchBody').innerHTML = branches.map(b => `<tr><td>${escapeHtml(b.name)}</td><td class="pd-mono">${fmtN(b.loans_count)}</td><td class="pd-mono">${fmtM(b.outstanding)}</td><td class="pd-mono" style="color:#f5a623">${fmtM(b.arrears)}</td><td><span class="pd-badge ${b.par30>=20?'pd-danger':b.par30>=10?'pd-warn':'pd-ok'}">${fmtP(b.par30)}</span></td><td><span class="pd-badge ${b.par90>=20?'pd-danger':b.par90>=10?'pd-warn':'pd-ok'}">${fmtP(b.par90)}</span></td><td class="pd-mono">${fmtP(b.collection_rate)}</td></tr>`).join('');
        document.getElementById('productBody').innerHTML = products.map(p => `<tr><td style="cursor:pointer;color:var(--pd-bl)" data-product="${escapeHtml(p.name)}">${escapeHtml(p.name)}</td><td class="pd-mono">${fmtN(p.loans_count)}</td><td class="pd-mono">${(p.outstanding/1e6).toFixed(2)}</td><td class="pd-mono" style="color:#f5a623">${(p.arrears/1e6).toFixed(2)}</td><td><span class="pd-badge ${p.par30>=20?'pd-danger':p.par30>=10?'pd-warn':'pd-ok'}">${fmtP(p.par30)}</span></td><td><button class="pd-btn" data-product-btn="${escapeHtml(p.name)}">View Loans</button></td></tr>`).join('');
        document.getElementById('officerBody').innerHTML = officers.map(o => `<tr><td style="cursor:pointer;color:var(--pd-bl)" data-officer="${escapeHtml(o.name)}">${escapeHtml(o.name)}</td><td class="pd-mono">${fmtN(o.loans_count)}</td><td class="pd-mono">${fmtM(o.outstanding)}</td><td class="pd-mono" style="color:#f5a623">${fmtM(o.arrears)}</td><td><span class="pd-badge ${o.par30>=20?'pd-danger':o.par30>=10?'pd-warn':'pd-ok'}">${fmtP(o.par30)}</span></td><td class="pd-mono">${fmtP(o.collection_rate)}</td><td>${riskBadge(o.par30)}</td></tr>`).join('');
        document.getElementById('officerCount').textContent = fmtN(officers.length) + ' officers';

        document.querySelectorAll('[data-product],[data-product-btn]').forEach(el => {
            el.addEventListener('click', () => {
                document.getElementById('gfProduct').value = el.getAttribute('data-product') || el.getAttribute('data-product-btn') || '';
                applyFilters();
                document.querySelector('.pd-tab[data-pane="overview"]').click();
            });
        });
        document.querySelectorAll('[data-officer]').forEach(el => {
            el.addEventListener('click', () => {
                document.getElementById('gfOfficer').value = el.getAttribute('data-officer') || '';
                applyFilters();
                document.querySelector('.pd-tab[data-pane="overview"]').click();
            });
        });
    }

    function updateDashboard(serverData){
        renderKpis();
        try { renderCharts(serverData); } catch (e) { console.error('renderCharts failed', e); }
        try { renderSummaryTables(serverData); } catch (e) { console.error('renderSummaryTables failed', e); }
        try { renderTxn(); } catch (e) { console.error('renderTxn failed', e); }
    }

    function exportCSV(){
        const headers = txnCols.map(c => c.h);
        const rows = filtered.map(l => txnCols.map(c => {
            const v = l[c.k];
            if (c.t === 'money' || c.t === 'number' || c.t === 'percent' || c.t === 'interest') return v ?? '';
            if (c.t === 'date') return formatDate(v);
            return v ?? '';
        }));
        const csv = [headers.join(',')].concat(rows.map(r => r.map(v => `"${String(v ?? '').replace(/"/g,'""')}"`).join(','))).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'portfolio_dashboard_loans.csv';
        a.click();
    }

    async function loadDashboard(){
        try {
            const res = await fetch(dashboardDataUrl, { cache: 'no-store' });
            const rawText = await res.text();
            if (!res.ok) throw new Error('HTTP ' + res.status + ' - ' + rawText.slice(0, 180));
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (e) {
                throw new Error('Invalid JSON response: ' + rawText.slice(0, 180));
            }
            if (!data || data.success === false) {
                document.getElementById('pdAsAt').textContent = 'No snapshot yet';
                raw.snapshot = null;
                raw.loans = [];
                raw.totalLoans = 0;
                filtered = [];
                updateDashboard(data);
                return;
            }
            raw.totalLoans = Array.isArray(data.loans) ? data.loans.length : 0;
            raw.loans = Array.isArray(data.loans) ? data.loans : [];
            buildFilters();
            await applyFilters();
        } catch (err) {
            console.error(err);
            alert('Failed to load Portfolio Dashboard data from database snapshot.\n' + (err.message || 'Unknown error'));
        }
    }

    function initTabs(){
        document.querySelectorAll('.pd-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.pd-tab').forEach(b => b.classList.remove('on'));
                document.querySelectorAll('.pd-pane').forEach(p => p.classList.remove('on'));
                btn.classList.add('on');
                document.getElementById('pane-' + btn.dataset.pane).classList.add('on');
            });
        });
    }

    ['gfBranch','gfProduct','gfOfficer','gfRisk','gfMinCollection','gfSearch'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', applyFilters);
        if (el) el.addEventListener('change', applyFilters);
    });
    const resetBtn = document.getElementById('pdResetFilters');
    if (resetBtn) resetBtn.addEventListener('click', () => {
        ['gfBranch','gfProduct','gfOfficer','gfRisk','gfMinCollection','gfSearch'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.value = '';
        });
        applyFilters();
    });
    const exportBtn = document.getElementById('pdExportCsv');
    if (exportBtn) exportBtn.addEventListener('click', exportCSV);
    const refreshBtn = document.getElementById('pdRefresh');
    if (refreshBtn) refreshBtn.addEventListener('click', loadDashboard);
    const officerSearchEl = document.getElementById('officerSearch');
    if (officerSearchEl) officerSearchEl.addEventListener('input', () => renderSummaryTables(lastServerData));
    const officerSortEl = document.getElementById('officerSort');
    if (officerSortEl) officerSortEl.addEventListener('change', () => renderSummaryTables(lastServerData));

    initTabs();
    loadDashboard();
    setInterval(loadDashboard, 5 * 60 * 1000);
})();
</script>
