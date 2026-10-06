import express from 'express';
import morgan from 'morgan';
import { spawn } from 'child_process';
import path from 'path';
import fs from 'fs';
import chokidar from 'chokidar';
import http from 'http';
import { fileURLToPath } from 'url';

// Load dotenv if available, otherwise use process.env 
let config;
try {
  const { config: dotenvConfig } = await import('dotenv');
  config = dotenvConfig;
  config();
} catch (e) {
  console.warn('dotenv not installed, using default environment variables:', e.message);
}

// Get __dirname equivalent in ES modules
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
app.use(morgan('dev'));
app.use(express.json());
// Simple CORS for browser access from the PHP app
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET,POST,DELETE,OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.sendStatus(200);
  next();
});

// Config - adjust as needed
const ROOT = path.resolve(__dirname, '..');
const projectRoot = path.resolve(ROOT);
const backupsDir = path.join(projectRoot, 'dbexportbackup');
fs.mkdirSync(backupsDir, { recursive: true });
const phpIndexPath = path.join(projectRoot, 'index.php');

// Load DB credentials from CodeIgniter config as defaults
function loadCiDbConfig() {
  try {
    const ciDbPath = path.join(projectRoot, 'application', 'config', 'database.php');
    const content = fs.readFileSync(ciDbPath, 'utf8');
    const get = (key) => {
      const m = content.match(new RegExp(`'${key}'\\s*=>\\s*'([^']*)'`));
      return m ? m[1] : '';
    };
    return {
      hostname: get('hostname') || 'localhost',
      username: get('username') || 'root',
      password: get('password') || '',
      database: get('database') || 'finfin'
    };
  } catch (e) {
    console.error('Failed to load CI DB config:', e.message);
    return { hostname: 'localhost', username: 'root', password: '', database: 'finfin' };
  }
}

// SSE clients
const sseClients = new Set();

function broadcast(event) {
  const data = `data: ${JSON.stringify(event)}\n\n`;
  for (const res of sseClients) {
    res.write(data);
  }
}

// Root endpoint for quick sanity check
app.get('/', (req, res) => {
  res.status(202).json({ message: 'Backup service is running.' });
});

app.get('/events', (req, res) => {
  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache');
  res.setHeader('Connection', 'keep-alive');
  res.flushHeaders();
  res.write('retry: 2000\n\n');
  sseClients.add(res);
  req.on('close', () => sseClients.delete(res));
});

// List existing backup files
app.get('/backups', (req, res) => {
  fs.readdir(backupsDir, (err, files) => {
    if (err) return res.status(500).json({ status: 'error', message: err.message });
    const list = files
      .filter((f) => f.endsWith('.sql') || f.endsWith('.zip'))
      .map((f) => {
        const full = path.join(backupsDir, f);
        const stat = fs.statSync(full);
        return { name: f, size: stat.size, mtime: stat.mtimeMs };
      })
      .sort((a, b) => b.mtime - a.mtime);
    res.json({ status: 'success', data: list });
  });
});

// Health check to verify configuration
app.get('/health', (req, res) => {
  const db = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    name: process.env.DB_NAME || 'finfin'
  };
  let mysqldumpPath = process.env.MYSQLDUMP_PATH || 'C:\\wamp64\\bin\\mysql\\mysql9.1.0\\bin\\mysqldump.exe';
  let dirWritable = false;
  try { fs.accessSync(backupsDir, fs.constants.W_OK); dirWritable = true; } catch {}
  const dumpExists = fs.existsSync(mysqldumpPath);
  res.json({ status: 'success', backupsDir, dirWritable, mysqldumpPath, dumpExists, db });
});

// Delete a backup file
app.delete('/backups/:name', (req, res) => {
  try {
    const name = req.params.name;
    if (!name || name.includes('..') || name.includes('/') || name.includes('\\')) {
      return res.status(400).json({ status: 'error', message: 'Invalid filename' });
    }
    const full = path.join(backupsDir, name);
    if (!fs.existsSync(full)) {
      return res.status(404).json({ status: 'error', message: 'File not found' });
    }
    fs.unlinkSync(full);
    broadcast({ type: 'backup:deleted', file: name });
    res.json({ status: 'success' });
  } catch (e) {
    res.status(500).json({ status: 'error', message: e.message });
  }
});

