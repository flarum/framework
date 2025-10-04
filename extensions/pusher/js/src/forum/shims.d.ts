import Pusher, { Channel } from 'pusher-js';

declare module 'flarum/forum/ForumApplication' {
  export default interface ForumApplication {
    pusher: Promise<{
      channels: {
        main: Channel;
        user: Channel | null;
      };
      pusher: Pusher;
    }>;

    pushedUpdates: Array<any>;
  }
}

declare module 'flarum/forum/components/DiscussionList' {
  export default interface DiscussionList {
    loadingUpdated?: boolean;
  }
}
