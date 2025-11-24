import PaginatedListState from 'flarum/common/states/PaginatedListState';
import type ErasureRequest from '../../common/models/ErasureRequest';
export default class ErasureRequestsListState extends PaginatedListState<ErasureRequest> {
    constructor();
    get type(): string;
    /**
     * Load flags into the application's cache if they haven't already
     * been loaded.
     */
    load(): Promise<void>;
}
