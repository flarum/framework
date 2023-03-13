import MentionFormats from '../forum/mentionables/formats/MentionFormats';

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
