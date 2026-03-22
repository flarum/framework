import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
// @ts-ignore - throttle lacks TS declarations
import throttle from 'lodash-es/throttle';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';
import Stream from 'flarum/common/utils/Stream';

/**
 * Adds a typing indicator to the MessageStream component when flarum/realtime
 * is enabled. Binds to a per-dialog private Pusher channel to send/receive
 * `client-typing` events.
 */
export default function addRealtimeTypingIndicator() {
  extend('ext:flarum/messages/forum/components/MessageStream', 'content', function (this: any, items: any) {
    const typingUsers = Object.keys(this.getTypingUsers());

    const count = typingUsers.length;
    const max = 3;

    const classes = classList(['TypingUsersContainer', count > 0 && 'TypingUsersContainer-active']);
    const typingIcon = count > 0 ? 'fas fa-ellipsis-h fa-beat' : 'fas fa-pause';

    const namedUsers = typingUsers.slice(0, max).join(', ');

    let showUsers = true;

    if (app.session?.user) {
      showUsers = (app.session.user as any).preferences()?.['flarum-realtime.typing-indicator-full'];
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

  extend('ext:flarum/messages/forum/components/MessageStream', 'oninit', function (this: any) {
    this.previousContent = (Stream as any)('');
    this.usersTyping = (Stream as any)({});
    this.typingTruncationListener = null;
    this.typingListener = null;

    this.getTypingUsers = function (this: any) {
      const invalidateWhen = new Date().getTime() - 3500;

      const users = this.usersTyping();
      let timeout: number | null = null;

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
          function (this: any) {
            m.redraw();
          }.bind(this),
          timeout - new Date().getTime()
        );
      }

      return users;
    };

    this.userTyping = function (this: any, data: any) {
      if (!data.discloseOnline) {
        return;
      }

      const users = this.usersTyping();
      users[data.displayName] = data.time;
      this.usersTyping(users);
      m.redraw();
    };

    this.actorIsTyping = function (this: any) {
      const discloseOnline = (app.session.user as any)?.preferences()?.discloseOnline;

      (app as any).websocket_channels?.privateMessages?.trigger('client-typing', {
        displayName: discloseOnline ? app.session.user?.displayName() : '[anonymous]',
        discloseOnline,
        time: Date.now(),
      });
    };

    this.checkTyping = function (this: any) {
      if (this.previousContent() !== (app as any).composer?.fields?.content()) {
        this.actorIsTyping();
        this.previousContent((app as any).composer?.fields?.content());
      }
    };
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'oncreate', function (this: any) {
    if ((app as any).forum?.attribute('websocket.disallow_connection')) return;
    if (!this.attrs?.dialog) return;

    this.typingListener = throttle(
      function (this: any) {
        this.checkTyping();
      }.bind(this),
      2000
    );
    this.typingListener = setInterval(this.typingListener, 1000);

    (app as any).websocket_channels = (app as any).websocket_channels || {};
    (app as any).websocket_channels.privateMessages = (app as any).websocket?.subscribe('private-privateMessageTyping=' + this.attrs.dialog.id());
    (app as any).websocket_channels.privateMessages?.bind('client-typing', (data: any) => {
      this.userTyping(data);
    });
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'onremove', function (this: any) {
    if (this.typingListener) clearInterval(this.typingListener);
    if (this.typingTruncationListener) clearTimeout(this.typingTruncationListener);
    (app as any).websocket_channels?.privateMessages?.unsubscribe();
  });
}
