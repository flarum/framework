import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import DiscussionList from 'flarum/forum/components/DiscussionList';
import IndexPage from 'flarum/forum/components/IndexPage';
import Button from 'flarum/common/components/Button';
import WebsocketUpdates from './WebsocketUpdates';
import extractText from 'flarum/common/utils/extractText';

export default function () {
  extend(DiscussionList.prototype, 'oninit', function () {
    this.releaseUpdates = function () {
      // Push new discussions.
      this.websocketUpdates.release(this.attrs.state);
    };

    this.addDiscussion = function (returned, discussion) {
      this.websocketUpdates.remove(discussion);

      if (app.current.matches(IndexPage)) {
        app.setTitleCount(this.websocketUpdates.length());
      }

      m.redraw();
    };

    this.websocketEventPosted = function (data) {
      // Retrieve current page params (eg for searching).
      const params = app.discussions.getParams();

      const activeTag = params.tags ? app.store.getBy('tags', 'slug', params.tags) : null;

      const noFilters = Object.keys(params.filter).length === 0;

      if (!params.q && !params.sort && (activeTag || noFilters)) {
        // The model entity this event relates to.
        const entity = app.store.pushPayload(data);

        // Identify the discussion we're dealing with.
        let discussion = entity instanceof Discussion ? entity : null;

        if (!discussion && entity instanceof Post) {
          discussion = entity.discussion();
        }

        // When we're on the Byobu private discussions page and this discussion is not private
        if (discussion && app.current.data.routeName === 'byobuPrivate' && !(discussion.recipientUsers() && discussion.recipientGroups())) {
          return;
        }

        if (
          discussion &&
          app.current.data.routeName === 'byobuPrivate' &&
          discussion.recipientUsers()?.length === 0 &&
          discussion.recipientGroups()?.length === 0
        ) {
          return;
        }

        // When we're on the user profile private discussions page, we block updates.
        if (discussion && app.current.data.routeName === 'byobuUserPrivate') return;

        // When we're on the user profile, we block updates.
        if (discussion && app.current.data.routeName === 'user.discussions') return;

        // When we're viewing a specific tag but the discussion has no such tags, ignore it.
        if (discussion && activeTag && discussion.tags?.()) {
          // Tag is not assigned to this discussion.
          const tagIds = discussion.tags().map((tag) => tag.id());
          if (tagIds.indexOf(activeTag.id()) === -1) return;
        }

        if (
          discussion &&
          discussion.tags?.() &&
          discussion.tags().find((tag) => {
            // When we have a page open of a tag the user hides
            // we will still show the notification.
            if (activeTag && activeTag.id() === tag.id()) return false;

            // When we are on the index and any of the assigned tags are set to "Hidden from All Discussions"
            if (!activeTag && tag.isHidden()) return true;

            // Ignore discussions that have a tag that the user ignored with fof/follow-tags.
            return tag.subscription?.() === 'hide';
          })
        ) {
          return;
        }

        // Identify whether the discussion is ignored by the user with flarum/subscriptions.
        if (discussion && discussion.subscription?.() === 'ignore') return;

        // We identify whether the user is following any of the tags of the discussion.
        const subscribedTag = discussion.tags()?.find((tag) => {
          return tag.subscription?.() === 'lurk' || tag.subscription?.() === 'follow';
        });

        // For subscriptions (and follow-tags)
        if (app.current.get('routeName') === 'following') {
          // Whenever the discussion has no tags the user is subscribed to,
          // or the discussion is not subscribed to; discard the event.
          if ((params.filter?.['following-tag'] && subscribedTag.length === 0) || discussion.subscription?.() !== 'follow') {
            return;
          }
        }

        // Not already pushed recently or pending an update.
        if (this.websocketUpdates.has(discussion)) return;

        // Discussion shouldn't already be top of DiscussionList.
        if (app.discussions.getPages()[0]?.items[0]?.id() === discussion.id()) return;

        const pushOnIndex =
          discussion &&
          // Not a discussion view page.
          !app.current.get('discussion');

        const pushOnView =
          discussion &&
          // Current discussion or subscribed/following the discussion or any of its tags.
          (discussion.id() === app.current.get('discussion')?.id() || subscribedTag || discussion.subscription?.() === 'follow');

        if (pushOnIndex || pushOnView) {
          this.websocketUpdates.push(discussion);

          if (app.current.matches(IndexPage)) {
            app.setTitleCount(this.websocketUpdates.length());

            m.redraw();
          }
        }
      }
    };

    this.websocketUpdates = new WebsocketUpdates();
    this.releaseTimeout = this.websocketUpdates.getReleaseInterval();
  });

  extend(DiscussionList.prototype, 'oncreate', function () {
    app.websocket_channels.public?.bind('Flarum\\Discussion\\Event\\Created', this.websocketEventPosted.bind(this));
    app.websocket_channels.public?.bind('Flarum\\Post\\Event\\Posted', this.websocketEventPosted.bind(this));

    app.websocket_channels.user?.bind('Flarum\\Discussion\\Event\\Created', this.websocketEventPosted.bind(this));
    app.websocket_channels.user?.bind('Flarum\\Post\\Event\\Posted', this.websocketEventPosted.bind(this));
  });

  extend(DiscussionList.prototype, 'onremove', function () {
    app.websocket_channels.public?.unbind('Flarum\\Discussion\\Event\\Created');
    app.websocket_channels.public?.unbind('Flarum\\Post\\Event\\Posted');

    app.websocket_channels.user?.unbind('Flarum\\Discussion\\Event\\Created');
    app.websocket_channels.user?.unbind('Flarum\\Post\\Event\\Posted');
  });

  extend(DiscussionList.prototype, 'view', function (vdom) {
    if (!this.websocketUpdates.isEmpty()) {
      const buttonLabel = (releaseTimeout) =>
        this.websocketUpdates.autoRelease()
          ? app.translator.trans('flarum-realtime.forum.push.discussion-list-new-activity-with-auto-release', {
              count: this.websocketUpdates.length(),
              releaseTimeout,
            })
          : app.translator.trans('flarum-realtime.forum.push.discussion-list-new-activity', { count: this.websocketUpdates.length() });

      if (this.websocketUpdates.length() && typeof vdom === 'object' && vdom && 'children' in vdom && vdom.children instanceof Array) {
        vdom.children.unshift(
          Button.component(
            {
              className: 'Button Button--block DiscussionList-update',
              'aria-live': 'polite',
              'aria-atomic': 'true',
              onclick: this.releaseUpdates.bind(this),
            },
            buttonLabel(this.releaseTimeout)
          )
        );

        this.websocketUpdates.startTimer();

        this.websocketUpdates.onTimer((second) => {
          if (second === 0) return this.releaseUpdates();

          this.$('.DiscussionList-update > .Button-label').text(extractText(buttonLabel(second)));
        });
      }
    }
  });

  extend(IndexPage.prototype, 'actionItems', (items) => {
    items.remove('refresh');
  });
}
