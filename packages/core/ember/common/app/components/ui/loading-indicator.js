import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

/**
  Loading spinner.
 */
export default Ember.Component.extend({
  classNames: ['loading-indicator'],

  layout: precompileTemplate('&nbsp;'),
  size: 'small',

  didInsertElement: function() {
    var size = this.get('size');
    Ember.$.fn.spin.presets[size].zIndex = 'auto';
    this.$().spin(size);
  }
});
