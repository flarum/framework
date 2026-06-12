import 'flarum/common/Application';
import 'flarum/common/models/Discussion';
import Pusher, { Channel } from 'pusher-js';
import type TypingState from '../forum/states/TypingState';

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

declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    /**
     * The typing-indicator state for this discussion, present while its PostStream
     * is mounted. Read it to render <TypingIndicator state={...} /> anywhere in the
     * discussion layout — e.g. `app.current.get('discussion')?.typingState`.
     */
    typingState?: TypingState;
  }
}
