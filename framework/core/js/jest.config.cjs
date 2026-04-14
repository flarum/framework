module.exports = require('@flarum/jest-config')({
  moduleNameMapper: {
    // webpack expose-loader syntax is meaningless in Jest — map to empty stubs
    '^expose-loader.*$': '<rootDir>/tests/__mocks__/emptyModule.js',
    // nanoid is ESM-only; stub it with a simple CJS id generator for tests
    '^nanoid$': '<rootDir>/tests/__mocks__/nanoid.js',
  },
});
