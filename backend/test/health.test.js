process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const request = require('supertest');
const nock = require('nock');
const app = require('../server');
const net = require('net');
const { EventEmitter } = require('events');

describe('GET /healthz', () => {
  afterEach(() => {
    nock.cleanAll();
    jest.restoreAllMocks();
    delete process.env.MA_PROXY;
  });

  it('reports healthy status', async () => {
    const res = await request(app).get('/healthz');
    expect(res.status).toBe(200);
    expect(res.body.ok).toBe(true);
    expect(res.body.proxyReachable).toBe(true);
  });

  it('reports proxy unreachable', async () => {
    process.env.MA_PROXY = 'http://127.0.0.1:1234';
    jest.spyOn(net, 'createConnection').mockImplementation(() => {
      const socket = new EventEmitter();
      socket.setTimeout = () => {};
      socket.destroy = () => {};
      process.nextTick(() => socket.emit('error', new Error('fail')));
      return socket;
    });
    const res = await request(app).get('/healthz');
    expect(res.status).toBe(200);
    expect(res.body.proxyReachable).toBe(false);
  });
});
