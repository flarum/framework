import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import RealtimeState from '../../RealtimeState';

const CORE_POSTED_EVENT = 'Flarum\\Post\\Event\\Posted';
const CORE_RENAMED_EVENT = 'discussionRenamed';
const CORE_REVISED_EVENT = 'revisedEvent';

export default function () {
  extend(DiscussionPage.prototype, 'oninit', function (this: any) {
    this.websocketEventPosted = function (this: any, data: any) {
      const discussion = app.store.pushPayload(data) as any;

      if (discussion.id() === this.discussion?.id() && this.stream) {
        const oldCount = this.discussion.commentCount() as number;

        app.store.find('discussions', this.discussion.id()).then(() => {
          this.stream.update().then(() => m.redraw());

          if (!document.hasFocus()) {
            app.setTitleCount(Math.max(0, this.discussion.commentCount() - oldCount));

            $(window).one('focus', () => {
              app.setTitleCount(0);
            });
          }
        });
      }
    };

    this.websocketEventStreamUpdate = function (this: any, data: any) {
      const discussion = app.store.pushPayload(data) as any;

      if (discussion.id() === this.discussion?.id() && this.stream) {
        app.store.find('discussions', this.discussion.id()).then(() => {
          this.stream.update().then(() => m.redraw());
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

  extend(DiscussionPage.prototype, 'onremove', function (this: any) {
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
