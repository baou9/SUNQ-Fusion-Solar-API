process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const request = require('supertest');
const nock = require('nock');
const app = require('../server');

describe('GET /api/stations/:code/alarms', () => {
  afterEach(() => nock.cleanAll());

  it('returns station alarms', async () => {
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });

    nock(process.env.FS_BASE)
      .post('/thirdData/alarmList', { stationCodes: '1', severity: '1' })
      .reply(200, { data: { list: [{ alarmId: 'a1' }] } });

    const res = await request(app).get('/api/stations/1/alarms?severity=1');
    expect(res.status).toBe(200);
    expect(res.body.data.list[0].alarmId).toBe('a1');
  });

  it('handles invalid parameters', async () => {
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });

    nock(process.env.FS_BASE)
      .post('/thirdData/alarmList', { stationCodes: '1', severity: 'bad' })
      .reply(400, { error: 'invalid' });

    const res = await request(app).get('/api/stations/1/alarms?severity=bad');
    expect(res.status).toBe(502);
    expect(res.body.error).toBe('upstream_error');
  });
});
