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

    const wsKey = this.forum.attribute<string>('websocket.key');
    const wsHost = this.forum.attribute<string>('websocket.host');
    const secure = this.forum.attribute<boolean>('websocket.secure');
    const disallowPublicConnection = this.forum.attribute<boolean>('websocket.disallow_connection');

    const pusherOptions = {
      channelAuthorization: {
        endpoint: this.forum.attribute<string>('apiUrl') + '/websocket/auth',
        transport: 'ajax' as const,
      },
      wsHost,
      wsPort: this.forum.attribute<number>('websocket.port'),
      wssPort: this.forum.attribute<number>('websocket.port'),
      enabledTransports: ['wss', 'ws'] as ('wss' | 'ws')[],
      forceTLS: secure,
    };

    app.websocket_channels = {
      public: null,
      user: null,
    };

    // Mount the notification toast container outside the main Mithril tree.
    // This persists across reconnect cycles — only the Pusher instance and
    // its channels are rebuilt.
    const toastState = new NotificationToastState();
    const toastEl = document.createElement('div');
    document.body.appendChild(toastEl);
    m.mount(toastEl, { view: () => m(NotificationToast, { state: toastState }) });

    // Subscribe to user/public channels and bind the inline notification
    // handler. Factored so `forceReconnect` can call it again against a fresh
    // Pusher instance after iOS Safari backgrounding.
    const setupChannels = (websocket: Pusher): void => {
      app.websocket_channels.public = null;
      app.websocket_channels.user = null;

      if (app.session.user) {
        const userChannel = websocket.subscribe('private-user=' + app.session.user.id());
        app.websocket_channels.user = userChannel;

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

        RealtimeState.notifyUserChannelReady(userChannel);
      } else if (!disallowPublicConnection) {
        const publicChannel = websocket.subscribe('public');
        app.websocket_channels.public = publicChannel;
        RealtimeState.notifyPublicChannelReady(publicChannel);
      }
    };

    app.websocket = new Pusher(wsKey, pusherOptions);
    setupChannels(app.websocket);

    // iOS Safari silently drops WebSocket connections when the tab is
    // backgrounded or the device sleeps, without firing `close` — pusher-js's
    // built-in recovery never triggers, so realtime updates go missing until
    // the page is reloaded. iOS also bfcaches pages on app-switch, which
    // restores via `pageshow` (persisted=true) and does NOT fire
    // `visibilitychange` on return. We therefore hook both events.
    //
    // `forceReconnect` constructs a fresh Pusher instance rather than
    // calling `connect()` on the existing one. pusher-js 7.6's default
    // strategy enforces a `lives: 2` budget on its WebSocket transport;
    // every iOS-initiated 1006 close decrements the budget and after the
    // second backgrounding the strategy reports unsupported, so every
    // subsequent `connect()` transitions straight to `'failed'`. A fresh
    // Pusher comes with a fresh strategy tree and a full `livesLeft`,
    // making the recovery survive arbitrarily many backgrounding cycles.
    //
    // After reconnecting, Pusher has no server-side buffering for events
    // that fired while the socket was dead — we refresh the visible
    // discussions list once the new connection reports `'connected'` so the
    // UI catches up on missed activity. Refresh is gated on the
    // `'connected'` event (not fired immediately after `connect()`) because
    // an immediate Mithril redraw races with pusher-js's channel
    // resubscription and can leave the client receiving no further push
    // events.
    //
    // See flarum/framework#4588 and #4597.
    const RECONNECT_HIDDEN_THRESHOLD_MS = 5_000;
    let hiddenSince: number | null = null;

    const forceReconnect = (): void => {
      const previous = app.websocket;
      previous?.disconnect();

      const fresh = new Pusher(wsKey, pusherOptions);
      app.websocket = fresh;

      const onReconnected = (): void => {
        fresh.connection.unbind('connected', onReconnected);
        (app as any).discussions?.refresh?.();
      };
      fresh.connection.bind('connected', onReconnected);

      setupChannels(fresh);
      RealtimeState.notifyChannelsReconnected();
    };

    // Application.mount() runs once per page load, so these listeners are
    // installed once and live for the lifetime of the page — no teardown needed.
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        hiddenSince = Date.now();
        return;
      }
      if (hiddenSince === null) return;
      const wasHiddenFor = Date.now() - hiddenSince;
      hiddenSince = null;
      if (wasHiddenFor > RECONNECT_HIDDEN_THRESHOLD_MS) {
        forceReconnect();
      }
    });

    window.addEventListener('pageshow', (event) => {
      if (event.persisted) forceReconnect();
    });
  });
}
