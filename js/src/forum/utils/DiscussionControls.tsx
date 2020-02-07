import DiscussionPage from '../components/DiscussionPage';
// import ReplyComposer from '../components/ReplyComposer';
import LogInModal from '../components/LogInModal';
import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
// import RenameDiscussionModal from '../components/RenameDiscussionModal';
import ItemList from '../../common/utils/ItemList';
import extractText from '../../common/utils/extractText';
import Discussion from '../../common/models/Discussion';

/**
 * The `DiscussionControls` utility constructs a list of buttons for a
 * discussion which perform actions on it.
 */
export default {
    /**
     * Get a list of controls for a discussion.
     *
     * @param discussion
     * @param context The parent component under which the controls menu will
     *     be displayed
     * @public
     */
    controls(discussion: Discussion, context): ItemList {
        const items = new ItemList();

        ['user', 'moderation', 'destructive'].forEach(section => {
            const controls = this[section](discussion, context).toArray();
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
     * @param discussion
     * @param context The parent component under which the controls menu will
     *     be displayed.
     * @protected
     */
    user(discussion: Discussion, context: any): ItemList {
        const items = new ItemList();

        // Only add a reply control if this is the discussion's controls dropdown
        // for the discussion page itself. We don't want it to show up for
        // discussions in the discussion list, etc.
        if (context instanceof DiscussionPage) {
            items.add(
                'reply',
                !app.session.user || discussion.canReply()
                    ? Button.component({
                          icon: 'fas fa-reply',
                          children: app.translator.trans(
                              app.session.user
                                  ? 'core.forum.discussion_controls.reply_button'
                                  : 'core.forum.discussion_controls.log_in_to_reply_button'
                          ),
                          onclick: this.replyAction.bind(discussion, true, false),
                      })
                    : Button.component({
                          icon: 'fas fa-reply',
                          children: app.translator.trans('core.forum.discussion_controls.cannot_reply_button'),
                          className: 'disabled',
                          title: app.translator.trans('core.forum.discussion_controls.cannot_reply_text'),
                      })
            );
        }

        return items;
    },

    /**
     * Get controls for a discussion pertaining to moderation (e.g. rename, lock).
     *
     * @param discussion
     * @param context The parent component under which the controls menu will
     *     be displayed.
     * @protected
     */
    moderation(discussion): ItemList {
        const items = new ItemList();

        if (discussion.canRename()) {
            items.add(
                'rename',
                Button.component({
                    icon: 'fas fa-pencil-alt',
                    children: app.translator.trans('core.forum.discussion_controls.rename_button'),
                    onclick: this.renameAction.bind(discussion),
                })
            );
        }

        return items;
    },

    /**
     * Get controls for a discussion which are destructive (e.g. delete).
     *
     * @param discussion
     * @param context The parent component under which the controls menu will
     *     be displayed.
     * @protected
     */
    destructive(discussion: Discussion): ItemList {
        const items = new ItemList();

        if (!discussion.isHidden()) {
            if (discussion.canHide()) {
                items.add(
                    'hide',
                    Button.component({
                        icon: 'far fa-trash-alt',
                        children: app.translator.trans('core.forum.discussion_controls.delete_button'),
                        onclick: this.hideAction.bind(discussion),
                    })
                );
            }
        } else {
            if (discussion.canHide()) {
                items.add(
                    'restore',
                    Button.component({
                        icon: 'fas fa-reply',
                        children: app.translator.trans('core.forum.discussion_controls.restore_button'),
                        onclick: this.restoreAction.bind(discussion),
                    })
                );
            }

            if (discussion.canDelete()) {
                items.add(
                    'delete',
                    Button.component({
                        icon: 'fas fa-times',
                        children: app.translator.trans('core.forum.discussion_controls.delete_forever_button'),
                        onclick: this.deleteAction.bind(discussion),
                    })
                );
            }
        }

        return items;
    },

    /**
     * Open the reply composer for the discussion. A promise will be returned,
     * which resolves when the composer opens successfully. If the user is not
     * logged in, they will be prompted. If they don't have permission to
     * reply, the promise will be rejected.
     *
     * @param goToLast Whether or not to scroll down to the last post if
     *     the discussion is being viewed.
     * @param forceRefresh Whether or not to force a reload of the
     *     composer component, even if it is already open for this discussion.
     */
    replyAction(this: Discussion, goToLast: boolean, forceRefresh: boolean): Promise<any> {
        return new Promise((resolve, reject) => {
            if (app.session.user) {
                if (this.canReply()) {
                    let component = app.composer.component;
                    if (!app.composingReplyTo(this) || forceRefresh) {
                        component = new ReplyComposer({
                            user: app.session.user,
                            discussion: this,
                        });
                        app.composer.load(component);
                    }

                    app.composer.show();

                    if (goToLast && app.viewingDiscussion(this) && !app.composer.isFullScreen()) {
                        app.current.stream.goToNumber('reply');
                    }

                    return resolve(component);
                } else {
                    return reject();
                }
            }

            app.modal.show(new LogInModal());

            reject();
        });
    },

    /**
     * Hide a discussion.
     */
    hideAction(this: Discussion) {
        this.pushAttributes({ hiddenAt: new Date(), hiddenUser: app.session.user });

        return this.save({ isHidden: true });
    },

    /**
     * Restore a discussion.
     */
    restoreAction(this: Discussion) {
        this.pushAttributes({ hiddenAt: null, hiddenUser: null });

        return this.save({ isHidden: false });
    },

    /**
     * Delete the discussion after confirming with the user.
     */
    deleteAction(this: Discussion) {
        if (confirm(extractText(app.translator.trans('core.forum.discussion_controls.delete_confirmation')))) {
            // If we're currently viewing the discussion that was deleted, go back
            // to the previous page.
            if (app.viewingDiscussion(this)) {
                app.history.back();
            }

            return this.delete().then(() => {
                // If there is a discussion list in the cache, remove this discussion.
                if (app.cache.discussionList) {
                    app.cache.discussionList.removeDiscussion(this);
                    m.redraw();
                }
            });
        }
    },

    /**
     * Rename the discussion.
     */
    renameAction(this: Discussion) {
        return app.modal.show(
            new RenameDiscussionModal({
                currentTitle: this.title(),
                discussion: this,
            })
        );
    },
};
