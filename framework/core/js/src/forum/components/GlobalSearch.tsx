import app from '../../forum/app';
import ItemList from '../../common/utils/ItemList';
import GlobalDiscussionsSearchSource from './GlobalDiscussionsSearchSource';
import GlobalUsersSearchSource from './GlobalUsersSearchSource';
import GlobalPostsSearchSource from './GlobalPostsSearchSource';
import AbstractGlobalSearch, {
  type SearchAttrs as BaseSearchAttrs,
  type GlobalSearchSource as BaseGlobalSearchSource,
} from '../../common/components/AbstractGlobalSearch';

export interface GlobalSearchSource extends BaseGlobalSearchSource {}

export interface SearchAttrs extends BaseSearchAttrs {}

export default class GlobalSearch<Attrs extends SearchAttrs = SearchAttrs> extends AbstractGlobalSearch<Attrs> {
  static initAttrs(attrs: SearchAttrs) {
    attrs.label = app.translator.trans('core.forum.header.search_placeholder', {}, true);
    attrs.a11yRoleLabel = app.translator.trans('core.forum.header.search_role_label', {}, true);
  }

  sourceItems(): ItemList<GlobalSearchSource> {
    const items = new ItemList<GlobalSearchSource>();

    if (app.forum.attribute('canViewForum')) {
      items.add('discussions', new GlobalDiscussionsSearchSource());
      items.add('posts', new GlobalPostsSearchSource());
    }

    if (app.forum.attribute('canSearchUsers')) {
      items.add('users', new GlobalUsersSearchSource());
    }

    return items;
  }
}
