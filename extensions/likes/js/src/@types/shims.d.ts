import User from 'flarum/common/models/User';

declare module 'flarum/common/models/Post' {
  export default interface Post {
    likes(): User[];
    likesCount(): number;
    canLike(): boolean;
  }
}
