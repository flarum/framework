import EditPostComposer from 'flarum/components/EditPostComposer';
import Button from 'flarum/components/Button';
import Separator from 'flarum/components/Separator';
import ItemList from 'flarum/utils/ItemList';

/**
 * The `PostControls` utility constructs a list of buttons for a post which
 * perform actions on it.
 */
export default {
  /**
   * Get a list of controls for a post.
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @public
   */
  controls(post, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach(section => {
      const controls = this[section + 'Controls'](post, context).toArray();
      if (controls.length) {
        controls.forEach(item => items.add(item.itemName, item));
        items.add(section + 'Separator', Separator.component());
      }
    });

    return items;
  },

  /**
   * Get controls for a post pertaining to the current user (e.g. report).
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  userControls() {
    return new ItemList();
  },

  /**
   * Get controls for a post pertaining to moderation (e.g. edit).
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  moderationControls(post) {
    const items = new ItemList();

    if (post.contentType() === 'comment' && post.canEdit()) {
      if (!post.isHidden()) {
        items.add('edit', Button.component({
          icon: 'pencil',
          children: app.trans('core.forum.post_controls_edit_button'),
          onclick: this.editAction.bind(post)
        }));
      }
    }

    return items;
  },

  /**
   * Get controls for a post that are destructive (e.g. delete).
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  destructiveControls(post) {
    const items = new ItemList();

    if (post.contentType() === 'comment' && !post.isHidden()) {
      if (post.canEdit()) {
        items.add('hide', Button.component({
          icon: 'trash-o',
          children: app.trans('core.forum.post_controls_delete_button'),
          onclick: this.hideAction.bind(post)
        }));
      }
    } else {
      if (post.canEdit()) {
        items.add('restore', Button.component({
          icon: 'reply',
          children: app.trans('core.forum.post_controls_restore_button'),
          onclick: this.restoreAction.bind(post)
        }));
      }
      if (post.canDelete() && post.number() !== 1) {
        items.add('delete', Button.component({
          icon: 'times',
          children: app.trans('core.forum.post_controls_delete_forever_button'),
          onclick: this.deleteAction.bind(post)
        }));
      }
    }

    return items;
  },

  /**
   * Open the composer to edit a post.
   */
  editAction() {
    app.composer.load(new EditPostComposer({ post: this }));
    app.composer.show();
  },

  /**
   * Hide a post.
   *
   * @return {Promise}
   */
  hideAction() {
    this.pushAttributes({ hideTime: new Date(), hideUser: app.session.user });

    return this.save({ isHidden: true }).then(() => m.redraw());
  },

  /**
   * Restore a post.
   *
   * @return {Promise}
   */
  restoreAction() {
    this.pushAttributes({ hideTime: null, hideUser: null });

    return this.save({ isHidden: false }).then(() => m.redraw());
  },

  /**
   * Delete a post.
   *
   * @return {Promise}
   */
  deleteAction() {
    this.discussion().removePost(this.id());

    return this.delete();
  }
};
