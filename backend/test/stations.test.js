process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const request = require('supertest');
const nock = require('nock');
const app = require('../server');

describe('GET /api/stations', () => {
  afterEach(() => nock.cleanAll());

  it('returns list of stations', async () => {
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });

    nock(process.env.FS_BASE)
      .post('/thirdData/stations')
      .reply(200, { data: { list: [{ stationCode: '1', stationName: 'A' }] } });

    const res = await request(app).get('/api/stations');
    expect(res.status).toBe(200);
    expect(res.body.data.list[0].stationName).toBe('A');
  });
});
