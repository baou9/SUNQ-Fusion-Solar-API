process.env.FS_USER = 'test';
process.env.FS_CODE = 'test';
process.env.FS_BASE = 'https://intl.fusionsolar.huawei.com';

const request = require('supertest');
const app = require('../server');

describe('GET /api/stations/:code/alarms', () => {
  it('returns 400 for invalid severity', async () => {
    const res = await request(app).get('/api/stations/1/alarms?severity=5');
    expect(res.status).toBe(400);
    expect(res.body.errors).toContain('severity must be an integer between 1 and 4');
  });
});

