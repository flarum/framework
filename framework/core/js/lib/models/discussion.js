import Model from 'flarum/model';
import computed from 'flarum/utils/computed';
import ItemList from 'flarum/utils/item-list';

class Discussion extends Model {
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

Discussion.prototype.id = Model.prop('id');
Discussion.prototype.title = Model.prop('title');
Discussion.prototype.slug = computed('title', title => title.toLowerCase().replace(/[^a-z0-9]/gi, '-').replace(/-+/g, '-').replace(/-$|^-/g, '') || '-');

Discussion.prototype.startTime = Model.prop('startTime', Model.date);
Discussion.prototype.startUser = Model.one('startUser');
Discussion.prototype.startPost = Model.one('startPost');

Discussion.prototype.lastTime = Model.prop('lastTime', Model.date);
Discussion.prototype.lastUser = Model.one('lastUser');
Discussion.prototype.lastPost = Model.one('lastPost');
Discussion.prototype.lastPostNumber = Model.prop('lastPostNumber');

Discussion.prototype.canReply = Model.prop('canReply');
Discussion.prototype.canRename = Model.prop('canRename');
Discussion.prototype.canDelete = Model.prop('canDelete');

Discussion.prototype.commentsCount = Model.prop('commentsCount');
Discussion.prototype.repliesCount = computed('commentsCount', commentsCount => commentsCount - 1);

Discussion.prototype.posts = Model.many('posts');
Discussion.prototype.relevantPosts = Model.many('relevantPosts');
Discussion.prototype.addedPosts = Model.many('addedPosts');
Discussion.prototype.removedPosts = Model.prop('removedPosts');

Discussion.prototype.readTime = Model.prop('readTime', Model.date);
Discussion.prototype.readNumber = Model.prop('readNumber');

Discussion.prototype.isUnread = computed('unreadCount', unreadCount => !!unreadCount);

export default Discussion;
