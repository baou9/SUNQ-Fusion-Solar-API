process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const request = require('supertest');
const nock = require('nock');
const app = require('../server');

describe('GET /api/stations/:code/overview', () => {
  afterEach(() => nock.cleanAll());

  it('returns station overview', async () => {
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });

    nock(process.env.FS_BASE)
      .post('/thirdData/stationRealKpi', { stationCodes: '1' })
      .reply(200, { data: { stationCode: '1', stationName: 'A' } });

    const res = await request(app).get('/api/stations/1/overview');
    expect(res.status).toBe(200);
    expect(res.body.data.stationName).toBe('A');
  });

  it('handles login failure', async () => {
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(500);

    const res = await request(app).get('/api/stations/1/overview');
    expect(res.status).toBe(502);
    expect(res.body.error).toBe('upstream_error');
  });
});
