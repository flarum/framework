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
      const discussion = app.store.pushPayload(data as Parameters<typeof app.store.pushPayload>[0]) as any;

      if (discussion.id() === this.discussion?.id() && this.stream) {
        const oldCount: number = this.discussion.commentCount();

        app.store.find('discussions', this.discussion.id()).then(() => {
          this.stream.update().then((): void => m.redraw());

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
      const discussion = app.store.pushPayload(data as Parameters<typeof app.store.pushPayload>[0]) as any;

      if (discussion.id() === this.discussion?.id() && this.stream) {
        app.store.find('discussions', this.discussion.id()).then(() => {
          this.stream.update().then((): void => m.redraw());
        });
      }
    };
  });

  extend(DiscussionPage.prototype, 'oncreate', function (this: any) {
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
  });

  extend(DiscussionPage.prototype, 'onremove', function () {
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
