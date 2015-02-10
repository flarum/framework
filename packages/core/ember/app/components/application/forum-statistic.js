import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

export default Ember.Component.extend({
  layout: precompileTemplate('{{number}} {{label}}')
});
