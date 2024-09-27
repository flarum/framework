declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    isLocked(): boolean;
    canLock(): boolean;
  }
}
