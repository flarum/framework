import ItemList from '../../common/utils/ItemList';
import AbstractSearch, { type SearchAttrs, type SearchSource as BaseSearchSource } from '../../common/components/AbstractSearch';
export interface SearchSource extends BaseSearchSource {
}
export default class Search extends AbstractSearch {
    static initAttrs(attrs: SearchAttrs): void;
    sourceItems(): ItemList<SearchSource>;
}
