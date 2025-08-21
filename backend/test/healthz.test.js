const request = require('supertest');
const app = require('../server');

describe('GET /healthz', () => {
  it('returns ok', async () => {
    const res = await request(app).get('/healthz');
    expect(res.status).toBe(200);
    expect(res.body.ok).toBe(true);
    expect(typeof res.body.proxyReachable).toBe('boolean');
    expect(res.body.build).toBe('dev');
  });
});
