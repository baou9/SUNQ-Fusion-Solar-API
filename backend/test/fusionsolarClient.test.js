process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const nock = require('nock');
const FusionSolarClient = require('../lib/fusionsolarClient');

describe('FusionSolarClient', () => {
  afterEach(() => nock.cleanAll());

  test('retries once on 5xx', async () => {
    const logs = [];
    const logger = {
      warn: (obj) => logs.push(obj),
      info: () => {},
    };
    const client = new FusionSolarClient(logger);
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });
    nock(process.env.FS_BASE)
      .post('/thirdData/stationList')
      .reply(500)
      .post('/thirdData/stationList')
      .reply(200, { data: { list: [] } });

    const data = await client.stationList();
    expect(data.data.list).toEqual([]);
    expect(logs[0].retryCount).toBe(1);
  });

  test('logs cache hits', async () => {
    const logs = [];
    const logger = {
      info: (obj) => logs.push(obj),
      warn: () => {},
    };
    const client = new FusionSolarClient(logger);
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });
    nock(process.env.FS_BASE)
      .post('/thirdData/stationRealKpi')
      .reply(200, { data: { currentPower: 1, todayEnergy: 2, totalEnergy: 3 } });

    await client.stationOverview('1');
    await client.stationOverview('1');

    const cacheHit = logs.find(l => l.cache === true);
    const miss = logs.find(l => l.cache === false);
    expect(cacheHit.latency).toBe(0);
    expect(miss.latency).toBeGreaterThan(0);
  });
});
