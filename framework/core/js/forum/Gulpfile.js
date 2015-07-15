var gulp = require('flarum-gulp');

gulp({
  files: [
    'node_modules/babel-core/external-helpers.js',
    '../bower_components/es6-promise-polyfill/promise.js',
    '../bower_components/es6-micro-loader/dist/system-polyfill.js',

    '../bower_components/mithril/mithril.js',
    '../bower_components/jquery/dist/jquery.js',
    '../bower_components/jquery.hotkeys/jquery.hotkeys.js',
    '../bower_components/color-thief/js/color-thief.js',
    '../bower_components/moment/moment.js',

    '../bower_components/bootstrap/js/affix.js',
    '../bower_components/bootstrap/js/dropdown.js',
    '../bower_components/bootstrap/js/modal.js',
    '../bower_components/bootstrap/js/tooltip.js',
    '../bower_components/bootstrap/js/transition.js',

    '../bower_components/spin.js/spin.js',
    '../bower_components/spin.js/jquery.spin.js',
    '../bower_components/fastclick/lib/fastclick.js'
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
