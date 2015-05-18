import Discussion from 'flarum/models/discussion';
import DiscussionPage from 'flarum/components/discussion-page';
import ComposerReply from 'flarum/components/composer-reply';
import LoginModal from 'flarum/components/login-modal';
import ActionButton from 'flarum/components/action-button';
import Separator from 'flarum/components/separator';
import ItemList from 'flarum/utils/item-list';

export default function(app) {
  Discussion.prototype.replyAction = function(goToLast, forceRefresh) {
    if (app.session.user() && this.canReply()) {
      if (goToLast && app.current.discussion && app.current.discussion().id() === this.id()) {
        app.current.streamContent.goToLast();
      }
      var component = app.composer.component;
      if (!(component instanceof ComposerReply) || component.props.discussion !== this || component.props.user !== app.session.user() || forceRefresh) {
        component = new ComposerReply({
          user: app.session.user(),
          discussion: this
        });
        app.composer.load(component);
      }
      app.composer.show(goToLast);
      return component;
    } else if (!app.session.user()) {
      app.modal.show(new LoginModal({
        message: 'You must be logged in to do that.',
        callback: this.replyAction.bind(this)
      }));
    }
  }

  Discussion.prototype.deleteAction = function() {
    if (confirm('Are you sure you want to delete this discussion?')) {
      this.delete();
      if (app.cache.discussionList) {
        app.cache.discussionList.removeDiscussion(this);
      }
      if (app.current instanceof DiscussionPage && app.current.discussion().id() === this.id()) {
        app.history.back();
      }
    }
  }

  Discussion.prototype.renameAction = function() {
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

  Discussion.prototype.controls = function(context) {
    var items = new ItemList();

    if (context instanceof DiscussionPage) {
      items.add('reply', !app.session.user() || this.canReply()
        ? ActionButton.component({ icon: 'reply', label: app.session.user() ? 'Reply' : 'Log In to Reply', onclick: this.replyAction.bind(this, true) })
        : ActionButton.component({ icon: 'reply', label: 'Can\'t Reply', className: 'disabled', title: 'You don\'t have permission to reply to this discussion.' })
      );

      items.add('separator', Separator.component());
    }

    if (this.canRename()) {
      items.add('rename', ActionButton.component({ icon: 'pencil', label: 'Rename', onclick: this.renameAction.bind(this) }));
    }

    if (this.canDelete()) {
      items.add('delete', ActionButton.component({ icon: 'times', label: 'Delete', onclick: this.deleteAction.bind(this) }));
    }

    return items;
  }
};
