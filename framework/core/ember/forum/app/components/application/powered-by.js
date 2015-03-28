import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

export default Ember.Component.extend({
  layout: precompileTemplate('<a href="http://flarum.org" target="_blank">Powered by Flarum</a>')
});
