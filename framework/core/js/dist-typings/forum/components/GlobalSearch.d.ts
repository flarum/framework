import ItemList from '../../common/utils/ItemList';
import AbstractGlobalSearch, { type SearchAttrs as BaseSearchAttrs, type GlobalSearchSource as BaseGlobalSearchSource } from '../../common/components/AbstractGlobalSearch';
export interface GlobalSearchSource extends BaseGlobalSearchSource {
}
export interface SearchAttrs extends BaseSearchAttrs {
}
export default class GlobalSearch<Attrs extends SearchAttrs = SearchAttrs> extends AbstractGlobalSearch<Attrs> {
    static initAttrs(attrs: SearchAttrs): void;
    sourceItems(): ItemList<GlobalSearchSource>;
}
