'use strict';
const assert = require('node:assert/strict');
const { buildCrbFilters } = require('../bulk_report/crbFilters');
const paid = buildCrbFilters({loan_status: 'PAID_UP'});
assert.equal(paid.params.length, 0);
assert.throws(() => buildCrbFilters({loan_status: "CLOSED' OR 1=1"}));
assert.throws(() => buildCrbFilters({branch: '1 OR 1=1'}));
assert.throws(() => buildCrbFilters({date_from: '2026-02-30'}));
assert.throws(() => buildCrbFilters({date_from: '2026-12-01', date_to: '2026-01-01'}));
assert.deepEqual(buildCrbFilters({loan_status:'ACTIVE', product:'2', branch:'7', date_from:'2026-01-01', date_to:'2026-11-12'}).params,
    ['ACTIVE', '2', '7', '2026-01-01', '2026-11-12']);
if (process.argv.includes('--sql')) {
    process.stdout.write(JSON.stringify({paid, filtered:buildCrbFilters({loan_status:'PAID_UP',product:'2',branch:'7',date_from:'2026-01-01',date_to:'2026-11-12'})}));
} else console.log('PASS: CRB filters accept valid selections and reject invalid status, identifiers and dates.');
