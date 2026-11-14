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
    /** The sort the list is in: the one chosen, or the first. */
    activeSort(): string | undefined;
    load(): Promise<void>;
    /**
     * Puts a dialog at the top of the list, as a new message in it does, without
     * reloading. A list that hasn't loaded yet is left alone: it loads fresh.
     *
     * @param limit Keep the first page to this many dialogs, for short lists like the dropdown's.
     */
    moveToTop(dialog: Dialog, limit?: number): void;
    /**
     * Shows a dialog that has a new message where the list's sort puts it,
     * without reloading. By the last message, that is the top. By when it was
     * started, a listed dialog stays where it is, and one not listed goes among
     * the loaded ones in order (ties by id, as the server breaks them), or is
     * left out when it falls past them. A sort this doesn't know leaves the list
     * as it is.
     *
     * @param limit Under the last-message sort, keep the first page to this many dialogs.
     */
    receive(dialog: Dialog, limit?: number): void;
    /** Takes a dialog off the list, as leaving it does, without reloading. */
    remove(dialog: Dialog): void;
    markAllAsRead(): Promise<void>;
}
