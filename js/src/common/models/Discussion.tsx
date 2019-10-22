import Model from '../Model';
import computed from '../utils/computed';
import ItemList from '../utils/ItemList';
import Badge from '../components/Badge';

import User from './User';
import Post from './Post';

export default class Discussion extends Model {
  title = Model.attribute('title') as () => string;
  slug = Model.attribute('slug') as () => string;

  createdAt = Model.attribute('createdAt', Model.transformDate) as () => Date;
  user = Model.hasOne('user') as () => User;
  firstPost = Model.hasOne('firstPost') as () => Post;

  lastPostedAt = Model.attribute('lastPostedAt', Model.transformDate) as () => Date;
  lastPostedUser = Model.hasOne('lastPostedUser') as () => User;
  lastPost = Model.hasOne('lastPost') as () => Post;
  lastPostNumber = Model.attribute('lastPostNumber') as () => number;

  commentCount = Model.attribute('commentCount') as () => number;
  replyCount = computed('commentCount', commentCount => Math.max(0, commentCount - 1)) as () => string;
  posts = Model.hasMany('posts') as () => Post[];
  mostRelevantPost = Model.hasOne('mostRelevantPost') as () => Post;

  lastReadAt = Model.attribute('lastReadAt', Model.transformDate) as () => Date;
  lastReadPostNumber = Model.attribute('lastReadPostNumber') as () => number;
  isUnread = computed('unreadCount', unreadCount => !!unreadCount) as () => boolean;
  isRead = computed('unreadCount', unreadCount => app.session.user && !unreadCount) as () => boolean;

  hiddenAt = Model.attribute('hiddenAt', Model.transformDate) as () => Date;
  hiddenUser = Model.hasOne('hiddenUser') as () => User;
  isHidden = computed('hiddenAt', hiddenAt => !!hiddenAt) as () => boolean;

  canReply = Model.attribute('canReply') as () => boolean;
  canRename = Model.attribute('canRename') as () => boolean;
  canHide = Model.attribute('canHide') as () => boolean;
  canDelete = Model.attribute('canDelete') as () => boolean;

  /**
   * Remove a post from the discussion's posts relationship.
   *
   * @param id The ID of the post to remove.
   */
  removePost(id: number) {
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
   */
  unreadCount(): number {
    const user = app.session.user;

    if (user && user.markedAllAsReadAt() < this.lastPostedAt()) {
      return Math.max(0, this.lastPostNumber() - (this.lastReadPostNumber() || 0));
    }

    return 0;
  }

  /**
   * Get the Badge components that apply to this discussion.
   */
  badges(): ItemList {
    const items = new ItemList();

    if (this.isHidden()) {
      items.add('hidden', <Badge type="hidden" icon="fas fa-trash" label={app.translator.trans('core.lib.badge.hidden_tooltip')}/>);
    }

    return items;
  }

  /**
   * Get a list of all of the post IDs in this discussion.
   */
  postIds(): number[] {
    const posts = this.data.relationships.posts;

    return posts ? posts.data.map(link => link.id) : [];
  }
}
