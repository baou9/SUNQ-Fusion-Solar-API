require('dotenv').config();
const express = require('express');
const cors = require('cors');
const pino = require('pino');
const pinoHttp = require('pino-http');
const FusionSolarClient = require('./lib/fusionsolarClient');

const app = express();
const logger = pino({ level: process.env.LOG_LEVEL || 'info' });
app.use(pinoHttp({ logger }));
app.use(express.json());
const allowedOrigins = (process.env.CORS_ORIGINS || process.env.FRONTEND_ORIGIN || '')
  .split(',')
  .map(o => o.trim())
  .filter(Boolean);
app.use(cors({
  origin: (origin, callback) => {
    if (!origin || allowedOrigins.includes(origin)) {
      callback(null, true);
    } else {
      callback(new Error('Not allowed by CORS'));
    }
  },
}));

const client = new FusionSolarClient();

app.get('/api/stations', async (req, res) => {
  try {
    const pageNo = Number(req.query.pageNo || 1);
    const pageSize = Number(req.query.pageSize || 20);
    const data = await client.stationList(pageNo, pageSize);
    res.json(data);
  } catch (err) {
    logger.error({ err }, 'stationList failed');
    res.status(502).json({ error: 'upstream_error' });
  }
});

app.get('/api/stations/:code/overview', async (req, res) => {
  try {
    const data = await client.stationOverview(req.params.code);
    res.json(data);
  } catch (err) {
    logger.error({ err }, 'stationOverview failed');
    res.status(502).json({ error: 'upstream_error' });
  }
});

app.get('/api/stations/:code/devices', async (req, res) => {
  try {
    const data = await client.stationDevices(req.params.code);
    res.json(data);
  } catch (err) {
    logger.error({ err }, 'stationDevices failed');
    res.status(502).json({ error: 'upstream_error' });
  }
});

app.get('/api/stations/:code/alarms', async (req, res) => {
  try {
    const data = await client.stationAlarms(req.params.code, req.query.severity);
    res.json(data);
  } catch (err) {
    logger.error({ err }, 'stationAlarms failed');
    res.status(502).json({ error: 'upstream_error' });
  }
});

app.get('/healthz', async (req, res) => {
  const net = require('net');
  let proxyReachable = false;
  if (process.env.MA_PROXY) {
    try {
      const u = new URL(process.env.MA_PROXY);
      await new Promise((resolve, reject) => {
        const socket = net.createConnection(u.port, u.hostname);
        socket.setTimeout(2000);
        socket.on('connect', () => { proxyReachable = true; socket.destroy(); resolve(); });
        socket.on('timeout', () => { socket.destroy(); reject(new Error('timeout')); });
        socket.on('error', reject);
      });
    } catch (e) {
      proxyReachable = false;
    }
  } else {
    proxyReachable = true;
  }
  res.json({ ok: true, version: process.env.GIT_SHA || 'dev', proxyReachable });
});

app.use((err, req, res, next) => {
  if (err && err.message === 'Not allowed by CORS') {
    res.status(403).json({ error: 'origin_not_allowed' });
  } else {
    next(err);
  }
});

const port = process.env.PORT || 8081;
if (require.main === module) {
  app.listen(port, () => logger.info(`Backend listening on ${port}`));
}

module.exports = app;
