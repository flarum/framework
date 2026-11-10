module.exports = require('@flarum/jest-config')({
  moduleNameMapper: {
    '^flarum/(.*)$': '<rootDir>/../../../framework/core/js/src/$1',
    // frappe-charts renders into a real SVG and measures it; jsdom has no
    // layout. The stub records what the widget asked it to draw.
    '^frappe-charts$': '<rootDir>/tests/stubs/frappe-charts.ts',
  },
});
