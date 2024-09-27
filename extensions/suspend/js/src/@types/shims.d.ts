declare module 'flarum/common/models/User' {
  export default interface User {
    canSuspend(): boolean;
    suspendedUntil(): Date | string | null | undefined;
    suspendReason(): string | null | undefined;
    suspendMessage(): string | null | undefined;
  }
}
