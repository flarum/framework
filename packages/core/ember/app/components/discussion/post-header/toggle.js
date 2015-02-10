import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

/**
  Component for the toggle button in a post header. Toggles the
  `parent.revealContent` property when clicked. Only displays if the supplied
  post is not hidden.
 */
export default Ember.Component.extend({
  tagName: 'li',
  classNameBindings: ['hidden'],
  layout: precompileTemplate('<a href="#" class="btn btn-default btn-more" {{action "toggle"}}>{{fa-icon "ellipsis-h"}}</a>'),

  hidden: Ember.computed.not('post.isHidden'),

  actions: {
    toggle: function() {
      this.toggleProperty('parent.revealContent');
    }
  }
});
