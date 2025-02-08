import type Model from '../Model';
import type { ApiQueryParamsPlural, ApiResponsePlural } from '../Store';
import type Mithril from 'mithril';
export type SortMapItem = string | {
    sort: string;
    label: Mithril.Children;
};
export type SortMap = {
    [key: string]: SortMapItem;
};
export interface Page<TModel extends Model> {
    number: number;
    items: ApiResponsePlural<TModel> | TModel[];
    hasPrev?: boolean;
    hasNext?: boolean;
}
export interface PaginationLocation {
    page: number;
    startIndex?: number;
    endIndex?: number;
}
export interface PaginatedListParams {
    [key: string]: any;
}
export interface PaginatedListRequestParams extends Omit<ApiQueryParamsPlural, 'include'> {
    include?: string | string[];
}
export default abstract class PaginatedListState<T extends Model, P extends PaginatedListParams = PaginatedListParams> {
    /**
     * This value should not be relied upon when preloading an API document.
     * In those cases the pageSize should be taken from the meta information of the preloaded
     * document. Checkout `DiscussionListState.loadPage` for an example.
     */
    static DEFAULT_PAGE_SIZE: number;
    protected location: PaginationLocation;
    pageSize: number | null;
    totalItems: number | null;
    protected pages: Page<T>[];
    protected params: P;
    protected initialLoading: boolean;
    protected loadingPrev: boolean;
    protected loadingNext: boolean;
    protected loadingPage: boolean;
    protected constructor(params?: P, page?: number, pageSize?: number | null);
    abstract get type(): string;
    clear(): void;
    loadPrev(): Promise<void>;
    loadNext(): Promise<void>;
    protected parseResults(pg: number, results: ApiResponsePlural<T>): void;
    /**
     * Load a new page of results.
     */
    protected loadPage(page?: number): Promise<ApiResponsePlural<T>>;
    protected mutateRequestParams(params: ApiQueryParamsPlural, page: number): ApiQueryParamsPlural;
    /**
     * Get the parameters that should be passed in the API request.
     * Do not include page offset unless subclass overrides loadPage.
     *
     * @abstract
     * @see loadPage
     */
    protected requestParams(): PaginatedListRequestParams;
    /**
     * Update the `this.params` object, calling `refresh` if they have changed.
     * Use `requestParams` for converting `this.params` into API parameters
     *
     * @param newParams
     * @param page
     * @see requestParams
     */
    refreshParams(newParams: P, page: number): Promise<void>;
    refresh(page?: number): Promise<void>;
    goto(page: number): Promise<void>;
    getPages(): Page<T>[];
    getLocation(): PaginationLocation;
    isLoading(): boolean;
    isInitialLoading(): boolean;
    isLoadingPrev(): boolean;
    isLoadingNext(): boolean;
    /**
     * Returns true when the number of items across all loaded pages is not 0.
     *
     * @see isEmpty
     */
    hasItems(): boolean;
    /**
     * Returns true when there aren't any items *and* the state has already done its initial loading.
     * If you want to know whether there are items regardless of load state, use `hasItems()` instead
     *
     * @see hasItems
     */
    isEmpty(): boolean;
    hasPrev(): boolean;
    hasNext(): boolean;
    /**
     * Stored state parameters.
     */
    getParams(): P;
    protected getNextPageNumber(): number;
    protected getPrevPageNumber(): number;
    protected paramsChanged(newParams: P): boolean;
    protected getAllItems(): T[];
    /**
     * In the last request, has the user searched for a model?
     */
    isSearchResults(): boolean;
    push(model: T): void;
    getSort(): string;
    sortMap(): SortMap;
    sortValue(sort: SortMapItem): string | undefined;
    currentSort(): string | undefined;
    changeSort(sort: string): void;
    changeFilter(key: string, value: any): void;
}
