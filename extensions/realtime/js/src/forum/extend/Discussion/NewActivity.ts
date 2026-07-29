import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import RealtimeState from '../../RealtimeState';

const CORE_POSTED_EVENT = 'Flarum\\Post\\Event\\Posted';
const CORE_RENAMED_EVENT = 'discussionRenamed';
const CORE_REVISED_EVENT = 'revisedEvent';

export default function (): void {
  extend(DiscussionPage.prototype, 'oninit', function (this: any) {
    this.websocketEventPosted = (data: unknown): void => {
      // Capture BEFORE the payload (and the refetch below) updates postIds:
      // once new posts are in the store, viewingEnd()'s 1-post drift
      // tolerance can no longer tell "was at the end, several posts arrived
      // at once" apart from "viewing an interior window" — so a single
      // missed event would permanently disable live-append (#4717).
      const wasViewingEnd: boolean = !!this.stream?.viewingEnd();

      const discussion = app.store.pushPayload(data as Parameters<typeof app.store.pushPayload>[0]) as any;

      if (discussion.id() === this.discussion?.id() && this.stream) {
        const oldCount: number = this.discussion.commentCount();

        app.store.find('discussions', this.discussion.id()).then(() => {
          if (wasViewingEnd) {
            this.stream.syncEnd().then((): void => m.redraw());
          } else {
            m.redraw();
          }

          if (!document.hasFocus()) {
            app.setTitleCount(Math.max(0, this.discussion.commentCount() - oldCount));

            $(window).one('focus', (): void => {
              app.setTitleCount(0);
            });
          }
        });
      }
    };

    this.websocketEventStreamUpdate = (data: unknown): void => {
      // Same pre-payload capture as websocketEventPosted (#4717) — event
      // posts (sticky/lock/rename) move postIds too.
      const wasViewingEnd: boolean = !!this.stream?.viewingEnd();

      const discussion = app.store.pushPayload(data as Parameters<typeof app.store.pushPayload>[0]) as any;

      if (discussion.id() === this.discussion?.id() && this.stream) {
        app.store.find('discussions', this.discussion.id()).then(() => {
          if (wasViewingEnd) {
            this.stream.syncEnd().then((): void => m.redraw());
          } else {
            m.redraw();
          }
        });
      }
    };
  });

  extend(DiscussionPage.prototype, 'oncreate', function (this: any) {
    // Bind handlers against the current channel objects. Extracted so it can
    // re-fire on reconnect — see flarum/framework#4597. On reconnect the
    // previous channel objects are discarded with the previous Pusher
    // instance, so re-binding against the new channels is non-duplicating.
    const bindHandlers = (): void => {
      app.websocket_channels.public?.bind(CORE_POSTED_EVENT, this.websocketEventPosted.bind(this));
      app.websocket_channels.user?.bind(CORE_POSTED_EVENT, this.websocketEventPosted.bind(this));

      app.websocket_channels.public?.bind(CORE_RENAMED_EVENT, this.websocketEventStreamUpdate.bind(this));
      app.websocket_channels.user?.bind(CORE_RENAMED_EVENT, this.websocketEventStreamUpdate.bind(this));

      app.websocket_channels.public?.bind(CORE_REVISED_EVENT, this.websocketEventPosted.bind(this));
      app.websocket_channels.user?.bind(CORE_REVISED_EVENT, this.websocketEventPosted.bind(this));

      for (const eventName of RealtimeState.getDiscussionStreamEventNames()) {
        app.websocket_channels.public?.bind(eventName, this.websocketEventStreamUpdate.bind(this));
        app.websocket_channels.user?.bind(eventName, this.websocketEventStreamUpdate.bind(this));
      }
    };

    bindHandlers();
    this._realtimeReconnectDisposer = RealtimeState.onChannelsReconnected(bindHandlers);
  });

  extend(DiscussionPage.prototype, 'onremove', function (this: any) {
    this._realtimeReconnectDisposer?.();
    this._realtimeReconnectDisposer = null;

    app.websocket_channels.public?.unbind(CORE_POSTED_EVENT);
    app.websocket_channels.user?.unbind(CORE_POSTED_EVENT);

    app.websocket_channels.public?.unbind(CORE_RENAMED_EVENT);
    app.websocket_channels.user?.unbind(CORE_RENAMED_EVENT);

    app.websocket_channels.public?.unbind(CORE_REVISED_EVENT);
    app.websocket_channels.user?.unbind(CORE_REVISED_EVENT);

    for (const eventName of RealtimeState.getDiscussionStreamEventNames()) {
      app.websocket_channels.public?.unbind(eventName);
      app.websocket_channels.user?.unbind(eventName);
    }
  });
}
