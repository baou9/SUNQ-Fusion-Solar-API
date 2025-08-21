process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const request = require('supertest');
const nock = require('nock');
const app = require('../server');

describe('GET /api/stations/:code/alarms', () => {
  afterEach(() => nock.cleanAll());

  it('translates alarm codes and filters by severity', async () => {
    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });
    nock(process.env.FS_BASE)
      .post('/thirdData/alarmList')
      .twice()
      .reply(200, { data: { list: [{ alarmCode: '1001' }, { alarmCode: '1002' }] } });

    const res = await request(app).get('/api/stations/1/alarms');
    expect(res.status).toBe(200);
    expect(res.body.list).toEqual([
      { code: '1001', message: 'Overvoltage', severity: 'major' },
      { code: '1002', message: 'Undervoltage', severity: 'minor' },
    ]);

    const res2 = await request(app).get('/api/stations/1/alarms?severity=major');
    expect(res2.status).toBe(200);
    expect(res2.body.list).toEqual([
      { code: '1001', message: 'Overvoltage', severity: 'major' },
    ]);
  });
});
