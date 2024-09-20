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
      include: ['lastMessage', 'users.groups'],
      filter: this.params.filter || {},
      sort: this.currentSort() || this.sortValue(Object.values(this.sortMap())[0]),
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

  load(): Promise<void> {
    if (app.session.user?.attribute<number>('messageCount') !== this.lastCount) {
      this.pages = [];
      this.location = { page: 1 };

      this.lastCount = app.session.user?.attribute<number>('messageCount') || 0;
    }

    if (this.pages.length > 0) {
      return Promise.resolve();
    }

    return super.loadNext();
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
        app.session.user!.pushAttributes({ messageCount: 0 });
        app.dropdownDialogs.clear();
        m.redraw();
      });
  }
}
