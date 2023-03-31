import type BasePost from 'flarum/common/models/Post';

declare module 'flarum/common/models/Post' {
  export default interface Post {
    mentionedBy(): BasePost[] | undefined | null;
    mentionedByCount(): number;
  }
}
