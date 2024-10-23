import type ForumApplication from 'flarum/forum/ForumApplication';
import type Flag from '../models/Flag';
import PaginatedListState from 'flarum/common/states/PaginatedListState';

export default class FlagListState extends PaginatedListState<Flag> {
  public app: ForumApplication;

  constructor(app: ForumApplication) {
    super({}, 1, null);
    this.app = app;
  }

  get type(): string {
    return 'flags';
  }

  /**
   * Load flags into the application's cache if they haven't already
   * been loaded.
   */
  load(): Promise<void> {
    if (this.app.session.user?.attribute<number>('newFlagCount')) {
      this.pages = [];
      this.location = { page: 1 };
    }

    if (this.pages.length > 0) {
      return Promise.resolve();
    }

    return super.loadNext();
  }
}
