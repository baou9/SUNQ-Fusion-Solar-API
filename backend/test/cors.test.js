process.env.CORS_ORIGINS = 'http://allowed.com';

const request = require('supertest');
const app = require('../server');

describe('CORS allowlist', () => {
  it('allows listed origins', async () => {
    const res = await request(app)
      .get('/healthz')
      .set('Origin', 'http://allowed.com');
    expect(res.status).toBe(200);
    expect(res.headers['access-control-allow-origin']).toBe('http://allowed.com');
  });

  it('rejects other origins', async () => {
    const res = await request(app)
      .get('/healthz')
      .set('Origin', 'http://evil.com');
    expect(res.status).toBe(403);
    expect(res.body.error).toBe('origin_not_allowed');
  });
});
