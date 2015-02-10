import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

/**
  Component for the username/avatar in a post header.
 */
export default Ember.Component.extend({
  tagName: 'h3',
  classNames: ['post-user'],
  layout: precompileTemplate('{{#link-to "user" post.user}}{{user-avatar post.user}} {{post.user.username}}{{/link-to}}')
});
