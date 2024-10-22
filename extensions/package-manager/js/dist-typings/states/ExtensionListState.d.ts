import PaginatedListState, { SortMap } from 'flarum/common/states/PaginatedListState';
import ExternalExtension from '../models/ExternalExtension';
export default class ExtensionListState extends PaginatedListState<ExternalExtension> {
    get type(): string;
    constructor();
    sortMap(): SortMap;
}
