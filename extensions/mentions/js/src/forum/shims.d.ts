import MentionableModels from './mentionables/MentionableModels';

declare module 'flarum/forum/ForumApplication' {
  export default interface ForumApplication {
    mentionables: MentionableModels;
  }
}

declare module 'flarum/common/models/User' {
  export default interface User {
    canMentionGroups(): boolean;
  }
}
