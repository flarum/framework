import 'flarum/common/models/User';

declare module 'flarum/common/models/User' {
  export default interface User {
    canEditNickname(): boolean;
  }
}
