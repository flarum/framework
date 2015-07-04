import Post from 'flarum/models/post';
import DiscussionPage from 'flarum/components/discussion-page';
import EditComposer from 'flarum/components/edit-composer';
import ActionButton from 'flarum/components/action-button';
import Separator from 'flarum/components/separator';
import ItemList from 'flarum/utils/item-list';

export default function(app) {
  function editAction() {
    app.composer.load(new EditComposer({ post: this }));
    app.composer.show();
  }

  function hideAction() {
    this.save({ isHidden: true });
    this.pushAttributes({ hideTime: new Date(), hideUser: app.session.user() });
  }

  function restoreAction() {
    this.save({ isHidden: false });
    this.pushAttributes({ hideTime: null, hideUser: null });
  }

  function deleteAction() {
    this.delete();
    // this.discussion().pushAttributes({removedPosts: [this.id()]});
    if (app.current instanceof DiscussionPage) {
      app.current.stream.removePost(this.id());
    }
  }

  Post.prototype.userControls = function(context) {
    return new ItemList();
  };

  Post.prototype.moderationControls = function(context) {
    var items = new ItemList();

    if (this.contentType() === 'comment' && this.canEdit()) {
      if (this.isHidden()) {
        items.add('restore', ActionButton.component({ icon: 'reply', label: 'Restore', onclick: restoreAction.bind(this) }));
      } else {
        items.add('edit', ActionButton.component({ icon: 'pencil', label: 'Edit', onclick: editAction.bind(this) }));
      }
    }

    return items;
  };

  Post.prototype.destructiveControls = function(context) {
    var items = new ItemList();

    if (this.number() != 1) {
      if (this.contentType() === 'comment' && !this.isHidden() && this.canEdit()) {
        items.add('hide', ActionButton.component({ icon: 'times', label: 'Delete', onclick: hideAction.bind(this) }));
      } else if ((this.contentType() !== 'comment' || this.isHidden()) && this.canDelete()) {
        items.add('delete', ActionButton.component({ icon: 'times', label: 'Delete Forever', onclick: deleteAction.bind(this) }));
      }
    }

    return items;
  };

  Post.prototype.controls = function(context) {
    var items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach(section => {
      var controls = this[section+'Controls'](context).toArray();
      if (controls.length) {
        items.add(section, controls);
        items.add(section+'Separator', Separator.component());
      }
    });

    return items;
  }
};
