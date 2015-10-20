import DiscussionPage from 'flarum/components/DiscussionPage';
import ReplyComposer from 'flarum/components/ReplyComposer';
import LogInModal from 'flarum/components/LogInModal';
import Button from 'flarum/components/Button';
import Separator from 'flarum/components/Separator';
import ItemList from 'flarum/utils/ItemList';
import extractText from 'flarum/utils/extractText';

/**
 * The `DiscussionControls` utility constructs a list of buttons for a
 * discussion which perform actions on it.
 */
export default {
  /**
   * Get a list of controls for a discussion.
   *
   * @param {Discussion} discussion
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @public
   */
  controls(discussion, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach(section => {
      const controls = this[section + 'Controls'](discussion, context).toArray();
      if (controls.length) {
        controls.forEach(item => items.add(item.itemName, item));
        items.add(section + 'Separator', Separator.component());
      }
    });

    return items;
  },

  /**
   * Get controls for a discussion pertaining to the current user (e.g. reply,
   * follow).
   *
   * @param {Discussion} discussion
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  userControls(discussion, context) {
    const items = new ItemList();

    // Only add a reply control if this is the discussion's controls dropdown
    // for the discussion page itself. We don't want it to show up for
    // discussions in the discussion list, etc.
    if (context instanceof DiscussionPage) {
      items.add('reply',
        !app.session.user || discussion.canReply()
          ? Button.component({
            icon: 'reply',
            children: app.translator.trans(app.session.user ? 'core.forum.discussion_controls.reply_button' : 'core.forum.discussion_controls.log_in_to_reply_button'),
            onclick: this.replyAction.bind(discussion, true, false)
          })
          : Button.component({
            icon: 'reply',
            children: app.translator.trans('core.forum.discussion_controls.cannot_reply_button'),
            className: 'disabled',
            title: app.translator.trans('core.forum.discussion_controls.cannot_reply_text')
          })
      );
    }

    return items;
  },

  /**
   * Get controls for a discussion pertaining to moderation (e.g. rename, lock).
   *
   * @param {Discussion} discussion
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  moderationControls(discussion) {
    const items = new ItemList();

    if (discussion.canRename()) {
      items.add('rename', Button.component({
        icon: 'pencil',
        children: app.translator.trans('core.forum.discussion_controls.rename_button'),
        onclick: this.renameAction.bind(discussion)
      }));
    }

    return items;
  },

  /**
   * Get controls for a discussion which are destructive (e.g. delete).
   *
   * @param {Discussion} discussion
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  destructiveControls(discussion) {
    const items = new ItemList();

    if (!discussion.isHidden()) {
      if (discussion.canHide()) {
        items.add('hide', Button.component({
          icon: 'trash-o',
          children: app.translator.trans('core.forum.discussion_controls.delete_button'),
          onclick: this.hideAction.bind(discussion)
        }));
      }
    } else if (discussion.canDelete()) {
      items.add('restore', Button.component({
        icon: 'reply',
        children: app.translator.trans('core.forum.discussion_controls.restore_button'),
        onclick: this.restoreAction.bind(discussion),
        disabled: discussion.commentsCount() === 0
      }));

      items.add('delete', Button.component({
        icon: 'times',
        children: app.translator.trans('core.forum.discussion_controls.delete_forever_button'),
        onclick: this.deleteAction.bind(discussion)
      }));
    }

    return items;
  },

  /**
   * Open the reply composer for the discussion. A promise will be returned,
   * which resolves when the composer opens successfully. If the user is not
   * logged in, they will be prompted and then the reply composer will open (and
   * the promise will resolve) after they do. If they don't have permission to
   * reply, the promise will be rejected.
   *
   * @param {Boolean} goToLast Whether or not to scroll down to the last post if
   *     the discussion is being viewed.
   * @param {Boolean} forceRefresh Whether or not to force a reload of the
   *     composer component, even if it is already open for this discussion.
   * @return {Promise}
   */
  replyAction(goToLast, forceRefresh) {
    const deferred = m.deferred();

    // Define a function that will check the user's permission to reply, and
    // either open the reply composer for this discussion and resolve the
    // promise, or reject it.
    const reply = () => {
      if (this.canReply()) {
        if (goToLast && app.viewingDiscussion(this)) {
          app.current.stream.goToLast();
        }

        let component = app.composer.component;
        if (!app.composingReplyTo(this) || forceRefresh) {
          component = new ReplyComposer({
            user: app.session.user,
            discussion: this
          });
          app.composer.load(component);
        }
        app.composer.show();

        deferred.resolve(component);
      } else {
        deferred.reject();
      }
    };

    // If the user is logged in, then we can run that function right away. But
    // if they're not, we'll prompt them to log in and then run the function
    // after the discussion has reloaded.
    if (app.session.user) {
      reply();
    } else {
      app.modal.show(
        new LogInModal({
          onlogin: () => app.current.one('loaded', reply)
        })
      );
    }

    return deferred.promise;
  },

  /**
   * Hide a discussion.
   *
   * @return {Promise}
   */
  hideAction() {
    this.pushAttributes({ hideTime: new Date(), hideUser: app.session.user });

    return this.save({ isHidden: true });
  },

  /**
   * Restore a discussion.
   *
   * @return {Promise}
   */
  restoreAction() {
    this.pushAttributes({ hideTime: null, hideUser: null });

    return this.save({ isHidden: false });
  },

  /**
   * Delete the discussion after confirming with the user.
   *
   * @return {Promise}
   */
  deleteAction() {
    if (confirm(extractText(app.translator.trans('core.forum.discussion_controls.delete_confirmation')))) {
      // If there is a discussion list in the cache, remove this discussion.
      if (app.cache.discussionList) {
        app.cache.discussionList.removeDiscussion(this);
      }

      // If we're currently viewing the discussion that was deleted, go back
      // to the previous page.
      if (app.viewingDiscussion(this)) {
        app.history.back();
      }

      return this.delete();
    }
  },

  /**
   * Rename the discussion.
   *
   * @return {Promise}
   */
  renameAction() {
    const currentTitle = this.title();
    const title = prompt(extractText(app.translator.trans('core.forum.discussion_controls.rename_text')), currentTitle);

    // If the title is different to what it was before, then save it. After the
    // save has completed, update the post stream as there will be a new post
    // indicating that the discussion was renamed.
    if (title && title !== currentTitle) {
      return this.save({title}).then(() => {
        if (app.viewingDiscussion(this)) {
          app.current.stream.update();
        }
        m.redraw();
      });
    }
  }
};
