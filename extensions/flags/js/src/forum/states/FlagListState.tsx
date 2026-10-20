import type ForumApplication from 'flarum/forum/ForumApplication';
import type Flag from '../models/Flag';
import PaginatedListState from 'flarum/common/states/PaginatedListState';
import type User from 'flarum/common/models/User';

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

  /** Remove an account's reports only after the server confirms dismissal. */
  dismissUser(user: User): Promise<void> {
    const id = user.id();
    if (!id) return Promise.reject(new Error('Cannot dismiss flags on an unsaved user.'));

    return this.app
      .request({
        url: this.app.forum.attribute('apiUrl') + '/users/' + encodeURIComponent(id) + '/flags',
        method: 'DELETE',
      })
      .then(() => {
        const ownFlags = user.flags();
        const hasReports =
          (ownFlags && ownFlags.length > 0) || this.pages.some((page) => page.items.some((flag) => (flag.targetUser() || null)?.id() === user.id()));

        this.pages.forEach((page) => {
          page.items = page.items.filter((flag) => (flag.targetUser() || null)?.id() !== user.id());
        });
        user.pushData({ relationships: { flags: [] } });

        if (hasReports) {
          const count = this.app.forum.attribute<number>('flagCount');
          if (typeof count === 'number') this.app.forum.pushAttributes({ flagCount: Math.max(0, count - 1) });
        }

        m.redraw();
      });
  }
}