// Start backup by invoking mysqldump
app.post('/backup', (req, res) => {
  const ci = loadCiDbConfig();
  const dbHost = process.env.DB_HOST || ci.hostname || 'localhost';
  const dbUser = process.env.DB_USER || ci.username || 'root';
  const dbPass = process.env.DB_PASS || ci.password || '';
  const dbName = process.env.DB_NAME || ci.database || 'finfin';
  const timestamp = new Date().toISOString().replace(/[:T]/g, '-').slice(0, 19);
  const outFile = path.join(backupsDir, `finfin-${timestamp}.sql`);

  // Ensure directory is writable
  try {
    fs.accessSync(backupsDir, fs.constants.W_OK);
  } catch (e) {
    console.error('Backup directory not writable:', e.message);
    return res.status(500).json({ status: 'error', message: 'Backup directory not writable' });
  }

  // Updated mysqldump path
  let mysqldumpPath = process.env.MYSQLDUMP_PATH || 'C:\\wamp64\\bin\\mysql\\mysql9.1.0\\bin\\mysqldump.exe';
  console.log('mysqldump path:', mysqldumpPath);

  const args = ['--host=' + dbHost, '--user=' + dbUser];
  if (dbPass) args.push('--password=' + dbPass.replace(/([\\"$`])/g, '\\$1')); // Escape special characters
  args.push('--routines', '--events', '--triggers', dbName);
  console.log('mysqldump args:', args);

  // Check if mysqldump exists
  if (!fs.existsSync(mysqldumpPath)) {
    console.error('mysqldump not found at:', mysqldumpPath);
    broadcast({ type: 'backup:log', message: `mysqldump not found at ${mysqldumpPath} — falling back to PHP backup.` });
    return fallbackPhpBackup(outFile, res);
  }

  broadcast({ type: 'backup:start', file: path.basename(outFile) });

  const dump = spawn(mysqldumpPath, args, { shell: true });
  const writeStream = fs.createWriteStream(outFile);
  dump.stdout.pipe(writeStream);

  let stderrBuf = '';
  dump.stderr.on('data', (d) => {
    const msg = d.toString();
    stderrBuf += msg;
    broadcast({ type: 'backup:log', message: msg });
  });

  let bytesWritten = 0;
  writeStream.on('drain', () => {
    try {
      const stat = fs.statSync(outFile);
      if (stat.size - bytesWritten > 512 * 1024) {
        bytesWritten = stat.size;
        broadcast({ type: 'backup:progress', bytes: stat.size, file: path.basename(outFile) });
      }
    } catch {}
  });

  dump.on('close', (code) => {
    if (code === 0) {
      broadcast({ type: 'backup:complete', file: path.basename(outFile) });
      res.json({ status: 'success', file: path.basename(outFile) });
    } else {
      console.error('mysqldump stderr:', stderrBuf);
      const detail = (stderrBuf || '').split('\n').slice(-8).join('\n');
      const message = 'mysqldump failed. Verify MYSQLDUMP_PATH and DB credentials.';
      broadcast({ type: 'backup:error', code, message, detail });
      res.status(500).json({ status: 'error', code, message, detail });
    }
  });
});

// Fallback: call CodeIgniter Backup/backupdb to produce a zip
function fallbackPhpBackup(outFileSqlPath, res) {
  try {
    const projectName = path.basename(projectRoot).replace(/\\/g, '');
    const urlPath = `/${projectName}/Backup/backupdb`;
    const targetName = `backup-php-${new Date().toISOString().replace(/[:T]/g, '-').slice(0, 19)}.zip`;
    const outZip = path.join(backupsDir, targetName);

    console.log('Attempting PHP fallback backup:', urlPath);

    const req = http.get({ host: 'localhost', port: 80, path: urlPath, timeout: 1000 * 60 * 5 }, (resp) => {
      if (resp.statusCode !== 200) {
        const message = `PHP backup endpoint returned ${resp.statusCode}`;
        console.error(message);
        broadcast({ type: 'backup:error', message });
        return res.status(500).json({ status: 'error', message });
      }
      const ws = fs.createWriteStream(outZip);
      resp.pipe(ws);
      ws.on('finish', () => {
        broadcast({ type: 'backup:complete', file: path.basename(outZip) });
        res.json({ status: 'success', file: path.basename(outZip) });
      });
    });
    req.on('error', (e) => {
      const message = 'Fallback PHP backup failed: ' + e.message;
      console.error(message);
      broadcast({ type: 'backup:error', message });
      res.status(500).json({ status: 'error', message });
    });
  } catch (e) {
    const message = 'Fallback PHP backup exception: ' + e.message;
    console.error(message);
    broadcast({ type: 'backup:error', message });
    res.status(500).json({ status: 'error', message });
  }
}

// Watch directory for new files
chokidar.watch(backupsDir, { ignoreInitial: true }).on('add', (file) => {
  broadcast({ type: 'backup:file', file: path.basename(file) });
});

const PORT = process.env.PORT || 5051;
app.listen(PORT, () => {
  console.log('Backup service running on http://localhost:' + PORT);
});
