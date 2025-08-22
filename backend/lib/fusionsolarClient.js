const axios = require('axios');
const axiosRetry = require('axios-retry');
const { HttpsProxyAgent } = require('https-proxy-agent');
const https = require('https');
const tough = require('tough-cookie');
const { wrapper } = require('axios-cookiejar-support');
const NodeCache = require('node-cache');

const FS_BASE = process.env.FS_BASE;
const FS_USER = process.env.FS_USER;
const FS_CODE = process.env.FS_CODE;
const MA_PROXY = process.env.MA_PROXY;
const REQUIRE_PROXY = process.env.NODE_ENV !== 'test';
const CACHE_TTL = parseInt(process.env.CACHE_TTL_SECONDS || '90', 10);

class FusionSolarClient {
  constructor(logger = console) {
    this.logger = logger;
    this.jar = new tough.CookieJar();
    if (!MA_PROXY && REQUIRE_PROXY) {
      this.logger.error('MA_PROXY not configured');
      this.proxyError = true;
    }
    const agent = MA_PROXY ? new HttpsProxyAgent(MA_PROXY) : new https.Agent();
    agent.timeout = 10000;
    this.client = wrapper(axios.create({
      baseURL: FS_BASE,
      jar: this.jar,
      withCredentials: true,
      httpsAgent: agent,
      timeout: 20000,
      transitional: { clarifyTimeoutError: true },
    }));
    axiosRetry(this.client, {
      retries: 1,
      retryDelay: () => 100 + Math.floor(Math.random() * 300),
      retryCondition: err => axiosRetry.isNetworkOrIdempotentRequestError(err) || err.response?.status >= 500,
      onRetry: (retryCount, err, reqConfig) => {
        this.logger.warn({ retryCount, err: err.message, url: reqConfig.url }, 'retrying request');
      },
    });
    this.cache = new NodeCache({ stdTTL: CACHE_TTL });
    this.loggedIn = false;
  }

  async login() {
    await this.client.post('/thirdData/login', {
      userName: FS_USER,
      systemCode: FS_CODE,
    });
    this.loggedIn = true;
  }

  async ensureLogin() {
    if (!this.loggedIn) {
      await this.login();
    }
  }

  async request(method, url, options = {}, cacheKey) {
    const start = Date.now();
    const cached = cacheKey ? this.cache.get(cacheKey) : undefined;
    if (cached) {
      this.logger.info({ url, cache: true, latency: 0, proxy: !!MA_PROXY }, 'fusionsolar request');
      return cached;
    }
    if (this.proxyError) {
      const err = new Error('proxy_misconfigured');
      err.code = 'PROXY_MISCONFIGURED';
      throw err;
    }
    await this.ensureLogin();
    const xsrf = (await this.jar.getCookies(FS_BASE)).find(c => c.key === 'XSRF-TOKEN');
    options.headers = Object.assign({}, options.headers, { 'XSRF-TOKEN': xsrf ? xsrf.value : undefined });
    try {
      const resp = await this.client.request({ method, url, ...options });
      const latency = Date.now() - start;
      if (cacheKey) this.cache.set(cacheKey, resp.data);
      this.logger.info({ url, cache: false, latency, proxy: !!MA_PROXY }, 'fusionsolar request');
      return resp.data;
    } catch (err) {
      if (err.response && (err.response.status === 401 || err.response.data?.msg === 'USER_MUST_RELOGIN')) {
        this.loggedIn = false;
        await this.ensureLogin();
        const resp = await this.client.request({ method, url, ...options });
        const latency = Date.now() - start;
        if (cacheKey) this.cache.set(cacheKey, resp.data);
        this.logger.info({ url, cache: false, latency, proxy: !!MA_PROXY }, 'fusionsolar request');
        return resp.data;
      }
      if (REQUIRE_PROXY && ['ECONNREFUSED', 'ENOTFOUND', 'ETIMEDOUT'].includes(err.code)) {
        err.code = 'PROXY_MISCONFIGURED';
      }
      throw err;
    }
  }

  stationList(pageNo = 1, pageSize = 20) {
    return this.request('POST', '/thirdData/stations', { data: { pageNo, pageSize } }, `stationList-${pageNo}-${pageSize}`);
  }

  stationOverview(code) {
    return this.request('POST', '/thirdData/getStationRealKpi', { data: { stationCodes: code } }, `overview-${code}`);
  }

  stationDevices(code) {
    return this.request('POST', '/thirdData/getDevList', { data: { stationCodes: code } }, `devices-${code}`);
  }

  stationAlarms(code, severity) {
    const now = Date.now();
    const dayAgo = now - 24 * 60 * 60 * 1000;
    const data = {
      stationCodes: code,
      severity,
      beginTime: dayAgo,
      endTime: now,
      language: 'en_US',
    };
    return this.request('POST', '/thirdData/getAlarmList', { data }, `alarms-${code}-${severity || 'all'}`);
  }
}

module.exports = FusionSolarClient;
