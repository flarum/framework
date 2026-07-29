import 'flarum/common/Application';

declare module 'flarum/common/Application' {
  export interface ApplicationData {
    // Injected by the backend Content\AdminPayload on the admin page.
    auditLogActions: Record<string, string[]>;
  }
}
