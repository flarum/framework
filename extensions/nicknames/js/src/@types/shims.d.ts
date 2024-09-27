declare module 'flarum/common/models/User' {
  export default interface User {
    canEditNickname(): boolean;
  }
}
