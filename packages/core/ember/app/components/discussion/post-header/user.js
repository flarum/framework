import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

/**
  Component for the username/avatar in a post header.
 */
export default Ember.Component.extend({
  tagName: 'h3',
  classNames: ['post-user'],
  layout: precompileTemplate('{{#if post.user}}{{#link-to "user" post.user}}{{user-avatar post.user}} {{user-name post.user}}{{/link-to}}{{else}}{{user-avatar post.user}} {{user-name post.user}}{{/if}}{{ui/item-list items=post.user.badges class="badges"}}')
});
