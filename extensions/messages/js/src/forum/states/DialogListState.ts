import app from 'flarum/forum/app';
import PaginatedListState, { PaginatedListParams, type SortMap } from 'flarum/common/states/PaginatedListState';
import Dialog from '../../common/models/Dialog';
import { type PaginatedListRequestParams } from 'flarum/common/states/PaginatedListState';

export interface DialogListParams extends PaginatedListParams {
  sort?: string;
}

export default class DialogListState<P extends DialogListParams = DialogListParams> extends PaginatedListState<Dialog, P> {
  protected lastCount: number = 0;

  constructor(params: P, page: number = 1, perPage: null | number = null) {
    super(params, page, perPage);
  }

  get type(): string {
    return 'dialogs';
  }

  public getAllItems(): Dialog[] {
    return super.getAllItems();
  }

  requestParams(): PaginatedListRequestParams {
    const params = {
      include: ['lastMessage.user', 'users.groups', 'groups'],
      filter: this.params.filter || {},
      sort: this.activeSort(),
    };

    return params;
  }

  sortMap(): SortMap {
    const map: any = {};

    map.latest = '-lastMessageAt';
    map.newest = '-createdAt';
    map.oldest = 'createdAt';

    return map;
  }

  /** The sort the list is in: the one chosen, or the first. */
  activeSort(): string | undefined {
    return this.currentSort() || this.sortValue(Object.values(this.sortMap())[0]);
  }

  load(): Promise<void> {
    if (app.session.user?.attribute<number>('unreadDialogCount') !== this.lastCount) {
      this.pages = [];
      this.location = { page: 1 };

      this.lastCount = app.session.user?.attribute<number>('unreadDialogCount') || 0;
    }

    if (this.pages.length > 0) {
      return Promise.resolve();
    }

    return super.loadNext();
  }

  /**
   * Puts a dialog at the top of the list, as a new message in it does, without
   * reloading. A list that hasn't loaded yet is left alone: it loads fresh.
   *
   * @param limit Keep the first page to this many dialogs, for short lists like the dropdown's.
   */
  moveToTop(dialog: Dialog, limit?: number): void {
    if (!this.pages.length) return;

    const id = dialog.id();

    this.pages.forEach((page) => {
      const index = page.items.findIndex((item) => item.id() === id);

      if (index !== -1) page.items.splice(index, 1);
    });

    this.pages[0].items.unshift(dialog);

    if (limit && this.pages[0].items.length > limit) this.pages[0].items.length = limit;
  }

  /**
   * Shows a dialog that has a new message where the list's sort puts it,
   * without reloading. By the last message, that is the top. By when it was
   * started, a listed dialog stays where it is, and one not listed goes among
   * the loaded ones in order (ties by id, as the server breaks them), or is
   * left out when it falls past them. A sort this doesn't know leaves the list
   * as it is.
   *
   * @param limit Under the last-message sort, keep the first page to this many dialogs.
   */
  receive(dialog: Dialog, limit?: number): void {
    if (!this.pages.length) return;

    const sort = this.activeSort();

    if (sort === '-lastMessageAt') return this.moveToTop(dialog, limit);

    if (sort !== 'createdAt' && sort !== '-createdAt') return;
    if (this.getAllItems().some((item) => item.id() === dialog.id())) return;

    const started = dialog.createdAt()?.getTime();
    if (started === undefined) return;

    const id = Number(dialog.id());
    const comesBefore = (item: Dialog) => {
      const itemStarted = item.createdAt()?.getTime() ?? 0;
      const order = started - itemStarted || id - Number(item.id());

      return sort === 'createdAt' ? order < 0 : order > 0;
    };

    for (const page of this.pages) {
      const index = page.items.findIndex(comesBefore);

      if (index !== -1) {
        page.items.splice(index, 0, dialog);
        return;
      }
    }

    if (!this.hasNext()) this.pages[this.pages.length - 1].items.push(dialog);
  }

  /** Takes a dialog off the list, as leaving it does, without reloading. */
  remove(dialog: Dialog): void {
    this.pages.forEach((page) => {
      page.items = page.items.filter((item) => item !== dialog);
    });
  }

  markAllAsRead() {
    return app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/dialogs/read',
      })
      .then(() => {
        app.dialogs.getAllItems().forEach((dialog: Dialog) => {
          dialog.pushAttributes({ unreadCount: 0 });
        });
        app.session.user!.pushAttributes({ unreadDialogCount: 0 });
        app.dropdownDialogs.clear();
        m.redraw();
      });
  }
}
