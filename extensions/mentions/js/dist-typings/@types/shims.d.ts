import MentionFormats from '../forum/mentionables/formats/MentionFormats';
import type BasePost from 'flarum/common/models/Post';

declare module 'flarum/forum/ForumApplication' {
  export default interface ForumApplication {
    mentionFormats: MentionFormats;
  }
}

declare module 'flarum/common/models/User' {
  export default interface User {
    canMentionGroups(): boolean;
  }
}

declare module 'flarum/common/models/Post' {
  export default interface Post {
    mentionedBy(): BasePost[] | undefined | null;
    mentionedByCount(): number;
  }
}
