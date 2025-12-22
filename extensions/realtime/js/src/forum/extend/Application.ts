import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Pusher, { Channel } from 'pusher-js';
import Application from 'flarum/common/Application';

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

    if (app.session.user) {
      app.websocket_channels.user = app.websocket.subscribe('private-user=' + app.session.user.id());
    } else if (!this.forum.attribute<boolean>('websocket.disallow_connection')) {
      app.websocket_channels.public = app.websocket.subscribe('public');
    }
  });
}
