import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Stream from 'flarum/common/utils/Stream';
import throttle from 'lodash-es/throttle';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';

export default function () {
  extend('ext:flarum/messages/forum/components/MessageStream', 'content', function (items) {
    const typingUsers = Object.keys(this.getTypingUsers());

    const count = typingUsers.length;
    const max = 3;

    const classes = classList(['TypingUsersContainer', count > 0 && 'TypingUsersContainer-active']);
    const typingIcon = count > 0 ? 'fas fa-ellipsis-h fa-beat' : 'fas fa-pause';

    const namedUsers = typingUsers.slice(0, max).join(', ');

    let showUsers = true; // default value

    if (app.session?.user) {
      showUsers = app.session.user.preferences()['flarum-realtime.typing-indicator-full'];
    }

    items.splice(
      items.length - 1,
      0,
      <div className={classes} key="typing">
        <div className="TypingUsers">
          <Icon name={typingIcon} />
          {count > 0
            ? showUsers
              ? app.translator.trans('flarum-realtime.forum.typing-indicator.users-are-typing', {
                  users: namedUsers,
                  count: count,
                  others: Math.max(count - max, 0),
                })
              : app.translator.trans('flarum-realtime.forum.typing-indicator.people-are-typing', { number: count })
            : app.translator.trans('flarum-realtime.forum.typing-indicator.no-activity')}
        </div>
      </div>
    );
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'oninit', function () {
    this.previousContent = new Stream('');
    this.usersTyping = new Stream({});
    this.typingTruncationListener = null;
    this.typingListener = null;

    this.getTypingUsers = function () {
      const invalidateWhen = new Date().getTime() - 3500;

      let users = this.usersTyping();
      let timeout = null;

      for (const displayName in users) {
        const time = users[displayName];

        if (time < invalidateWhen) {
          delete users[displayName];
        } else if (!timeout || timeout < time) {
          timeout = time;
        }
      }

      this.usersTyping(users);

      if (timeout && this.typingTruncationListener) {
        clearTimeout(this.typingTruncationListener);
      }

      if (timeout) {
        this.typingTruncationListener = setTimeout(
          function () {
            m.redraw();
          }.bind(this),
          timeout - new Date().getTime()
        );
      }

      return users;
    };

    this.userTyping = function (data) {
      let users = this.usersTyping();

      // Don't show user typing, don't show 'anonymous' as well
      if (!data.discloseOnline) {
        return;
      }

      users[data.displayName] = data.time;

      this.usersTyping(users);

      m.redraw();
    };

    this.actorIsTyping = function () {
      const discloseOnline = app.session.user.preferences()?.discloseOnline;

      app.websocket_channels.privateMessages.trigger('client-typing', {
        displayName: discloseOnline ? app.session.user.displayName() : '[anonymous]',
        discloseOnline,
        time: Date.now(),
      });
    };

    this.checkTyping = function () {
      if (this.previousContent() !== app.composer.fields.content()) {
        this.actorIsTyping();

        // Update previous, so we can match on the next tick.
        this.previousContent(app.composer.fields.content());
      }
    };
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'view', function () {
    if (app.forum.attribute('websocket.disallow_connection')) return;

    if (this.attrs?.dialog && !this.typingListener) {
      this.typingListener = throttle(
        function () {
          this.checkTyping();
        }.bind(this),
        2000
      );

      this.typingListener = setInterval(this.typingListener, 1000);
    }

    if (this.attrs?.dialog) {
      app.websocket_channels.privateMessages = app.websocket.subscribe('private-privateMessageTyping=' + this.attrs?.dialog.id());

      app.websocket_channels.privateMessages.bind('client-typing', (data) => {
        this.userTyping(data);
      });
    }
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'onremove', function () {
    if (this.typingListener) clearInterval(this.typingListener);

    if (this.typingTruncationListener) clearTimeout(this.typingTruncationListener);

    // Unsubscribe
    app.websocket_channels.privateMessages.unsubscribe();
  });
}
