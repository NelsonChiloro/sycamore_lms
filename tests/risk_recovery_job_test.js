'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(require.resolve('../bulk_report/index.js'),'utf8');
const start=source.indexOf("app.post('/generate-report-risk-recovery'");
const end=source.indexOf('// Arrears Report endpoint',start);
assert.ok(start>=0 && end>start);
async function run(mode,legacy=false){
    let handler;const row={};const active=new Set();let files=0;const updates=[];
    const context={
        app:{post(route,callback){handler=callback;}},console:{log(){},error(){}},reportTrackers:{},db:{},
        moment:()=>({format:()=> '20261117_010000'}),reportPeriodFields:()=>({}),
        firstNonEmpty:(...values)=>values.find(value=>value!==null && value!==undefined && value!==''),
        getReportPaths:(name,stamp)=>({filePath:stamp,dbPath:`reports/${stamp}.html`}),
        setInterval(){const id=active.size+1;active.add(id);return id;},clearInterval(id){active.delete(id);},updatePercentageToDB(){},
        fs:{promises:{async writeFile(){if(mode==='write')throw new Error('Disk full');files++;}}},
        generateRiskRecoveryReport:async()=>{if(mode==='query')throw new Error('Query timed out');return '<html>OK</html>';},
        query:async(sql,params)=>{
            if(sql.startsWith('INSERT')){Object.assign(row,params[0]);return {insertId:45};}
            updates.push(sql);
            if(sql.includes('SET percentage')){row.percentage=params[0];return {};}
            if(sql.includes('SET completed_time')){row.completed_time=params[0];row.percentage=0;return {};}
            const values=params[0];
            if(values.status==='failed' && legacy)throw new Error('Unknown error_message column / invalid enum');
            if(values.status==='completed' && mode==='complete')throw new Error('Completion update failed');
            Object.assign(row,values);return {};
        }
    };
    vm.runInNewContext(source.slice(start,end),context);
    let accepted=false;
    await handler({body:{}},{status(code){assert.equal(code,202);accepted=true;return this;},json(){}});
    assert.ok(accepted);assert.equal(active.size,0);assert.equal(Object.keys(context.reportTrackers).length,0);
    assert.ok(updates.some(sql=>sql.includes('SET percentage')),'Initial progress not persisted');
    if(mode==='ok'){assert.equal(row.status,'completed');assert.equal(row.percentage,100);assert.equal(files,1);assert.ok(row.download_link.includes('_45'));}
    else if(legacy){assert.equal(row.status,'in progress');assert.ok(row.completed_time);assert.equal(row.percentage,0);}
    else {assert.equal(row.status,'failed');assert.ok(row.completed_time);}
}
(async()=>{
    await run('ok');
    for(const failure of ['query','write','complete']){await run(failure);await run(failure,true);}
    console.log('PASS: actual Risk Recovery route completes, persists initial progress, handles query/write/completion errors on modern and legacy schemas, and always clears timers.');
})().catch(error=>{console.error(error);process.exitCode=1;});
