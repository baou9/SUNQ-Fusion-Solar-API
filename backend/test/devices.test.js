process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const request = require('supertest');
const nock = require('nock');
const app = require('../server');

describe('GET /api/stations/:code/devices', () => {
  afterEach(() => nock.cleanAll());

  it('returns station devices', async () => {
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });
    nock(process.env.FS_BASE)
      .post('/thirdData/getDevList')
      .reply(200, { data: { list: [{ id: '1' }] } });

    const res = await request(app).get('/api/stations/1/devices');
    expect(res.status).toBe(200);
    expect(res.body.data.list[0].id).toBe('1');
  });
});
