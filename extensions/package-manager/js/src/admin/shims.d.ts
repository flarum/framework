import 'dayjs/plugin/relativeTime';
import ExtensionManagerState from './states/ExtensionManagerState';

export interface AsyncBackendResponse {
  processing: boolean;
}

declare module 'flarum/admin/AdminApplication' {
  export default interface AdminApplication {
    extensionManager: ExtensionManagerState;
  }
}
