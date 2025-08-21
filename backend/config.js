const dotenv = require('dotenv');
dotenv.config();

const requiredVars = ['FS_BASE', 'FS_USER', 'FS_CODE'];
for (const v of requiredVars) {
  if (!process.env[v]) {
    throw new Error(`Missing required environment variable ${v}`);
  }
}

function intFromEnv(name, defaultValue) {
  const value = process.env[name];
  return value ? parseInt(value, 10) : defaultValue;
}

module.exports = {
  FS_BASE: process.env.FS_BASE,
  FS_USER: process.env.FS_USER,
  FS_CODE: process.env.FS_CODE,
  MA_PROXY: process.env.MA_PROXY,
  LOG_LEVEL: process.env.LOG_LEVEL || 'info',
  FRONTEND_ORIGIN: process.env.FRONTEND_ORIGIN || '*',
  PORT: intFromEnv('PORT', 8081),
  CACHE_TTL_SECONDS: intFromEnv('CACHE_TTL_SECONDS', 90),
  GIT_SHA: process.env.GIT_SHA,
};
