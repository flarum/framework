import PaginatedListState, { PaginatedListParams, type SortMap } from 'flarum/common/states/PaginatedListState';
import Dialog from '../../common/models/Dialog';
import { type PaginatedListRequestParams } from 'flarum/common/states/PaginatedListState';
export interface DialogListParams extends PaginatedListParams {
    sort?: string;
}
export default class DialogListState<P extends DialogListParams = DialogListParams> extends PaginatedListState<Dialog, P> {
    protected lastCount: number;
    constructor(params: P, page?: number, perPage?: null | number);
    get type(): string;
    getAllItems(): Dialog[];
    requestParams(): PaginatedListRequestParams;
    sortMap(): SortMap;
    load(): Promise<void>;
    markAllAsRead(): Promise<void>;
}
