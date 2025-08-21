const { spawnSync } = require('child_process');
const path = require('path');

const serverPath = path.join(__dirname, '..', 'server.js');

const baseEnv = {
  ...process.env,
  FS_BASE: 'https://example.com',
  FS_USER: 'user',
  FS_CODE: 'code',
};

describe('server configuration', () => {
  ['FS_BASE', 'FS_USER', 'FS_CODE'].forEach((variable) => {
    test(`fails fast when ${variable} is missing`, () => {
      const env = { ...baseEnv };
      delete env[variable];
      const result = spawnSync('node', [serverPath], { env, encoding: 'utf8' });
      expect(result.status).toBe(1);
      expect(result.stderr).toContain(`Missing required environment variable ${variable}`);
    });
  });
});
