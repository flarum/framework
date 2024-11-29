import ItemList from '../../common/utils/ItemList';
import AbstractGlobalSearch, {
  type SearchAttrs,
  type GlobalSearchSource as BaseGlobalSearchSource,
} from '../../common/components/AbstractGlobalSearch';
import GeneralSearchSource from './GeneralSearchSource';
import app from '../app';

export interface GlobalSearchSource extends BaseGlobalSearchSource {}

export default class GlobalSearch extends AbstractGlobalSearch {
  static initAttrs(attrs: SearchAttrs) {
    attrs.label = app.translator.trans('core.admin.header.search_placeholder', {}, true);
    attrs.a11yRoleLabel = app.translator.trans('core.admin.header.search_role_label', {}, true);
  }

  sourceItems(): ItemList<GlobalSearchSource> {
    const items = new ItemList<GlobalSearchSource>();

    items.add('general', new GeneralSearchSource());

    return items;
  }
}
