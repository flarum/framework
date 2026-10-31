import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Stream from 'flarum/common/utils/Stream';
// @ts-ignore — lodash-es does not ship its own declaration files
import throttle from 'lodash-es/throttle';
import TypingIndicator from '../../components/TypingIndicator';
import TypingState, { type TypingData } from '../../states/TypingState';

export default function (): void {
  extend('flarum/forum/components/PostStream', 'endItems', function (this: any, items) {
    if (this.discussion.attribute('canViewWhoTypes') && this.discussion.typingState) {
      // Added as a named item so themes and extensions can grab it —
      // `items.remove('typingIndicator')` — and render a fresh <TypingIndicator> wherever
      // they like (reading the state from `discussion.typingState`), choosing their own
      // keying for that context.
      //
      // In this default placement the item is spread into PostStream's keyed children
      // (alongside the posts, load-more and reply placeholder), so it needs a key here too,
      // otherwise Mithril throws on the mixed keyed/unkeyed fragment.
      items.add('typingIndicator', <TypingIndicator key="typingIndicator" state={this.discussion.typingState} />, 70);
    }
  });

  extend('flarum/forum/components/PostStream', 'oninit', function (this: any) {
    this.previousContent = (Stream as any)('') as ReturnType<typeof Stream>;
    this.typingListener = null as ReturnType<typeof setInterval> | null;

    // The state lives on the discussion model so it can be reached anywhere in the
    // discussion layout (e.g. `app.current.get('discussion')?.typingState`) without a
    // reference to this PostStream. PostStream owns its lifecycle — see onremove.
    this.discussion.typingState = new TypingState();

    this.actorIsTyping = (): void => {
      // Nothing identifying is sent: the server resolves who we are from the
      // connection's authenticated user channel, and decides which audience may
      // learn it. `time` drives the receiving client's expiry countdown.
      app.websocket_channels.discussion?.trigger('client-typing', {
        time: Date.now(),
      });
    };

    this.checkTyping = (): void => {
      if (!app.composer.composingReplyTo(this.discussion)) return;

      const currentContent = (app.composer as any).fields?.content?.();
      if (this.previousContent() !== currentContent) {
        this.actorIsTyping();
        this.previousContent(currentContent);
      }
    };
  });

  extend('flarum/forum/components/PostStream', 'view', function (this: any) {
    if (app.forum.attribute('websocket.disallow_connection')) return;

    if (this.discussion && (app.composer as any).editor && !this.typingListener) {
      const checkFn = throttle(() => this.checkTyping(), 2000);
      this.typingListener = setInterval(checkFn, 1000);
    }

    if (this.discussion) {
      const discussionId = m.route.param('id').match(/[0-9]+/);

      app.websocket_channels.discussion = app.websocket.subscribe('private-typing=' + discussionId);

      if (this.discussion.attribute('canViewWhoTypes')) {
        app.websocket_channels.discussion.bind('client-typing', (data: TypingData) => {
          this.discussion.typingState?.add(data);
        });

        // Users who may see through a hidden online status (core's
        // `user.viewLastSeenAt`) also listen on the channel that names hidden
        // typists. Subscription is authorised server-side; the attribute just
        // avoids asking for a channel we'd be refused. Anonymised events for
        // those same typists are not sent here, so there's nothing to de-duplicate.
        if (app.session.user?.attribute('canViewHiddenTypers')) {
          app.websocket_channels.discussionIdentified = app.websocket.subscribe('private-typingIdentified=' + discussionId);

          app.websocket_channels.discussionIdentified.bind('client-typing', (data: TypingData) => {
            this.discussion.typingState?.add(data);
          });
        }
      }
    }
  });

  extend('flarum/forum/components/PostStream', 'onremove', function (this: any) {
    if (this.typingListener) clearInterval(this.typingListener);

    this.discussion.typingState?.dispose();
    // Don't leave runtime state lingering on the cached discussion model.
    delete this.discussion.typingState;
  });
}
