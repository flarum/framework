module.exports = require('@flarum/jest-config')({
  moduleNameMapper: {
    // webpack expose-loader syntax is meaningless in Jest — map to empty stubs
    '^expose-loader.*$': '<rootDir>/tests/__mocks__/emptyModule.mjs',
    // nanoid is ESM-only; stub it with a simple ESM id generator for tests
    '^nanoid$': '<rootDir>/tests/__mocks__/nanoid.mjs',
  },
});
