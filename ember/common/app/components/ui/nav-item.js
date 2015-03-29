import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

/**
  A list item which contains a navigation link. The list item's `active`
  property reflects whether or not the link is active.
 */
export default Ember.Component.extend({
  layout: precompileTemplate('{{#link-to routeName}}{{fa-icon icon}} {{label}} <span class="count">{{badge}}</span>{{/link-to}}'),
  tagName: 'li',
  classNameBindings: ['active'],

  icon: '',
  label: '',
  badge: '',
  routeName: '',

  active: Ember.computed('childViews.@each.active', function() {
    return !!this.get('childViews').anyBy('active');
  })
});
