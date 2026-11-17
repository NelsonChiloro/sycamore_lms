# Risk Recovery generation fix

Upload ALL six files to their matching paths, restart the existing bulk_report
Node service, then generate a fresh Risk Recovery report. Existing stuck requests
are not automatically resumed or relabeled. No production records were changed
by this session. No SQL migration or npm install is required.

Source findings:
1. The prior inline-export change read the vendor and handler files synchronously
   without a catch. A missing/permission-denied export file threw after querying
   the report, stopping generation. The XLSX asset URL had previously returned 404;
   that is evidence of a deployment issue, not proof of the latest job's exact
   server error (the Node service logs are authoritative).
2. The older reports schema has status ENUM('in progress','completed') and no
   error_message column. The existing catch attempted to save both 'failed' and
   error_message; that update can fail too, leaving an apparently running job.
3. The report list displayed all non-completed statuses as In Progress, including
   modern 'failed' statuses.

Corrections:
- Missing export assets are nonfatal. When present, they are embedded into the
  saved HTML as before. If unavailable, an embedded handler loads the project's
  existing SheetJS version from the browser's CDN only when Excel is requested.
  Uploading the included vendor asset remains preferred and enables offline
  standalone previews; generation does not require the CDN.
- The report SELECT has a 120-second query timeout.
- Initial progress 5% is saved immediately; the progress timer is always cleared.
- Report filenames include job ID to prevent same-second collisions.
- Modern schema failure recording is attempted normally. If unsupported, only
  existing columns are used: completed_time is set while status remains in
  progress. The updated report list recognizes that terminal-failure combination
  for Risk Recovery and displays Failed, with no invalid preview link.
- All actual errors are retained in the Node service log. If the database itself
  is unavailable, failure persistence may also be impossible; check service logs.

Validation passed:
- Actual route callback executed under tests: successful completion, query error,
  write error and completion-update error on modern and legacy schemas; correct
  status/progress and no leaked timers.
- Export with inline assets and with both local assets missing; numeric Excel
  cells, safe text, leading-zero identifiers, summary sheet and empty reports.
- PHP report-list render test: in-progress vs complete vs modern/legacy failure.
- Node/PHP syntax checks.

Remote generation was not run or repaired automatically. If a fresh request
still fails, provide the Node log entry beginning 'Risk Recovery Report generation
failed:' (redact database credentials if a log includes configuration). The log
identifies the real SQL, filesystem or connection error; the screenshot alone
cannot distinguish those causes.