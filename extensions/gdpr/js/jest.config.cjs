module.exports = require('@flarum/jest-config')({
  moduleNameMapper: {
    '^flarum/(.*)$': '<rootDir>/../../../framework/core/js/src/$1',
    // The extension imports its own modules from `src/...`.
    '^src/(.*)$': '<rootDir>/src/$1',
  },
});
