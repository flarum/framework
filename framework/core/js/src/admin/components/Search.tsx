import ItemList from '../../common/utils/ItemList';
import AbstractSearch, { type SearchAttrs, type SearchSource as BaseSearchSource } from '../../common/components/AbstractSearch';
import GeneralSearchSource from './GeneralSearchSource';
import app from '../app';

export interface SearchSource extends BaseSearchSource {}

export default class Search extends AbstractSearch {
  static initAttrs(attrs: SearchAttrs) {
    attrs.label = app.translator.trans('core.admin.header.search_placeholder', {}, true);
    attrs.a11yRoleLabel = app.translator.trans('core.admin.header.search_role_label', {}, true);
  }

  sourceItems(): ItemList<SearchSource> {
    const items = new ItemList<SearchSource>();

    items.add('general', new GeneralSearchSource());

    return items;
  }
}
