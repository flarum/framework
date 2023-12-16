import 'dayjs/plugin/relativeTime';
import PackageManagerState from './states/PackageManagerState';

export interface AsyncBackendResponse {
  processing: boolean;
}

declare module 'flarum/admin/AdminApplication' {
  export default interface AdminApplication {
    packageManager: PackageManagerState;
  }
}
