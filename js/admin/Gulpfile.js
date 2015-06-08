var gulp = require('flarum-gulp');

gulp({
  files: [
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
  outputFile: 'dist/app.js'
});
