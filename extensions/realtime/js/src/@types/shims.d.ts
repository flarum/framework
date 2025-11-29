import 'flarum/common/Application';
import Pusher, { Channel } from 'pusher-js';

declare module 'flarum/common/Application' {
  export default interface Application {
    websocket: Pusher;
    websocket_channels: {
      public: Channel | null;
      user: Channel | null;
    };
  }
}

declare module 'flarum/tags/common/models/Tag' {
  export default interface Tag {
    isQnA(): boolean;
    reminders(): boolean;
  }
}

declare module 'flarum/forum/states/DiscussionListState' {
  export default interface DiscussionListState {
    bestAnswer: string | undefined;
  }
}

declare module 'flarum/common/models/User' {
  export default interface User {
    bestAnswerCount(): number;
  }
}
