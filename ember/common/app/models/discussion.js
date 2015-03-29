import Ember from 'ember';
import DS from 'ember-data';

import HasItemLists from '../mixins/has-item-lists';
import Subject from './subject';

export default Subject.extend(HasItemLists, {
  /**
    Define a "badges" item list. Example usage:
    ```
    populateBadges: function(items) {
      items.pushObjectWithTag(BadgeButton.extend({
        label: 'Sticky',
        icon: 'thumb-tack',
        className: 'badge-sticky',
        discussion: this,
        isHiddenInList: Ember.computed.not('discussion.sticky')
      }), 'sticky');
    }
    ```
   */
  itemLists: ['badges'],

  title: DS.attr('string'),
  slug: Ember.computed('title', function() {
    return this.get('title').toLowerCase().replace(/[^a-z0-9]/gi, '-').replace(/-+/g, '-').replace(/-$|^-/g, '');
  }),

  startTime: DS.attr('date'),
  startUser: DS.belongsTo('user'),
  startPost: DS.belongsTo('post'),

  lastTime: DS.attr('date'),
  lastUser: DS.belongsTo('user'),
  lastPost: DS.belongsTo('post'),
  lastPostNumber: DS.attr('number'),

  canReply: DS.attr('boolean'),
  canEdit: DS.attr('boolean'),
  canDelete: DS.attr('boolean'),

  commentsCount: DS.attr('number'),
  repliesCount: Ember.computed('commentsCount', function() {
    return Math.max(0, this.get('commentsCount') - 1);
  }),

  // The API returns the `posts` relationship as a list of IDs. To hydrate a
  // post-stream object, we're only interested in obtaining a list of IDs, so
  // we make it a string and then split it by comma. Instead, we'll put a
  // relationship on `loadedPosts`.
  // posts: DS.attr('string'),
  posts: DS.hasMany('post', {async: true}),
  postIds: Ember.computed(function() {
    var ids = [];
    this.get('data.posts').forEach(function(post) {
      ids.push(post.id);
    });
    return ids;
  }),
  loadedPosts: DS.hasMany('post'),
  relevantPosts: DS.hasMany('post'),
  addedPosts: DS.hasMany('post'),

  readTime: DS.attr('date'),
  readNumber: DS.attr('number'),
  unreadCount: Ember.computed('lastPostNumber', 'readNumber', 'session.user.readTime', function() {
    return this.get('session.user.readTime') < this.get('lastTime') ? Math.max(0, this.get('lastPostNumber') - (this.get('readNumber') || 0)) : 0;
  }),
  isUnread: Ember.computed.bool('unreadCount'),

  // Only used to save a new discussion
  content: DS.attr('string')
});
