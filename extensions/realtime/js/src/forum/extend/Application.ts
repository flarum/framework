import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Pusher from 'pusher-js';
import Application from 'flarum/common/Application';
import RealtimeState from '../RealtimeState';
import NotificationToast from '../components/NotificationToast';
import NotificationToastState from '../states/NotificationToastState';
import isIOS from '../utils/isIOS';

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

    const indexTypingEnabled = !!app.data['flarum-realtime.index-typing-indicator'];
    const indexTypingRestrictedEnabled = !!app.data['flarum-realtime.index-typing-indicator-restricted'];

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
      app.websocket_channels.indexTyping = undefined;
      app.websocket_channels.indexTypingTags = undefined;

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

        userChannel.bind('assetsRevision', (data: { revision?: string }) => app.checkAssetsRevision(data?.revision ?? null));

        RealtimeState.notifyUserChannelReady(userChannel);
      } else if (!disallowPublicConnection) {
        const publicChannel = websocket.subscribe('public');
        app.websocket_channels.public = publicChannel;

        publicChannel.bind('assetsRevision', (data: { revision?: string }) => app.checkAssetsRevision(data?.revision ?? null));

        RealtimeState.notifyPublicChannelReady(publicChannel);
      }

      const bindIndexTyping = (channel: ReturnType<Pusher['subscribe']>): void => {
        channel.bind('index-typing', (data: { id?: number; source?: string; typing: boolean; tags?: number[] }) => {
          // Replies carry a discussion `id`; new-discussion compose typing has no id
          // yet and carries only `tags` (+ a per-user `source` key). Only feed the
          // discussion-list dot when there's an id.
          if (data.id !== undefined) {
            RealtimeState.indexTyping.set(data.id, data.typing);
          }

          // Light up the tag-list dots for the tags involved. The backend scopes
          // `tags` per channel, so we only ever receive tag IDs the subscriber is
          // allowed to see. `source` (discussion id or per-user key) lets concurrent
          // typers in one tag coexist without clearing each other's dot.
          if (data.tags?.length) {
            RealtimeState.indexTagTyping.set(data.source ?? data.id ?? '', data.tags, data.typing);
          }
        });
      };

      // Ambient typing dots on the discussion list. A single shared, public,
      // presence-only channel — one subscription per viewer regardless of how
      // many discussions are listed, so it stays cheap at any scale. Only
      // guest-visible discussions are surfaced here (gated server-side).
      if (indexTypingEnabled && (app.session.user || !disallowPublicConnection)) {
        const indexTypingChannel = websocket.subscribe('public-index-typing');
        app.websocket_channels.indexTyping = indexTypingChannel;
        bindIndexTyping(indexTypingChannel);
      }

      // Restricted discussions can't go on the public channel without leaking, so
      // they're routed per restricted tag. Subscribe only to the channels of the
      // restricted tags THIS user can see — the backend computes that list
      // (`index-typing-tags`, gated on the setting + tag visibility) so we issue
      // exactly one private-channel auth per restricted tag, rather than one per
      // visible tag (most of which are unrestricted and never broadcast). Channel
      // auth (AuthController::indexTypingTag) still enforces visibility server-side.
      // All feed the same IndexTypingState, so list rendering is unchanged.
      const indexTypingTagIds = this.forum.attribute<number[]>('flarum-realtime.index-typing-tags') ?? [];

      if (indexTypingRestrictedEnabled && app.session.user && indexTypingTagIds.length) {
        app.websocket_channels.indexTypingTags = indexTypingTagIds.map((tagId) => {
          const channel = websocket.subscribe('private-index-typing-tag=' + tagId);
          bindIndexTyping(channel);
          return channel;
        });
      }
    };

    // WebKit silently drops WebSocket connections when the tab is
    // backgrounded or the device sleeps, often without firing `close` —
    // pusher-js's built-in recovery never triggers (or triggers only after
    // its foreground-only activity/pong timers finally elapse), so realtime
    // updates go missing until the page is reloaded. iOS also bfcaches pages
    // on app-switch, which restores via `pageshow` (persisted=true) and does
    // NOT fire `visibilitychange` on return. We therefore hook both events.
    //
    // The visibilitychange path reconnects when on iOS (where backgrounding
    // kills the socket every time — see #4590/#4654) OR when the connection
    // is demonstrably unhealthy: pusher-js already reports a non-connected
    // state, or no protocol frame (events, pusher:pong, ...) has arrived
    // within the activity window — the zombie case where `state` still
    // claims 'connected'. Desktop Safari suspends hidden pages just like
    // iOS, so it routinely lands in that zombie case (#4717). Healthy
    // desktop tabs keep receiving pongs every ≤30s even while hidden, so a
    // plain tab switch still triggers no spurious refetch (#4662).
    //
    // `forceReconnect` constructs a fresh Pusher instance rather than
    // calling `connect()` on the existing one. pusher-js 7.6's default
    // strategy enforces a `lives: 2` budget on its WebSocket transport;
    // every silent 1006 close decrements the budget and after the second
    // one the strategy reports unsupported, so every subsequent `connect()`
    // transitions straight to `'failed'`. A fresh Pusher comes with a fresh
    // strategy tree and a full `livesLeft`, making the recovery survive
    // arbitrarily many backgrounding cycles.
    //
    // See flarum/framework#4588, #4597 and #4717.
    const RECONNECT_HIDDEN_THRESHOLD_MS = 5_000;
    // A healthy connection sees protocol traffic at least every
    // `activity_timeout` (30s, advertised by the server) plus the pong wait.
    const STALE_CONNECTION_THRESHOLD_MS = 65_000;

    let hiddenSince: number | null = null;
    let lastFrameAt = Date.now();
    let everConnected = false;

    // Refresh the data that realtime events would have kept current. Pusher
    // has no server-side buffering: anything that fired while the socket was
    // down is lost, so on reconnect the UI has to catch up by refetching.
    const catchUp = (): void => {
      (app as any).discussions?.refresh?.();

      const discussion = app.current.get('discussion');
      const stream = app.current.get('stream');

      if (discussion && stream) {
        // Capture BEFORE the refetch grows postIds: missed events never
        // reached the store, so right now viewingEnd() still reflects the
        // last synced state. Afterwards its 1-post drift tolerance could no
        // longer tell "was at the end, several posts arrived while
        // disconnected" apart from "viewing an interior window" (#4717).
        const wasViewingEnd: boolean = !!stream.viewingEnd();

        app.store.find('discussions', discussion.id() as string).then(() => {
          if (wasViewingEnd) {
            stream.syncEnd().then((): void => m.redraw());
          } else {
            m.redraw();
          }
        });
      }
    };

    // Track liveness and reconnects. Applied to the initial Pusher instance
    // and to every fresh instance `forceReconnect` builds.
    const watchConnection = (websocket: Pusher): void => {
      websocket.bind_global((): void => {
        lastFrameAt = Date.now();
      });

      websocket.connection.bind('state_change', (states: { previous: string; current: string }) => {
        if (states.current !== 'connected') return;

        const isReconnect = everConnected;
        everConnected = true;
        lastFrameAt = Date.now();

        // Catch up on every effective reconnect — pusher-js's own recovery
        // as well as our forced ones. Gated on the transition into
        // `'connected'` (not run right after `connect()`) because an
        // immediate Mithril redraw races with pusher-js's channel
        // resubscription and can leave the client receiving no further push
        // events (see #4590).
        if (isReconnect) catchUp();
      });
    };

    const forceReconnect = (): void => {
      const previous = app.websocket;
      previous?.disconnect();

      const fresh = new Pusher(wsKey, pusherOptions);
      app.websocket = fresh;

      watchConnection(fresh);
      setupChannels(fresh);
      RealtimeState.notifyChannelsReconnected();
    };

    app.websocket = new Pusher(wsKey, pusherOptions);
    watchConnection(app.websocket);
    setupChannels(app.websocket);

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
      if (wasHiddenFor <= RECONNECT_HIDDEN_THRESHOLD_MS) return;

      const unhealthy = app.websocket?.connection.state !== 'connected' || Date.now() - lastFrameAt > STALE_CONNECTION_THRESHOLD_MS;

      if (isIOS() || unhealthy) {
        forceReconnect();
      }
    });

    window.addEventListener('pageshow', (event) => {
      if (event.persisted) forceReconnect();
    });
  });
}
