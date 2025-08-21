process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const nock = require('nock');
const FusionSolarClient = require('../lib/fusionsolarClient');

describe('FusionSolarClient login locking', () => {
  afterEach(() => nock.cleanAll());

  it('reuses in-flight login for concurrent ensureLogin calls', async () => {
    const client = new FusionSolarClient();

    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .times(1)
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });

    await Promise.all([
      client.ensureLogin(),
      client.ensureLogin(),
      client.ensureLogin(),
    ]);

    expect(client.loggedIn).toBe(true);
    expect(nock.isDone()).toBe(true);
  });

  it('logs in once when multiple requests run concurrently', async () => {
    const client = new FusionSolarClient();

    nock(process.env.FS_BASE)
      .post('/thirdData/login')
      .times(1)
      .reply(200, { data: 'ok' }, { 'set-cookie': ['XSRF-TOKEN=abc'] });

    nock(process.env.FS_BASE)
      .post('/thirdData/stationList')
      .times(3)
      .reply(200, { data: { list: [] } });

    await Promise.all([
      client.stationList(),
      client.stationList(),
      client.stationList(),
    ]);

    expect(nock.isDone()).toBe(true);
  });
});
