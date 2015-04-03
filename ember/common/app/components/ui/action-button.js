import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

/**
  Button which sends an action when clicked.
 */
export default Ember.Component.extend({
  tagName: 'a',
  attributeBindings: ['href', 'title'],
  classNameBindings: ['className'],
  href: '#',
  layout: precompileTemplate('{{#if icon}}{{fa-icon icon class="fa-fw icon-glyph"}} {{/if}}<span class="label">{{label}}</span>'),

  label: '',
  icon: '',
  className: '',
  action: null,

  click: function(e) {
    e.preventDefault();
    var action = this.get('action');
    if (typeof action === 'string') {
      this.sendAction('action');
    } else if (typeof action === 'function') {
      action.call(this);
    }
  }
});
