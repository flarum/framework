import type ForumApplication from 'flarum/forum/ForumApplication';
import type Flag from '../models/Flag';
import type Post from 'flarum/common/models/Post';

export default class FlagListState {
  public app: ForumApplication;
  public loading = false;
  public cache: Flag[] | null = null;
  public index: Post | false | null = null;

  constructor(app: ForumApplication) {
    this.app = app;
  }

  /**
   * Load flags into the application's cache if they haven't already
   * been loaded.
   */
  load() {
    if (this.cache && !this.app.session.user!.attribute<number>('newFlagCount')) {
      return;
    }

    this.loading = true;
    m.redraw();

    this.app.store
      .find<Flag[]>('flags')
      .then((flags) => {
        this.app.session.user!.pushAttributes({ newFlagCount: 0 });
        this.cache = flags.sort((a, b) => b.createdAt()!.getTime() - a.createdAt()!.getTime());
      })
      .catch(() => {})
      .then(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
