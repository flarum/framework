module.exports = require('@flarum/jest-config')({
  moduleNameMapper: {
    '^flarum/(.*)$': '<rootDir>/../../../framework/core/js/src/$1',
    // TagHero drags in legacy `.js` helpers the ESM transform can't load, and
    // nothing under test renders it.
    'components/TagHero$': '<rootDir>/tests/stubs/TagHero.ts',
  },
});
