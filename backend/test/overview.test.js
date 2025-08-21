process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const request = require('supertest');
const nock = require('nock');
const app = require('../server');

describe('GET /api/stations/:code/overview', () => {
  afterEach(() => nock.cleanAll());

  it('returns shaped overview with green metrics', async () => {
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });
    nock(process.env.FS_BASE)
      .post('/thirdData/stationRealKpi')
      .reply(200, { data: { realTimePower: 5, dayEnergy: 10, totalEnergy: 100 } });

    const res = await request(app).get('/api/stations/1/overview');
    expect(res.status).toBe(200);
    expect(res.body.currentPower).toBe(5);
    expect(res.body.todayEnergy).toBe(10);
    expect(res.body.totalEnergy).toBe(100);
    expect(res.body.co2AvoidedKg).toBeCloseTo(60);
    expect(res.body.treesEquivalent).toBeCloseTo(60 / 21);
    expect(res.body.homesPowered).toBeCloseTo(100 / 30);
  });
});
