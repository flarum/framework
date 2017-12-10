var gulp = require('flarum-gulp');

gulp({
  modules: {
    'flarum/statistics': 'src/**/*.js'
  },
  files: [
    'node_modules/frappe-charts/dist/frappe-charts.min.iife.js'
  ]
});
