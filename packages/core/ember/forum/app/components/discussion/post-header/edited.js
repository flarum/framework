import Ember from 'ember';

import humanTime from 'flarum-forum/utils/human-time';

var precompileTemplate = Ember.Handlebars.compile;

/**
  Component for the edited pencil icon in a post header. Shows a tooltip on
  hover which details who edited the post and when.
 */
export default Ember.Component.extend({
  tagName: 'li',
  classNames: ['post-edited'],
  classNameBindings: ['hidden'],
  attributeBindings: ['title'],
  layout: precompileTemplate('{{fa-icon "pencil"}}'),

  title: Ember.computed('post.editTime', 'post.editUser', function() {
    return 'Edited by '+this.get('post.editUser.username')+' '+humanTime(this.get('post.editTime'));
  }),

  // In the context of an item list, this item will be hidden if the post
  // hasn't been edited, or if it's been hidden.
  hidden: Ember.computed('post.isEdited', 'post.isHidden', function() {
    return !this.get('post.isEdited') || this.get('post.isHidden');
  }),

  didInsertElement: function() {
    this.$().tooltip();
  },

  // Whenever the title changes, we need to tell the tooltip to update to
  // reflect the new value.
  updateTooltip: Ember.observer('title', function() {
    Ember.run.scheduleOnce('afterRender', this, function() {
      this.$().tooltip('fixTitle');
    });
  })
});
