var gulp = require('flarum-gulp');

var nodeDir = 'node_modules';
var bowerDir = '../bower_components';

gulp({
  files: [
    nodeDir + '/babel-core/external-helpers.js',

    bowerDir + '/es6-promise-polyfill/promise.js',
    bowerDir + '/es6-micro-loader/dist/system-polyfill.js',

    bowerDir + '/mithril/mithril.js',
    bowerDir + '/jquery/dist/jquery.js',
    bowerDir + '/jquery.hotkeys/jquery.hotkeys.js',
    bowerDir + '/color-thief/js/color-thief.js',
    bowerDir + '/moment/moment.js',

    bowerDir + '/bootstrap/js/affix.js',
    bowerDir + '/bootstrap/js/dropdown.js',
    bowerDir + '/bootstrap/js/modal.js',
    bowerDir + '/bootstrap/js/tooltip.js',
    bowerDir + '/bootstrap/js/transition.js',

    bowerDir + '/spin.js/spin.js',
    bowerDir + '/spin.js/jquery.spin.js',
    bowerDir + '/fastclick/lib/fastclick.js'
  ],
  moduleFiles: [
    'src/**/*.js',
    '../lib/**/*.js'
  ],
  modulePrefix: 'flarum',
  externalHelpers: true,
  outputFile: 'dist/app.js'
});
