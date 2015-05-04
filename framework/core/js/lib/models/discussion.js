import Model from 'flarum/model';
import computed from 'flarum/utils/computed';
import ItemList from 'flarum/utils/item-list';
import DiscussionPage from 'flarum/components/discussion-page';
import ActionButton from 'flarum/components/action-button';
import Separator from 'flarum/components/separator';
import ComposerReply from 'flarum/components/composer-reply';
import LoginModal from 'flarum/components/login-modal';

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

  controls(context) {
    var items = new ItemList();

    if (context instanceof DiscussionPage) {
      items.add('reply', !app.session.user() || this.canReply()
        ? ActionButton.component({ icon: 'reply', label: app.session.user() ? 'Reply' : 'Log In to Reply', onclick: this.replyAction.bind(this) })
        : ActionButton.component({ icon: 'reply', label: 'Can\'t Reply', className: 'disabled', title: 'You don\'t have permission to reply to this discussion.' })
      );

      items.add('separator', Separator.component());
    }

    if (this.canEdit()) {
      items.add('rename', ActionButton.component({ icon: 'pencil', label: 'Rename', onclick: this.renameAction.bind(this) }));
    }

    if (this.canDelete()) {
      items.add('delete', ActionButton.component({ icon: 'times', label: 'Delete', onclick: this.deleteAction.bind(this) }));
    }

    return items;
  }

  replyAction() {
    if (app.session.user() && this.canReply()) {
      if (app.current.discussion && app.current.discussion().id() === this.id()) {
        app.current.streamContent.goToLast();
      }
      app.composer.load(new ComposerReply({
        user: app.session.user(),
        discussion: this
      }));
      app.composer.show();
    } else if (!app.session.user()) {
      app.modal.show(new LoginModal({
        message: 'You must be logged in to do that.',
        callback: this.replyAction.bind(this)
      }));
    }
  }

  deleteAction() {
    if (confirm('Are you sure you want to delete this discussion?')) {
      this.delete();
      if (app.cache.discussionList) {
        app.cache.discussionList.removeDiscussion(this);
      }
      if (app.current.discussion && app.current.discussion().id() === this.id()) {
        app.history.back();
      }
    }
  }

  renameAction() {
    var currentTitle = this.title();
    var title = prompt('Enter a new title for this discussion:', currentTitle);
    if (title && title !== currentTitle) {
      this.save({title}).then(discussion => {
        if (app.current instanceof DiscussionPage) {
          app.current.stream().sync();
        }
        m.redraw();
      });
    }
  }
}

Discussion.prototype.id = Model.prop('id');
Discussion.prototype.title = Model.prop('title');
Discussion.prototype.slug = computed('title', title => title.toLowerCase().replace(/[^a-z0-9]/gi, '-').replace(/-+/g, '-').replace(/-$|^-/g, ''));

Discussion.prototype.startTime = Model.prop('startTime', Model.date);
Discussion.prototype.startUser = Model.one('startUser');
Discussion.prototype.startPost = Model.one('startPost');

Discussion.prototype.lastTime = Model.prop('lastTime', Model.date);
Discussion.prototype.lastUser = Model.one('lastUser');
Discussion.prototype.lastPost = Model.one('lastPost');
Discussion.prototype.lastPostNumber = Model.prop('lastPostNumber');

Discussion.prototype.canReply = Model.prop('canReply');
Discussion.prototype.canEdit = Model.prop('canEdit');
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
