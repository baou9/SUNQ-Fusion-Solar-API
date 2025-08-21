const request = require('supertest');

describe('proxy misconfiguration', () => {
  const originalEnv = { ...process.env };

  afterEach(() => {
    process.env = { ...originalEnv };
    jest.resetModules();
    jest.unmock('pino');
  });

  it('fails with clear error when MA_PROXY missing', async () => {
    process.env.NODE_ENV = 'production';
    delete process.env.MA_PROXY;
    process.env.FS_USER = 'u';
    process.env.FS_CODE = 'c';
    process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

    const logs = [];
    jest.doMock('pino', () => () => ({
      info: () => {},
      warn: () => {},
      error: (obj, msg) => logs.push({ obj, msg }),
    }));

    const app = require('../server');
    const res = await request(app).get('/api/stations');
    expect(res.status).toBe(500);
    expect(res.body.error).toBe('proxy_misconfigured');
    const log = logs.find(l => l.msg === 'stationList failed');
    expect(log.obj.err.code).toBe('PROXY_MISCONFIGURED');
  });
});

