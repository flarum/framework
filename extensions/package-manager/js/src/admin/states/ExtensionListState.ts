import app from 'flarum/admin/app';
import PaginatedListState, { SortMap } from 'flarum/common/states/PaginatedListState';
import ExternalExtension from '../models/ExternalExtension';

export default class ExtensionListState extends PaginatedListState<ExternalExtension> {
  get type(): string {
    return 'external-extensions';
  }

  constructor() {
    super(
      {
        sort: '-downloads',
      },
      1,
      12
    );
  }

  sortMap(): SortMap {
    return {
      '-createdAt': {
        sort: '-createdAt',
        label: app.translator.trans('flarum-extension-manager.admin.sections.discover.sort.latest', {}, true),
      },
      '-downloads': {
        sort: '-downloads',
        label: app.translator.trans('flarum-extension-manager.admin.sections.discover.sort.top', {}, true),
      },
    };
  }
}
