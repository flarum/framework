import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

/**
  Component for the username/avatar in a post header.
 */
export default Ember.Component.extend({
  classNames: ['post-user'],
  layout: precompileTemplate('{{#if post.user}}<h3>{{#link-to "user" post.user}}{{user-avatar post.user}} {{user-name post.user}}{{/link-to}} {{ui/item-list items=post.user.badges class="badges"}}</h3>{{#if showCard}}{{user/user-card user=post.user class="user-card-popover fade" controlsButtonClass="btn btn-default btn-icon btn-sm btn-naked"}}{{/if}}{{else}}<h3>{{user-avatar post.user}} {{user-name post.user}}</h3>{{/if}}'),

  didInsertElement: function() {
    var component = this;
    var timeout;
    this.$().bind('mouseover', '> a, .user-card', function() {
      clearTimeout(timeout);
      timeout = setTimeout(function() {
        component.set('showCard', true);
        Ember.run.scheduleOnce('afterRender', function() {
          Ember.run.next(function() { component.$('.user-card').addClass('in'); });
        });
      }, 250);
    }).bind('mouseout', '> a, .user-card', function() {
      clearTimeout(timeout);
      timeout = setTimeout(function() {
        component.$('.user-card').removeClass('in').one('transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd', function() {
          component.set('showCard', false);
        });
      }, 250);
    });
  }
});
