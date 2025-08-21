let config;
try {
  config = require('./config');
} catch (err) {
  console.error(err.message);
  process.exit(1);
}

const express = require('express');
const cors = require('cors');
const pino = require('pino');
const pinoHttp = require('pino-http');
const FusionSolarClient = require('./lib/fusionsolarClient');

const app = express();
const logger = pino({ level: config.LOG_LEVEL });
app.use(pinoHttp({ logger }));
app.use(express.json());
app.use(cors({ origin: config.FRONTEND_ORIGIN }));

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
  if (config.MA_PROXY) {
    try {
      const u = new URL(config.MA_PROXY);
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
  res.json({ ok: true, version: config.GIT_SHA || 'dev', proxyReachable });
});

const port = config.PORT;
if (require.main === module) {
  app.listen(port, () => logger.info(`Backend listening on ${port}`));
}

module.exports = app;
