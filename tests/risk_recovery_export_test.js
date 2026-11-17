'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const XLSX = require('../bulk_report/public/vendor/xlsx.full.min.js');
const {generateRiskRecoveryReport,excelRuntime} = require('../bulk_report/riskRecoveryReport');
(async () => {
    const rows = [{loan_number:'0000123',customer_name:'=1+1 </script><script>bad()</script>',product_name:'Shared',branch_name:'Mzuzu',
        loan_status:'ACTIVE',days_in_arrears:31,risk_officer:'Tester',principal_balance:1000.25,interest_balance:50.25,
        amount_paid:222.50,collateral_total_value:3000,last_payment_date:'2026-10-22',write_off_recommendation:0}];
    const html = await generateRiskRecoveryReport({},1,{1:{}},{query(sql,params,callback){callback(null,rows);}});
    assert.ok(html.includes('Export Excel'));
    assert.ok(!html.includes('<script src='), 'Saved preview must export without external asset requests');
    const encoded = html.match(/id="risk-recovery-excel-data">([\s\S]*?)<\/script>/)[1];
    assert.ok(!encoded.includes('</script>'));
    const data = JSON.parse(encoded);
    let exported;
    const sandbox={window:{},document:{getElementById(){return {textContent:encoded};}},
        XLSX:{...XLSX,writeFile(book,name){exported={book,name};}}};
    vm.runInNewContext(fs.readFileSync(require.resolve('../bulk_report/public/reportExcelExport.js'),'utf8'),sandbox);
    sandbox.window.downloadReportExcel('data','Risk_Recovery_Report.xlsx','Risk Recovery');
    const reread=XLSX.read(XLSX.write(exported.book,{type:'buffer',bookType:'xlsx'}),{type:'buffer'});
    const sheet=reread.Sheets['Risk Recovery'];
    assert.equal(sheet.B2.v,'0000123');assert.equal(sheet.B2.t,'s');
    assert.equal(sheet.C2.v,rows[0].customer_name);assert.equal(sheet.C2.f,undefined);
    assert.equal(sheet.I2.v,1050.5);assert.equal(sheet.I2.t,'n');
    assert.equal(data.rows.length,1);assert.ok(reread.Sheets.Summary);
    // Execute the actual scripts embedded in the generated HTML, with no network
    // and no preloaded XLSX global. This reproduces the failed browser scenario.
    const browser={window:null,document:{getElementById(){return {textContent:encoded};}},console};
    browser.window=browser;browser.self=browser;browser.globalThis=browser;
    vm.createContext(browser);
    for(const script of html.matchAll(/<script>([\s\S]*?)<\/script>/g)) vm.runInContext(script[1],browser);
    assert.ok(browser.XLSX && browser.XLSX.utils,'Inline XLSX runtime did not initialize');
    let standalone;
    browser.XLSX.writeFile=(book,name)=>{standalone={book,name};};
    browser.downloadReportExcel('data','Risk_Recovery_Report.xlsx','Risk Recovery');
    assert.equal(standalone.name,'Risk_Recovery_Report.xlsx');
    assert.equal(standalone.book.Sheets['Risk Recovery'].B2.v,'0000123');
    let fallbackExport;
    const fallbackBrowser={window:null,document:{getElementById(){return {textContent:encoded};},createElement(){return {};},
        head:{appendChild(script){fallbackBrowser.XLSX={...XLSX,writeFile(book,name){fallbackExport={book,name};}};script.onload();}}}};
    fallbackBrowser.window=fallbackBrowser;
    vm.runInNewContext(excelRuntime(()=>{const error=new Error('Missing file');error.code='ENOENT';throw error;}),fallbackBrowser);
    await fallbackBrowser.downloadReportExcel('data','Fallback.xlsx','Risk Recovery');
    assert.equal(fallbackExport.name,'Fallback.xlsx');assert.equal(fallbackExport.book.Sheets['Risk Recovery'].I2.v,1050.5);
    const revenue=XLSX.read(fs.readFileSync(require.resolve('../.local/revenue-export-test.xlsx')),{type:'buffer'});
    const revenueRows=XLSX.utils.sheet_to_json(revenue.Sheets['Revenue Analysis'],{header:1});
    assert.equal(revenueRows[1][0],'Mzuzu');assert.equal(revenueRows[1][2],30);
    assert.equal(revenueRows[1][8],54);assert.equal(revenueRows[1][9],3);
    assert.equal(revenueRows[2][0],'TOTALS');assert.equal(revenueRows[2][8],54);
    const safe=XLSX.read(fs.readFileSync(require.resolve('../.local/revenue-text-test.xlsx')),{type:'buffer'}).Sheets['Safe Text'];
    assert.equal(safe.A2.v,'=1+1');assert.equal(safe.A2.f,undefined);assert.equal(safe.A3.v,'000123');
    assert.equal(safe.B2.t,'n');assert.equal(safe.B2.v,1234.56);
    const empty = await generateRiskRecoveryReport({},2,{2:{}},{query(sql,params,callback){callback(null,[]);}});
    const emptyData=JSON.parse(empty.match(/id="risk-recovery-excel-data">([\s\S]*?)<\/script>/)[1]);
    assert.equal(emptyData.rows.length,0);
    console.log('PASS: Risk Recovery Excel download, numeric cells, leading-zero IDs, safe text, summary sheet and PHP revenue XLSX values/totals.');
})().catch(error=>{console.error(error);process.exitCode=1;});
