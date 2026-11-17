<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bulk historical penalties</title>
<style>body{font:16px Arial,sans-serif;max-width:1100px;margin:32px auto;padding:0 20px;color:#203040}button,select{padding:10px;margin:5px}table{border-collapse:collapse;width:100%;margin-top:20px}th,td{border:1px solid #ccd3da;padding:10px;text-align:left;overflow-wrap:anywhere}th{background:#eef2f6}#progress{white-space:pre-wrap}button:disabled{opacity:.5}</style>
</head><body>
<h1>Bulk historical penalties</h1>
<p>Preview all active loans, then apply the date repairs and historical penalties. Keep this page open until processing completes.</p>
<label>Missing payment dates <select id="source">
<option value="reference-date">Use account dates, then dates from payment references</option>
<option value="account-only">Use matching account dates only</option>
</select></label>
<p>Dates from payment references are inferred posting dates. The report identifies each inferred date. Conflicting dates and repayment totals remain listed for review.</p>
<p>Before applying, back up the database and pause repayments and the daily charge job. Valid date repairs are saved even if a loan's penalties need further review.</p>
<button id="preview">Preview all active loans</button>
<button id="apply" disabled>Apply repairs and historical penalties</button>
<button id="download" disabled>Download report</button>
<p id="progress" role="status" aria-live="polite">Ready to preview. Preview does not save changes.</p>
<table><thead><tr><th>Loan</th><th>Status</th><th>Date repairs</th><th>Historical opening penalty (MWK)</th><th>Review details</th></tr></thead><tbody id="results"></tbody></table>
<script>
'use strict';
const endpoint=<?= json_encode(site_url('Late_charges/bulk_history_batch'), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
let csrf=<?= json_encode($this->config->item('csrf_protection') ? array('name'=>$this->security->get_csrf_token_name(),'value'=>$this->security->get_csrf_hash()) : null, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const source=document.getElementById('source'),preview=document.getElementById('preview'),apply=document.getElementById('apply'),download=document.getElementById('download'),progress=document.getElementById('progress'),results=document.getElementById('results');
let report=[],previewSource=null,running=false;
source.addEventListener('change',()=>{previewSource=null;apply.disabled=true;});
async function run(mode){
    if(running || (mode==='apply' && previewSource!==source.value))return;
    running=true;preview.disabled=true;apply.disabled=true;source.disabled=true;download.disabled=true;
    const dateSource=source.value;report=[];results.replaceChildren();let cursor=0,processed=0,review=0,ready=0,repairs=0;
    try{
        let more=true;
        while(more){
            progress.textContent=(mode==='preview'?'Previewing':'Applying')+'… '+processed+' loans processed. Keep this page open.';
            const body=new URLSearchParams({mode:mode,date_source:dateSource,after_id:String(cursor)});
            if(csrf)body.set(csrf.name,csrf.value);
            const response=await fetch(endpoint,{method:'POST',credentials:'same-origin',body:body});
            if(!response.ok)throw new Error('Request failed ('+response.status+'). Sign in as SUPER ADMIN and reopen this page.');
            const data=await response.json();if(data.csrf)csrf=data.csrf;
            if(!data.success)throw new Error(data.error || 'Batch failed.');
            for(const item of data.items){
                report.push(item);const tr=document.createElement('tr');
                const details=[item.error || '',...(item.date_repairs || []).map(r=>JSON.stringify(r)),...(item.unresolved_dates || []).map(r=>JSON.stringify(r))].filter(Boolean).join('\n');
                for(const value of [item.loan_number,item.status,(item.date_repairs || []).length,item.opening_penalty===undefined?'—':Number(item.opening_penalty).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2}),details]){
                    const td=document.createElement('td');td.textContent=String(value);tr.appendChild(td);
                }results.appendChild(tr);
            }
            processed+=data.summary.processed;review+=data.summary.needs_review;ready+=data.summary.reconstructed;repairs+=data.summary.date_repairs;
            const next=data.summary.last_loan_id;more=data.has_more;
            if(more && next<=cursor)throw new Error('Processing did not advance. Reopen the page to retry.');
            cursor=next;
        }
        progress.textContent=(mode==='preview'?'Preview complete. No changes saved.':'Repair complete.')+' '+processed+' loans processed; '+ready+' '+(mode==='preview'?'ready':'reconstructed')+'; '+review+' need review; '+repairs+' date repairs.';
        previewSource=mode==='preview'?dateSource:null;
    }catch(error){previewSource=null;progress.textContent='Stopped: '+error.message+' Last completed loan ID: '+cursor+'. Download the report. Completed apply batches remain saved.';}
    finally{running=false;source.disabled=false;preview.disabled=false;apply.disabled=previewSource!==source.value;download.disabled=report.length===0;}
}
preview.addEventListener('click',()=>run('preview'));
apply.addEventListener('click',()=>run('apply'));
download.addEventListener('click',()=>{
    const url=URL.createObjectURL(new Blob([report.map(item=>JSON.stringify(item)).join('\n')+'\n'],{type:'application/x-ndjson'}));
    const link=document.createElement('a');link.href=url;link.download='historical-penalties.jsonl';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
});
</script></body></html>
