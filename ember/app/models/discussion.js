import Ember from 'ember';
import DS from 'ember-data';

export default DS.Model.extend({
  title: DS.attr('string'),
  slug: Ember.computed('title', function() {
    return this.get('title').toLowerCase().replace(/[^a-z0-9]/gi, '-').replace(/-+/g, '-');
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
  posts: DS.attr('string'),
  postIds: Ember.computed('posts', function() {
    var posts = this.get('posts') || '';
    return posts.split(',');
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
