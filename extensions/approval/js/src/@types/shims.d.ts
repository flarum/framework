declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    isApproved(): boolean;
  }
}

declare module 'flarum/common/models/Post' {
  export default interface Post {
    isApproved(): boolean;
    canApprove(): boolean;
  }
}
