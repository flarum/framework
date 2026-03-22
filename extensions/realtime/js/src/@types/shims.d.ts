import 'flarum/common/Application';
import Pusher, { Channel } from 'pusher-js';

declare module 'flarum/common/Application' {
  export default interface Application {
    websocket: Pusher;
    websocket_channels: {
      public: Channel | null;
      user: Channel | null;
      /** Presence channel for the currently open discussion (typing indicator). */
      discussion?: Channel;
    };
  }
}
