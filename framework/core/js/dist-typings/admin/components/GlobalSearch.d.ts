import ItemList from '../../common/utils/ItemList';
import AbstractGlobalSearch, { type SearchAttrs, type GlobalSearchSource as BaseGlobalSearchSource } from '../../common/components/AbstractGlobalSearch';
export interface GlobalSearchSource extends BaseGlobalSearchSource {
}
export default class GlobalSearch extends AbstractGlobalSearch {
    static initAttrs(attrs: SearchAttrs): void;
    sourceItems(): ItemList<GlobalSearchSource>;
}
