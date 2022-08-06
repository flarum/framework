import QueueState from './states/QueueState';

export interface AsyncBackendResponse {
  processing: boolean;
}

declare module 'flarum/admin/AdminApplication' {
  export default interface AdminApplication {
    packageManagerQueue: QueueState;
  }
}
