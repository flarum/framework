import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Stream from 'flarum/common/utils/Stream';
// @ts-ignore — lodash-es does not ship its own declaration files
import throttle from 'lodash-es/throttle';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';

interface TypingUserMap {
  [displayName: string]: number;
}

interface TypingData {
  displayName: string;
  discloseOnline: boolean;
  time: number;
}

export default function (): void {
  extend('flarum/forum/components/PostStream', 'endItems', function (this: any, items) {
    if (this.discussion.attribute('canViewWhoTypes')) {
      const typingUsers = Object.keys(this.getTypingUsers());
      const count = typingUsers.length;
      const max = 3;

      const classes = classList(['TypingUsersContainer', count > 0 && 'TypingUsersContainer-active']);
      const typingIcon = count > 0 ? 'fas fa-ellipsis-h fa-beat' : 'fas fa-pause';
      const namedUsers = typingUsers.slice(0, max).join(', ');

      let showUsers = true;

      if (app.session?.user) {
        showUsers = app.session.user.preferences()?.['flarum-realtime.typing-indicator-full'] ?? true;
      }

      items.add(
        'usersTyping',
        <div className={classes} key="typing">
          <div className="TypingUsers">
            <Icon name={typingIcon} />
            {count > 0
              ? showUsers
                ? app.translator.trans('flarum-realtime.forum.typing-indicator.users-are-typing', {
                    users: namedUsers,
                    count,
                    others: Math.max(count - max, 0),
                  })
                : app.translator.trans('flarum-realtime.forum.typing-indicator.people-are-typing', { number: count })
              : app.translator.trans('flarum-realtime.forum.typing-indicator.no-activity')}
          </div>
        </div>,
        70
      );
    }
  });

  extend('flarum/forum/components/PostStream', 'oninit', function (this: any) {
    this.previousContent = (Stream as any)('') as ReturnType<typeof Stream>;
    this.usersTyping = (Stream as any)({} as TypingUserMap) as ReturnType<typeof Stream>;
    this.typingTruncationListener = null as ReturnType<typeof setTimeout> | null;
    this.typingListener = null as ReturnType<typeof setInterval> | null;

    this.getTypingUsers = (): TypingUserMap => {
      const invalidateWhen = Date.now() - 6000;
      const users: TypingUserMap = { ...this.usersTyping() };
      let latestTime: number | null = null;

      for (const displayName of Object.keys(users)) {
        if (users[displayName] < invalidateWhen) {
          delete users[displayName];
        } else if (!latestTime || latestTime < users[displayName]) {
          latestTime = users[displayName];
        }
      }

      this.usersTyping(users);

      if (latestTime && this.typingTruncationListener) {
        clearTimeout(this.typingTruncationListener);
      }

      if (latestTime) {
        this.typingTruncationListener = setTimeout(() => m.redraw(), latestTime - Date.now());
      }

      return users;
    };

    this.userTyping = (data: TypingData): void => {
      const users: TypingUserMap = { ...this.usersTyping() };

      if (!data.discloseOnline) {
        data.displayName = String(app.translator.trans('flarum-realtime.forum.typing-indicator.anonymous-user'));
      }

      users[data.displayName] = data.time;
      this.usersTyping(users);
      m.redraw();
    };

    this.actorIsTyping = (): void => {
      const discloseOnline = app.session.user?.preferences()?.discloseOnline;

      app.websocket_channels.discussion?.trigger('client-typing', {
        displayName: discloseOnline ? app.session.user?.displayName() : '[anonymous]',
        discloseOnline,
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
      app.websocket_channels.discussion = app.websocket.subscribe('private-typing=' + m.route.param('id').match(/[0-9]+/));

      if (this.discussion.attribute('canViewWhoTypes')) {
        app.websocket_channels.discussion.bind('client-typing', (data: TypingData) => {
          this.userTyping(data);
        });
      }
    }
  });

  extend('flarum/forum/components/PostStream', 'onremove', function (this: any) {
    if (this.typingListener) clearInterval(this.typingListener);
    if (this.typingTruncationListener) clearTimeout(this.typingTruncationListener);
  });
}
