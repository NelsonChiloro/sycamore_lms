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
.pd-ks{font-size:10px;color:var(--pd-dim);margin-top:4px;font-family:'DM Mono',monospace;}
.pd-cg{color:var(--pd-g)} .pd-ca{color:var(--pd-am)} .pd-cr{color:var(--pd-red)} .pd-cb{color:var(--pd-bl)}
.pd-row{display:grid;gap:14px;margin-bottom:14px;}
.pd-r3{grid-template-columns:2fr 1fr 1fr;} .pd-r2{grid-template-columns:1fr 1fr;}
.pd-card{background:var(--pd-s1);border:1px solid var(--pd-bord);border-radius:10px;padding:16px;}
.pd-tit{font-family:'Fraunces',serif;font-size:14px;font-weight:600;margin-bottom:2px;}
.pd-subt{font-size:10px;color:var(--pd-mut);margin-bottom:12px;}
.pd-tw{overflow:auto;max-height:450px;}
.pd-tw table{width:100%;border-collapse:collapse;min-width:920px;}
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
@media(max-width:1200px){.pd-grid{grid-template-columns:repeat(4,1fr)}.pd-r3{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.pd-grid{grid-template-columns:repeat(2,1fr)}.pd-r3,.pd-r2{grid-template-columns:1fr}}
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
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-g)"></div><div class="pd-kl">Principal Disbursed</div><div class="pd-kv pd-cg" id="kpiPrincipal">0</div><div class="pd-ks">total principal</div></div>
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-bl)"></div><div class="pd-kl">Outstanding Balance</div><div class="pd-kv pd-cb" id="kpiOutstanding">0</div><div class="pd-ks">amount to collect</div></div>
        <div class="pd-kpi"><div class="pd-kbar" style="background:var(--pd-am)"></div><div class="pd-kl">Total Arrears</div><div class="pd-kv pd-ca" id="kpiArrears">0</div><div class="pd-ks" id="kpiInArrears">0 in arrears</div></div>
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
        <div class="pd-row pd-r3">
            <div class="pd-card"><div class="pd-tit">Disbursement Trend</div><div class="pd-subt">18-month history: loan count and principal (KM)</div><canvas id="chTrend" height="210"></canvas></div>
            <div class="pd-card"><div class="pd-tit">PAR Aging</div><div class="pd-subt">Outstanding by delinquency bucket</div><canvas id="chPar" height="210"></canvas></div>
            <div class="pd-card"><div class="pd-tit">Arrears Aging Detail</div><div class="pd-subt">Loan count and arrears amount</div><canvas id="chAging" height="210"></canvas></div>
        </div>

        <div class="pd-card">
            <div class="pd-tit">Transaction List</div>
            <div class="pd-subt">Hourly snapshot data from database</div>
            <div class="pd-tw">
                <table>
                    <thead>
                    <tr>
                        <th>Loan #</th><th>Customer</th><th>Product</th><th>Branch</th><th>Officer</th>
                        <th>Principal</th><th>Outstanding</th><th>Arrears</th><th>Days</th><th>Collection%</th>
                        <th>Interest</th><th>Period</th><th>Status</th>
                    </tr>
                    </thead>
                    <tbody id="txnBody"></tbody>
                </table>
            </div>
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
                    <thead><tr><th>Product</th><th>Loans</th><th>Outstanding (KM)</th><th>Arrears (KM)</th><th>PAR Rate</th></tr></thead>
                    <tbody id="productBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="pd-pane" id="pane-officer">
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
    const nodeBase = <?php echo json_encode($node_base_url); ?>;
    const currency = <?php echo json_encode($currency); ?>;
    const fmtN = (n) => Number(n || 0).toLocaleString();
    const fmtM = (n) => currency + Number(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const fmtP = (n) => Number(n || 0).toFixed(2) + '%';
    const fmtK = (n) => currency + (Number(n || 0) / 1000000).toFixed(2) + 'M';

    let charts = {};

    function riskBadge(par){
        par = Number(par || 0);
        if (par >= 20) return '<span class="pd-badge pd-danger">Critical</span>';
        if (par >= 10) return '<span class="pd-badge pd-warn">Monitor</span>';
        return '<span class="pd-badge pd-ok">Healthy</span>';
    }

    function formatAsAt(rawValue){
        if (!rawValue) {
            return 'No snapshot yet';
        }

        // Supports MySQL datetime (YYYY-MM-DD HH:mm:ss) and ISO values.
        const normalized = String(rawValue).replace('T', ' ').replace('Z', '').trim();
        const match = normalized.match(/^(\d{4})-(\d{2})-(\d{2})(?:\s+(\d{2}):(\d{2}))?/);
        if (match) {
            const year = Number(match[1]);
            const month = Number(match[2]) - 1;
            const day = Number(match[3]);
            const hour = Number(match[4] || 0);
            const minute = Number(match[5] || 0);
            const d = new Date(year, month, day, hour, minute, 0);
            return new Intl.DateTimeFormat('en-GB', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }).format(d);
        }

        const fallback = new Date(rawValue);
        if (!Number.isNaN(fallback.getTime())) {
            return new Intl.DateTimeFormat('en-GB', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }).format(fallback);
        }

        return String(rawValue);
    }

    function renderKpis(s){
        document.getElementById('pdAsAt').textContent = formatAsAt(s.snapshot_time || s.as_of_date);
        document.getElementById('kpiLoans').textContent      = fmtN(s.active_loans);
        document.getElementById('kpiPrincipal').textContent  = fmtK(s.total_principal);
        document.getElementById('kpiOutstanding').textContent= fmtK(s.total_outstanding);
        document.getElementById('kpiArrears').textContent    = fmtK(s.total_arrears);
        document.getElementById('kpiInArrears').textContent  = fmtN(s.loans_in_arrears) + ' in arrears';
        document.getElementById('kpiPar30').textContent      = fmtP(s.par30);
        document.getElementById('kpiPar90').textContent      = fmtP(s.par90);
        document.getElementById('kpiCR').textContent         = fmtP(s.collection_rate);
    }

    function destroyCharts(){
        Object.keys(charts).forEach(k => {
            try { charts[k].destroy(); } catch(e) {}
        });
        charts = {};
    }

    function renderCharts(data){
        destroyCharts();

        Chart.defaults.color = '#60745f';
        Chart.defaults.borderColor = '#d6e3d8';
        Chart.defaults.font.family = "'DM Sans',sans-serif";

        const trendLabels = data.trend.map(t => t.month_year);
        charts.trend = new Chart('chTrend', {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [
                    { label: '# Loans', data: data.trend.map(t => t.loan_count), backgroundColor: 'rgba(36,193,107,0.45)', borderColor: '#24C16B', borderWidth: 1, yAxisID: 'y1' },
                    { label: 'Principal (KM)', type: 'line', data: data.trend.map(t => t.principal_km), borderColor: '#f5a623', backgroundColor: 'rgba(245,166,35,0.10)', tension: 0.35, yAxisID: 'y2', fill: true }
                ]
            },
            options: {responsive:true,interaction:{mode:'index',intersect:false}, scales:{y1:{position:'left'},y2:{position:'right',grid:{drawOnChartArea:false}}}}
        });

        charts.par = new Chart('chPar', {
            type: 'doughnut',
            data: {
                labels: data.aging.map(a => a.bucket),
                datasets: [{
                    data: data.aging.map(a => a.outstanding_balance),
                    backgroundColor: ['#a8d8a6','#c7e7b6','#f5a623','#e8834a','#e84855']
                }]
            },
            options: {responsive:true,cutout:'60%'}
        });

        charts.aging = new Chart('chAging', {
            type: 'bar',
            data: {
                labels: data.aging.map(a => a.bucket),
                datasets: [
                    {label:'# Loans', data:data.aging.map(a => a.loan_count), backgroundColor:'rgba(36,193,107,0.45)', borderRadius:3, yAxisID:'y1'},
                    {label:'Arrears KM', data:data.aging.map(a => a.arrears_km), backgroundColor:'rgba(232,72,85,0.45)', borderRadius:3, yAxisID:'y2'}
                ]
            },
            options: {responsive:true, interaction:{mode:'index',intersect:false}, scales:{y1:{position:'left'}, y2:{position:'right',grid:{drawOnChartArea:false}}}}
        });

        const bnames = data.branches.map(b => b.branch_name);
        charts.branchBar = new Chart('chBranchBar', {
            type:'bar',
            data:{labels:bnames,datasets:[
                {label:'Outstanding (M)', data:data.branches.map(b => b.outstanding / 1000000), backgroundColor:'rgba(21,53,5,0.5)', borderRadius:3},
                {label:'Arrears (M)', data:data.branches.map(b => b.arrears / 1000000), backgroundColor:'rgba(245,166,35,0.5)', borderRadius:3}
            ]}, options:{responsive:true,interaction:{mode:'index',intersect:false}}
        });

        charts.branchPar = new Chart('chBranchPar', {
            type:'bar', data:{labels:bnames,datasets:[
                {label:'PAR30%', data:data.branches.map(b => b.par30), backgroundColor:'rgba(245,166,35,0.65)', borderRadius:3},
                {label:'PAR90%', data:data.branches.map(b => b.par90), backgroundColor:'rgba(232,72,85,0.65)', borderRadius:3}
            ]}, options:{responsive:true,interaction:{mode:'index',intersect:false}}
        });

        const pnames = data.products.map(p => p.product_name);
        charts.productBar = new Chart('chProductBar', {
            type:'bar',
            data:{labels:pnames,datasets:[
                {label:'Outstanding KM', data:data.products.map(p => p.outstanding_km), backgroundColor:'rgba(21,53,5,0.5)', borderRadius:3},
                {label:'Arrears KM', data:data.products.map(p => p.arrears_km), backgroundColor:'rgba(232,72,85,0.5)', borderRadius:3}
            ]},
            options:{responsive:true,indexAxis:'y'}
        });

        charts.productPar = new Chart('chProductPar', {
            type:'bar',
            data:{labels:pnames,datasets:[{
                label:'PAR%',
                data:data.products.map(p => p.par_rate),
                backgroundColor:data.products.map(p => p.par_rate >= 20 ? 'rgba(232,72,85,0.75)' : p.par_rate >= 10 ? 'rgba(245,166,35,0.75)' : 'rgba(36,193,107,0.75)'),
                borderRadius:3
            }]},
            options:{responsive:true,indexAxis:'y',plugins:{legend:{display:false}}}
        });

        const top = data.officers.slice(0, 10);
        charts.offTop = new Chart('chOfficerTop', {
            type:'bar',
            data:{labels:top.map(o => o.officer_name), datasets:[{label:'Outstanding (M)', data:top.map(o => o.outstanding / 1000000), backgroundColor:'rgba(21,53,5,0.5)', borderRadius:3}]},
            options:{responsive:true,indexAxis:'y',plugins:{legend:{display:false}}}
        });

        charts.offPar = new Chart('chOfficerPar', {
            type:'bar',
            data:{labels:top.map(o => o.officer_name), datasets:[{label:'PAR%', data:top.map(o => o.par_rate), backgroundColor:top.map(o => o.par_rate >= 20 ? 'rgba(232,72,85,0.75)' : o.par_rate >= 10 ? 'rgba(245,166,35,0.75)' : 'rgba(36,193,107,0.75)'), borderRadius:3}]},
            options:{responsive:true,indexAxis:'y',plugins:{legend:{display:false}}}
        });
    }

    function renderTables(data){
        document.getElementById('txnBody').innerHTML = data.loans.slice(0, 1500).map(l => `
            <tr>
              <td class="pd-mono">${l.loan_number || '--'}</td>
              <td>${l.customer_name || '--'}</td>
              <td>${l.product_name || '--'}</td>
              <td>${l.branch_name || '--'}</td>
              <td>${l.officer_name || '--'}</td>
              <td class="pd-mono">${fmtM(l.principal)}</td>
              <td class="pd-mono">${fmtM(l.outstanding)}</td>
              <td class="pd-mono" style="color:#f5a623">${fmtM(l.arrears)}</td>
              <td class="pd-mono">${l.days_arrears || 0}</td>
              <td class="pd-mono">${fmtP(l.collection_rate)}</td>
              <td class="pd-mono">${Number(l.interest_rate || 0).toFixed(2)}%</td>
              <td>${l.period || '--'}</td>
              <td><span class="pd-badge pd-info">${l.loan_status || '--'}</span></td>
            </tr>
        `).join('');

        document.getElementById('branchBody').innerHTML = data.branches.map(b => `
            <tr>
              <td>${b.branch_name}</td>
              <td class="pd-mono">${fmtN(b.loans_count)}</td>
              <td class="pd-mono">${fmtM(b.outstanding)}</td>
              <td class="pd-mono" style="color:#f5a623">${fmtM(b.arrears)}</td>
              <td><span class="pd-badge ${b.par30>=20?'pd-danger':b.par30>=10?'pd-warn':'pd-ok'}">${fmtP(b.par30)}</span></td>
              <td><span class="pd-badge ${b.par90>=20?'pd-danger':b.par90>=10?'pd-warn':'pd-ok'}">${fmtP(b.par90)}</span></td>
              <td class="pd-mono">${fmtP(b.collection_rate)}</td>
            </tr>
        `).join('');

        document.getElementById('productBody').innerHTML = data.products.map(p => `
            <tr>
              <td>${p.product_name}</td>
              <td class="pd-mono">${fmtN(p.loans_count)}</td>
              <td class="pd-mono">${Number(p.outstanding_km || 0).toFixed(2)}</td>
              <td class="pd-mono" style="color:#f5a623">${Number(p.arrears_km || 0).toFixed(2)}</td>
              <td><span class="pd-badge ${p.par_rate>=20?'pd-danger':p.par_rate>=10?'pd-warn':'pd-ok'}">${fmtP(p.par_rate)}</span></td>
            </tr>
        `).join('');

        document.getElementById('officerBody').innerHTML = data.officers.map(o => `
            <tr>
              <td>${o.officer_name}</td>
              <td class="pd-mono">${fmtN(o.loans_count)}</td>
              <td class="pd-mono">${fmtM(o.outstanding)}</td>
              <td class="pd-mono" style="color:#f5a623">${fmtM(o.arrears)}</td>
              <td><span class="pd-badge ${o.par_rate>=20?'pd-danger':o.par_rate>=10?'pd-warn':'pd-ok'}">${fmtP(o.par_rate)}</span></td>
              <td class="pd-mono">${fmtP(o.collection_rate)}</td>
              <td>${riskBadge(o.par_rate)}</td>
            </tr>
        `).join('');
    }

    async function loadDashboard(){
        try {
            const res = await fetch(nodeBase + '/portfolio-dashboard/data', { cache: 'no-store' });
            if (res.status === 202) {
                document.getElementById('pdAsAt').textContent = 'No snapshot yet';
                return;
            }
            if (!res.ok) throw new Error('Failed to load dashboard data');
            const data = await res.json();
            renderKpis(data.snapshot);
            renderCharts(data);
            renderTables(data);
        } catch (err) {
            console.error(err);
            alert('Failed to load Portfolio Dashboard data from Node service. Check bulk_report server on port 4500.');
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

    document.getElementById('pdRefresh').addEventListener('click', loadDashboard);

    initTabs();
    loadDashboard();
    setInterval(loadDashboard, 5 * 60 * 1000);
})();
</script>
