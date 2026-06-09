const express = require('express');
const morgan = require('morgan');
const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

const app = express();
app.use(morgan('dev'));
app.use(express.json());
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.sendStatus(200);
  next();
});

const projectRoot = path.resolve(__dirname, '..');
const backupsDir = path.join(projectRoot, 'dbexportbackup');

const sseClients = new Set();
function broadcast(evt){
  const data = `data: ${JSON.stringify(evt)}\n\n`;
  for(const res of sseClients){ res.write(data); }
}

app.get('/events', (req, res) => {
  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache');
  res.setHeader('Connection', 'keep-alive');
  res.flushHeaders();
  res.write('retry: 2000\n\n');
  sseClients.add(res);
  req.on('close', () => sseClients.delete(res));
});

app.get('/backups', (req, res) => {
  fs.mkdirSync(backupsDir, { recursive: true });
  fs.readdir(backupsDir, (err, files) => {
    if (err) return res.status(500).json({ status: 'error', message: err.message });
    const list = files.filter(f => f.endsWith('.sql') || f.endsWith('.zip')).map(f => {
      const full = path.join(backupsDir, f);
      const stat = fs.statSync(full);
      return { name: f, size: stat.size, mtime: stat.mtimeMs };
    }).sort((a,b)=>b.mtime-a.mtime);
    res.json({ status: 'success', data: list });
  });
});

app.delete('/backups/:name', (req, res) => {
  try {
    const name = req.params.name;
    if(!name || name.includes('..') || name.includes('/') || name.includes('\\')){
      return res.status(400).json({ status: 'error', message: 'Invalid filename' });
    }
    const full = path.join(backupsDir, name);
    if(!fs.existsSync(full)){
      return res.status(404).json({ status: 'error', message: 'File not found' });
    }
    fs.unlinkSync(full);
    broadcast({ type: 'backup:deleted', file: name });
    res.json({ status: 'success' });
  } catch (e) {
    res.status(500).json({ status: 'error', message: e.message });
  }
});

app.post('/backup', (req, res) => {
  fs.mkdirSync(backupsDir, { recursive: true });
  const dbHost = process.env.DB_HOST || 'localhost';
  const dbUser = process.env.DB_USER || 'root';
  const dbPass = process.env.DB_PASS || '';
  const dbName = process.env.DB_NAME || 'finfin';
  const timestamp = new Date().toISOString().replace(/[:T]/g, '-').slice(0, 19);
  const outFile = path.join(backupsDir, `finfin-${timestamp}.sql`);

  const args = ['--host=' + dbHost, '--user=' + dbUser];
  if (dbPass) args.push('--password=' + dbPass);
  args.push('--routines', '--events', '--triggers', dbName);

  broadcast({ type: 'backup:start', file: path.basename(outFile) });

  const mysqldumpPath = process.env.MYSQLDUMP_PATH || 'mysqldump';
  const dump = spawn(mysqldumpPath, args, { shell: true });
  const writeStream = fs.createWriteStream(outFile);
  dump.stdout.pipe(writeStream);

  dump.stderr.on('data', (d) => broadcast({ type: 'backup:log', message: d.toString() }));
  writeStream.on('finish', () => broadcast({ type: 'backup:file', file: path.basename(outFile) }));

  dump.on('close', (code) => {
    if (code === 0) {
      broadcast({ type: 'backup:complete', file: path.basename(outFile) });
      res.json({ status: 'success', file: path.basename(outFile) });
    } else {
      broadcast({ type: 'backup:error', code });
      res.status(500).json({ status: 'error', code });
    }
  });
});

const PORT = process.env.BACKUP_PORT || 5051;
app.listen(PORT, () => console.log(`Backup service on http://localhost:${PORT}`));


