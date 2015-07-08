import Model from 'flarum/model';
import computed from 'flarum/utils/computed';
import ItemList from 'flarum/utils/item-list';

class Discussion extends Model {
  /**
   * Remove a post from the discussion's posts relationship.
   *
   * @param {int} id The ID of the post to remove.
   * @return {void}
   */
  removePost(id) {
    const relationships = this.data().relationships;
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

  unreadCount() {
    var user = app.session.user();
    if (user && user.readTime() < this.lastTime()) {
      return Math.max(0, this.lastPostNumber() - (this.readNumber() || 0))
    }
    return 0;
  }

  badges() {
    return new ItemList();
  }
}

Discussion.prototype.title = Model.attribute('title');
Discussion.prototype.slug = computed('title', title => title.toLowerCase().replace(/[^a-z0-9]/gi, '-').replace(/-+/g, '-').replace(/-$|^-/g, '') || '-');

Discussion.prototype.startTime = Model.attribute('startTime', Model.transformDate);
Discussion.prototype.startUser = Model.hasOne('startUser');
Discussion.prototype.startPost = Model.hasOne('startPost');

Discussion.prototype.lastTime = Model.attribute('lastTime', Model.transformDate);
Discussion.prototype.lastUser = Model.hasOne('lastUser');
Discussion.prototype.lastPost = Model.hasOne('lastPost');
Discussion.prototype.lastPostNumber = Model.attribute('lastPostNumber');

Discussion.prototype.canReply = Model.attribute('canReply');
Discussion.prototype.canRename = Model.attribute('canRename');
Discussion.prototype.canDelete = Model.attribute('canDelete');

Discussion.prototype.commentsCount = Model.attribute('commentsCount');
Discussion.prototype.repliesCount = computed('commentsCount', commentsCount => Math.max(0, commentsCount - 1));

Discussion.prototype.posts = Model.hasMany('posts');
Discussion.prototype.postIds = function() { return this.data().relationships.posts.data.map((link) => link.id); };
Discussion.prototype.relevantPosts = Model.hasMany('relevantPosts');
Discussion.prototype.addedPosts = Model.hasMany('addedPosts');
Discussion.prototype.removedPosts = Model.attribute('removedPosts');

Discussion.prototype.readTime = Model.attribute('readTime', Model.transformDate);
Discussion.prototype.readNumber = Model.attribute('readNumber');

Discussion.prototype.isUnread = computed('unreadCount', unreadCount => !!unreadCount);

export default Discussion;
