const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

// Reuse DB settings like reports (fallback to env if provided)
const DB_HOST = process.env.DB_HOST || 'localhost';
const DB_USER = process.env.DB_USER || 'root';
const DB_PASS = process.env.DB_PASS || '';
const DB_NAME = process.env.DB_NAME || 'finfin';

// Resolve output dir like reports style (project root/dbexportbackup)
const projectRoot = path.resolve(__dirname, '..');
const outDir = path.join(projectRoot, 'dbexportbackup');
fs.mkdirSync(outDir, { recursive: true });

// Timestamp naming (YYYYMMDD_HHmmss)
function ts() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return (
        d.getFullYear().toString() +
        pad(d.getMonth() + 1) +
        pad(d.getDate()) + '_' +
        pad(d.getHours()) +
        pad(d.getMinutes()) +
        pad(d.getSeconds())
    );
}

// Try to locate mysqldump on Windows WAMP if not provided
function findMysqldump() {
    if (process.env.MYSQLDUMP_PATH) return process.env.MYSQLDUMP_PATH;
    const candidates = [
        'C:/wamp64/bin/mysql/mysql8.0.33/bin/mysqldump.exe',
        'C:/wamp64/bin/mysql/mysql8.0.31/bin/mysqldump.exe',
        'C:/wamp64/bin/mysql/mysql8.0.30/bin/mysqldump.exe',
        'C:/wamp64/bin/mysql/mysql5.7.31/bin/mysqldump.exe',
        'mysqldump'
    ];
    for (const p of candidates) {
        try { if (fs.existsSync(p) || p === 'mysqldump') return p; } catch {}
    }
    return 'mysqldump';
}

async function main() {
    const dumpPath = findMysqldump();
    const filename = `${DB_NAME}_backup_${ts()}.sql`;
    const outFile = path.join(outDir, filename);

    const args = ['--host=' + DB_HOST, '--user=' + DB_USER];
    if (DB_PASS) args.push('--password=' + DB_PASS);
    args.push('--routines', '--events', '--triggers', DB_NAME);

    console.log('Starting backup:', { outFile, dumpPath });
    const dump = spawn(dumpPath, args, { shell: true });
    const ws = fs.createWriteStream(outFile);
    dump.stdout.pipe(ws);

    dump.stderr.on('data', (d) => process.stderr.write(d.toString()));

    await new Promise((resolve) => ws.on('finish', resolve));

    await new Promise((resolve) => dump.on('close', resolve));

    // Verify file size > 0
    try {
        const st = fs.statSync(outFile);
        if (st.size === 0) throw new Error('Dump produced empty file');
        console.log('Backup complete:', { file: filename, size: st.size });
    } catch (e) {
        console.error('Backup failed:', e.message);
        process.exitCode = 1;
    }
}

main().catch((e) => { console.error(e); process.exit(1); });


