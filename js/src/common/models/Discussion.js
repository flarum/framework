import Model from '../Model';
import computed from '../utils/computed';
import ItemList from '../utils/ItemList';
import Badge from '../components/Badge';

export default class Discussion extends Model {}

Object.assign(Discussion.prototype, {
  title: Model.attribute('title'),
  slug: Model.attribute('slug'),

  createdAt: Model.attribute('createdAt', Model.transformDate),
  user: Model.hasOne('user'),
  firstPost: Model.hasOne('firstPost'),

  lastPostedAt: Model.attribute('lastPostedAt', Model.transformDate),
  lastPostedUser: Model.hasOne('lastPostedUser'),
  lastPost: Model.hasOne('lastPost'),
  lastPostNumber: Model.attribute('lastPostNumber'),

  commentCount: Model.attribute('commentCount'),
  replyCount: computed('commentCount', commentCount => Math.max(0, commentCount - 1)),
  posts: Model.hasMany('posts'),
  mostRelevantPost: Model.hasOne('mostRelevantPost'),

  lastReadAt: Model.attribute('lastReadAt', Model.transformDate),
  lastReadPostNumber: Model.attribute('lastReadPostNumber'),
  isUnread: computed('unreadCount', unreadCount => !!unreadCount),
  isRead: computed('unreadCount', unreadCount => app.session.user && !unreadCount),

  hiddenAt: Model.attribute('hiddenAt', Model.transformDate),
  hiddenUser: Model.hasOne('hiddenUser'),
  isHidden: computed('hiddenAt', hiddenAt => !!hiddenAt),

  canReply: Model.attribute('canReply'),
  canRename: Model.attribute('canRename'),
  canHide: Model.attribute('canHide'),
  canDelete: Model.attribute('canDelete'),

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
  },

  /**
   * Get the estimated number of unread posts in this discussion for the current
   * user.
   *
   * @return {Integer}
   * @public
   */
  unreadCount() {
    const user = app.session.user;

    if (user && user.markedAllAsReadAt() < this.lastPostedAt()) {
      return Math.max(0, this.lastPostNumber() - (this.lastReadPostNumber() || 0));
    }

    return 0;
  },

  /**
   * Get the Badge components that apply to this discussion.
   *
   * @return {ItemList}
   * @public
   */
  badges() {
    const items = new ItemList();

    if (this.isHidden()) {
      items.add('hidden', <Badge type="hidden" icon="fas fa-trash" label={app.translator.trans('core.lib.badge.hidden_tooltip')}/>);
    }

    return items;
  },

  /**
   * Get a list of all of the post IDs in this discussion.
   *
   * @return {Array}
   * @public
   */
  postIds() {
    const posts = this.data.relationships.posts;

    return posts ? posts.data.map(link => link.id) : [];
  }
});
