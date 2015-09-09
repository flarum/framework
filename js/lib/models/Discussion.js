import Model from 'flarum/Model';
import mixin from 'flarum/utils/mixin';
import computed from 'flarum/utils/computed';
import ItemList from 'flarum/utils/ItemList';
import { slug } from 'flarum/utils/string';

export default class Discussion extends mixin(Model, {
  title: Model.attribute('title'),
  slug: computed('title', slug),

  startTime: Model.attribute('startTime', Model.transformDate),
  startUser: Model.hasOne('startUser'),
  startPost: Model.hasOne('startPost'),

  lastTime: Model.attribute('lastTime', Model.transformDate),
  lastUser: Model.hasOne('lastUser'),
  lastPost: Model.hasOne('lastPost'),
  lastPostNumber: Model.attribute('lastPostNumber'),

  commentsCount: Model.attribute('commentsCount'),
  repliesCount: computed('commentsCount', commentsCount => Math.max(0, commentsCount - 1)),
  posts: Model.hasMany('posts'),
  relevantPosts: Model.hasMany('relevantPosts'),

  readTime: Model.attribute('readTime', Model.transformDate),
  readNumber: Model.attribute('readNumber'),
  isUnread: computed('unreadCount', unreadCount => !!unreadCount),
  isRead: computed('unreadCount', unreadCount => app.session.user && !unreadCount),

  canReply: Model.attribute('canReply'),
  canRename: Model.attribute('canRename'),
  canDelete: Model.attribute('canDelete')
}) {
  /**
   * Remove a post from the discussion's posts relationship.
   *
   * @param {Integer} id The ID of the post to remove.
   * @public
   */
  removePost(id) {
    const relationships = this.data.relationships;
    const posts = relationships && relationships.posts;

    if (posts) {
      posts.data.some((data, i) => {
        if (id === data.id) {
          posts.data.splice(i, 1);
          return true;
        }
      });
    }
  }

  /**
   * Get the estimated number of unread posts in this discussion for the current
   * user.
   *
   * @return {Integer}
   * @public
   */
  unreadCount() {
    const user = app.session.user;

    if (user && user.readTime() < this.lastTime()) {
      return Math.max(0, this.lastPostNumber() - (this.readNumber() || 0));
    }

    return 0;
  }

  /**
   * Get the Badge components that apply to this discussion.
   *
   * @return {ItemList}
   * @public
   */
  badges() {
    return new ItemList();
  }

  /**
   * Get a list of all of the post IDs in this discussion.
   *
   * @return {Array}
   * @public
   */
  postIds() {
    return this.data.relationships.posts.data.map(link => link.id);
  }
}
