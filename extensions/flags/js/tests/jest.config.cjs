const path = require('path');
const shared = require('@flarum/jest-config')();
const [transformer, babel] = shared.transform['^.+\\.[tj]sx?$'];

// Flags still has JSX-bearing .js sources in a CommonJS package. Reuse the
// project's Babel/JSDOM harness, but give the focused suite a consistent
// CommonJS transform instead of changing the extension's packaging for tests.
module.exports = {
  ...shared,
  moduleNameMapper: {
    ...shared.moduleNameMapper,
    '^ext:flarum/deck/(.*)$': '<rootDir>/../../deck/js/src/$1',
  },
  rootDir: path.resolve(__dirname, '..'),
  roots: [path.resolve(__dirname, '..'), shared.globals.__FLARUM_CORE_DIR__],
  testMatch: ['<rootDir>/tests/**/*.test.ts'],
  extensionsToTreatAsEsm: [],
  transform: {
    '^.+\\.[tj]sx?$': [
      transformer,
      {
        ...babel,
        presets: babel.presets.map((preset) => {
          const name = Array.isArray(preset) ? preset[0] : preset;
          return typeof name === 'string' && name.includes('preset-env')
            ? [name, { ...(Array.isArray(preset) ? preset[1] : {}), modules: 'commonjs' }]
            : preset;
        }),
      },
    ],
  },
};
