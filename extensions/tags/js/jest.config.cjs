module.exports = require('@flarum/jest-config')({
  moduleNameMapper: {
    '^flarum/(.*)$': '<rootDir>/../../../framework/core/js/src/$1',
  },
});
