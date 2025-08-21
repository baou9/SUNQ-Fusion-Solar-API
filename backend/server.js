require('dotenv').config();
const express = require('express');
const cors = require('cors');
const pino = require('pino');
const pinoHttp = require('pino-http');
const { randomUUID } = require('crypto');
const FusionSolarClient = require('./lib/fusionsolarClient');
const { greenMetrics } = require('./lib/greenMetrics');
const alarmCodes = require('./lib/alarmCodes');
const axios = require('axios');
const { HttpsProxyAgent } = require('https-proxy-agent');

const app = express();
const logger = pino({ level: process.env.LOG_LEVEL || 'info' });
app.use(pinoHttp({
  logger,
  genReqId: () => randomUUID(),
  customProps: req => ({ requestId: req.id }),
  redact: ['req.headers.authorization', 'req.headers.cookie'],
}));
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

const client = new FusionSolarClient(logger);

function handleErr(err, res, msg) {
  logger.error({ err }, msg);
  if (err.code === 'PROXY_MISCONFIGURED') {
    res.status(500).json({ error: 'proxy_misconfigured' });
  } else {
    res.status(502).json({ error: 'upstream_error' });
  }
}

app.get('/api/stations', async (req, res) => {
  try {
    const pageNo = Number(req.query.pageNo || 1);
    const pageSize = Number(req.query.pageSize || 20);
    const data = await client.stationList(pageNo, pageSize);
    res.json(data);
  } catch (err) {
    handleErr(err, res, 'stationList failed');
  }
});

app.get('/api/stations/:code/overview', async (req, res) => {
  try {
    const raw = await client.stationOverview(req.params.code);
    const d = raw.data || raw;
    const shaped = {
      currentPower: d.currentPower ?? d.realTimePower ?? d.power ?? 0,
      todayEnergy: d.todayEnergy ?? d.dayEnergy ?? d.day_power ?? 0,
      totalEnergy: d.totalEnergy ?? d.total_power ?? 0,
    };
    if (d.performanceRatio !== undefined) shaped.performanceRatio = d.performanceRatio;
    Object.assign(shaped, greenMetrics(shaped.totalEnergy));
    res.json(shaped);
  } catch (err) {
    handleErr(err, res, 'stationOverview failed');
  }
});

app.get('/api/stations/:code/devices', async (req, res) => {
  try {
    const data = await client.stationDevices(req.params.code);
    res.json(data);
  } catch (err) {
    handleErr(err, res, 'stationDevices failed');
  }
});

app.get('/api/stations/:code/alarms', async (req, res) => {
  try {
    const data = await client.stationAlarms(req.params.code, req.query.severity);
    const list = (data.data?.list || data.list || []).map(a => {
      const mapping = alarmCodes[a.alarmCode];
      return {
        code: a.alarmCode,
        message: mapping ? mapping.message : a.message,
        severity: mapping ? mapping.severity : a.severity,
      };
    });
    const severity = req.query.severity;
    const filtered = severity ? list.filter(a => a.severity === severity) : list;
    res.json({ list: filtered });
  } catch (err) {
    handleErr(err, res, 'stationAlarms failed');
  }
});

app.get('/healthz', async (req, res) => {
  let proxyReachable = false;
  if (process.env.MA_PROXY) {
    try {
      const agent = new HttpsProxyAgent(process.env.MA_PROXY);
      await axios.head('https://example.com', { httpsAgent: agent, timeout: 5000 });
      proxyReachable = true;
    } catch (e) {
      proxyReachable = false;
    }
  }
  res.json({ ok: true, build: process.env.BUILD_SHA || 'dev', proxyReachable });
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
