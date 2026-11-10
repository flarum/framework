module.exports = require('@flarum/jest-config')({
  moduleNameMapper: {
    '^flarum/(.*)$': '<rootDir>/../../../framework/core/js/src/$1',
    // Deck's column contract and sources, which the Flagged posts column builds on.
    '^ext:flarum/deck/(.*)$': '<rootDir>/../../deck/js/src/$1',
  },
});
