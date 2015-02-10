/* global require, module */

var EmberApp = require('ember-cli/lib/broccoli/ember-app');

var app = new EmberApp({
	vendorFiles: {
		'handlebars.js': null
	}
});

app.import('bower_components/ember/ember-template-compiler.js');

app.import('bower_components/bootstrap/dist/js/bootstrap.js');
app.import('bower_components/spin.js/spin.js');
app.import('bower_components/spin.js/jquery.spin.js');
app.import('bower_components/moment/moment.js');
app.import('bower_components/jquery.hotkeys/jquery.hotkeys.js');

app.import('bower_components/font-awesome/fonts/fontawesome-webfont.eot');
app.import('bower_components/font-awesome/fonts/fontawesome-webfont.svg');
app.import('bower_components/font-awesome/fonts/fontawesome-webfont.ttf');
app.import('bower_components/font-awesome/fonts/fontawesome-webfont.woff');
app.import('bower_components/font-awesome/fonts/FontAwesome.otf');

module.exports = app.toTree();
