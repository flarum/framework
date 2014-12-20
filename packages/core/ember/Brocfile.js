/* global require, module */

var EmberApp = require('ember-cli/lib/broccoli/ember-app');

var app = new EmberApp();

app.import('vendor/bootstrap/dist/js/bootstrap.js');
app.import('vendor/spin.js/spin.js');
app.import('vendor/spin.js/jquery.spin.js');
app.import('vendor/moment/moment.js');
app.import('vendor/json-api.js');

app.import('vendor/font-awesome/fonts/fontawesome-webfont.eot');
app.import('vendor/font-awesome/fonts/fontawesome-webfont.svg');
app.import('vendor/font-awesome/fonts/fontawesome-webfont.ttf');
app.import('vendor/font-awesome/fonts/fontawesome-webfont.woff');
app.import('vendor/font-awesome/fonts/FontAwesome.otf');

module.exports = app.toTree();
