var gulp = require('flarum-gulp');

gulp({
  files: [
    'node_modules/babel-core/external-helpers.js',
    '../bower_components/loader.js/loader.js',
    '../bower_components/mithril/mithril.js',
    '../bower_components/jquery/dist/jquery.js',
    '../bower_components/moment/moment.js',
    '../bower_components/bootstrap/dist/js/bootstrap.js',
    '../bower_components/spin.js/spin.js',
    '../bower_components/spin.js/jquery.spin.js'
  ],
  moduleFiles: [
    'src/**/*.js',
    '../lib/**/*.js'
  ],
  bootstrapFiles: [],
  modulePrefix: 'flarum',
  externalHelpers: true,
  outputFile: 'dist/app.js'
});
