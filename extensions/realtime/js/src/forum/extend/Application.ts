import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Pusher from 'pusher-js';
import Application from 'flarum/common/Application';
import RealtimeState from '../RealtimeState';
import NotificationToast from '../components/NotificationToast';
import NotificationToastState from '../states/NotificationToastState';

export default function () {
  extend(Application.prototype, 'mount' as any, function () {
    // Enable logging to console when debug is enabled.
    Pusher.logToConsole = this.forum.attribute<boolean>('debug');

    const wsHost = this.forum.attribute<string>('websocket.host');
    const secure = this.forum.attribute<boolean>('websocket.secure');

    app.websocket = new Pusher(this.forum.attribute<string>('websocket.key'), {
      channelAuthorization: {
        endpoint: this.forum.attribute<string>('apiUrl') + '/websocket/auth',
        transport: 'ajax',
      },
      wsHost,
      wsPort: this.forum.attribute<number>('websocket.port'),
      wssPort: this.forum.attribute<number>('websocket.port'),
      enabledTransports: ['wss', 'ws'],
      forceTLS: secure,
    });

    app.websocket_channels = {
      public: null,
      user: null,
    };

    // Mount the notification toast container outside the main Mithril tree.
    const toastState = new NotificationToastState();
    const toastEl = document.createElement('div');
    document.body.appendChild(toastEl);
    m.mount(toastEl, { view: () => m(NotificationToast, { state: toastState }) });

    if (app.session.user) {
      const userChannel = app.websocket.subscribe('private-user=' + app.session.user.id());
      app.websocket_channels.user = userChannel;
      RealtimeState.notifyUserChannelReady(userChannel);

      // Show a toast for each incoming realtime notification and update the badge count.
      userChannel.bind('notification', (data: unknown) => {
        const notification = app.store.pushPayload(data as any) as any;

        if (notification) {
          const user = app.session.user as any;
          user?.pushAttributes({
            unreadNotificationCount: (user.unreadNotificationCount() ?? 0) + 1,
            newNotificationCount: (user.newNotificationCount() ?? 0) + 1,
          });

          toastState.push(notification);
        }
      });
    } else if (!this.forum.attribute<boolean>('websocket.disallow_connection')) {
      const publicChannel = app.websocket.subscribe('public');
      app.websocket_channels.public = publicChannel;
      RealtimeState.notifyPublicChannelReady(publicChannel);
    }
  });
}
