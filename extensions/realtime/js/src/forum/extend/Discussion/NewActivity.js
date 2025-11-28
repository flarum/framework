import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';

export default function () {
  extend(DiscussionPage.prototype, 'oninit', function () {
    this.websocketEventPosted = function (data) {
      const discussion = app.store.pushPayload(data);

      if (discussion.id() === this.discussion?.id() && this.stream) {
        const oldCount = this.discussion.commentCount();

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

    this.websocketEventStreamUpdate = function (data) {
      const discussion = app.store.pushPayload(data);

      if (discussion.id() === this.discussion?.id() && this.stream) {
        app.store.find('discussions', this.discussion.id()).then(() => {
          this.stream.update().then(() => m.redraw());
        });
      }
    };
  });

  extend(DiscussionPage.prototype, 'oncreate', function () {
    app.websocket_channels.public?.bind('Flarum\\Post\\Event\\Posted', this.websocketEventPosted.bind(this));
    app.websocket_channels.user?.bind('Flarum\\Post\\Event\\Posted', this.websocketEventPosted.bind(this));
    app.websocket_channels.public?.bind('discussionRenamed', this.websocketEventStreamUpdate.bind(this));
    app.websocket_channels.user?.bind('discussionRenamed', this.websocketEventStreamUpdate.bind(this));
    app.websocket_channels.public?.bind('revisedEvent', this.websocketEventPosted.bind(this));
    app.websocket_channels.user?.bind('revisedEvent', this.websocketEventPosted.bind(this));

    // fof/best-answer
    app.websocket_channels.public?.bind('bestAnswerMutation', this.websocketEventStreamUpdate.bind(this));
    app.websocket_channels.user?.bind('bestAnswerMutation', this.websocketEventStreamUpdate.bind(this));

    // flarum/likes
    app.websocket_channels.public?.bind('likesMutation', this.websocketEventStreamUpdate.bind(this));
    app.websocket_channels.user?.bind('likesMutation', this.websocketEventStreamUpdate.bind(this));

    // fof/gamification
    app.websocket_channels.public?.bind('votedMutation', this.websocketEventStreamUpdate.bind(this));
    app.websocket_channels.user?.bind('votedMutation', this.websocketEventStreamUpdate.bind(this));

    // fof/reactions
    app.websocket_channels.public?.bind('reactionsMutation', this.websocketEventStreamUpdate.bind(this));
    app.websocket_channels.user?.bind('reactionsMutation', this.websocketEventStreamUpdate.bind(this));

    // flarum/lock
    app.websocket_channels.user?.bind('lockedEvent', this.websocketEventPosted.bind(this));
  });

  extend(DiscussionPage.prototype, 'onremove', function () {
    app.websocket_channels.public?.unbind('Flarum\\Post\\Event\\Posted');
    app.websocket_channels.user?.unbind('Flarum\\Post\\Event\\Posted');
    app.websocket_channels.public?.unbind('discussionRenamed');
    app.websocket_channels.user?.unbind('discussionRenamed');
    app.websocket_channels.public?.unbind('revisedEvent');
    app.websocket_channels.user?.unbind('revisedEvent');

    app.websocket_channels.public?.unbind('bestAnswerMutation');
    app.websocket_channels.user?.unbind('bestAnswerMutation');

    app.websocket_channels.public?.unbind('likesMutation');
    app.websocket_channels.user?.unbind('likesMutation');

    app.websocket_channels.public?.unbind('votedMutation');
    app.websocket_channels.user?.unbind('votedMutation');

    app.websocket_channels.public?.unbind('reactionsMutation');
    app.websocket_channels.user?.unbind('reactionsMutation');

    app.websocket_channels.user?.unbind('lockedEvent');
  });
}
