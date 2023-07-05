const path = require('path');

module.exports = (options = {}) => ({
  testEnvironment: 'jsdom',
  extensionsToTreatAsEsm: ['.ts', '.tsx'],
  transform: {
    '^.+\\.[tj]sx?$': [
      'babel-jest',
      require('flarum-webpack-config/babel.config.cjs'),
    ],
    '^.+\\.tsx?$': [
      'ts-jest',
      {
        useESM: true,
      },
    ],
  },
  preset: 'ts-jest',
  setupFilesAfterEnv: [path.resolve(__dirname, 'setup-env.js')],
  moduleDirectories: ['node_modules', 'src'],
  ...options,
});
