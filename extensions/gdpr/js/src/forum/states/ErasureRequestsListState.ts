import app from 'flarum/forum/app';
import PaginatedListState from 'flarum/common/states/PaginatedListState';
import type ErasureRequest from '../../common/models/ErasureRequest';

export default class ErasureRequestsListState extends PaginatedListState<ErasureRequest> {
  constructor() {
    super({}, 1, null);
  }

  get type(): string {
    return 'user-erasure-requests';
  }

  /**
   * Load flags into the application's cache if they haven't already
   * been loaded.
   */
  load(): Promise<void> {
    if (app.session.user?.attribute<number>('erasureRequestCount')) {
      this.pages = [];
      this.location = { page: 1 };
    }

    if (this.pages.length > 0) {
      return Promise.resolve();
    }

    return super.loadNext();
  }
}
