import app from '../../forum/app';
import ItemList from '../../common/utils/ItemList';
import DiscussionsSearchSource from './DiscussionsSearchSource';
import UsersSearchSource from './UsersSearchSource';
import PostsSearchSource from './PostsSearchSource';
import AbstractSearch, { type SearchAttrs, type SearchSource as BaseSearchSource } from '../../common/components/AbstractSearch';

export interface SearchSource extends BaseSearchSource {}

export default class Search extends AbstractSearch {
  static initAttrs(attrs: SearchAttrs) {
    attrs.label = app.translator.trans('core.forum.header.search_placeholder', {}, true);
    attrs.a11yRoleLabel = app.translator.trans('core.forum.header.search_role_label', {}, true);
  }

  sourceItems(): ItemList<SearchSource> {
    const items = new ItemList<SearchSource>();

    if (app.forum.attribute('canViewForum')) {
      items.add('discussions', new DiscussionsSearchSource());
      items.add('posts', new PostsSearchSource());
    }

    if (app.forum.attribute('canSearchUsers')) {
      items.add('users', new UsersSearchSource());
    }

    return items;
  }
}
