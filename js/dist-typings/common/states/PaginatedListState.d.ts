import Model from '../Model';
export interface Page<TModel> {
    number: number;
    items: TModel[];
    hasPrev?: boolean;
    hasNext?: boolean;
}
export interface PaginationLocation {
    page: number;
    startIndex?: number;
    endIndex?: number;
}
export default abstract class PaginatedListState<T extends Model> {
    protected location: PaginationLocation;
    protected pageSize: number;
    protected pages: Page<T>[];
    protected params: any;
    protected initialLoading: boolean;
    protected loadingPrev: boolean;
    protected loadingNext: boolean;
    protected constructor(params?: any, page?: number, pageSize?: number);
    abstract get type(): string;
    clear(): void;
    loadPrev(): Promise<void>;
    loadNext(): Promise<void>;
    protected parseResults(pg: number, results: T[]): void;
    /**
     * Load a new page of results.
     */
    protected loadPage(page?: number): Promise<T[]>;
    /**
     * Get the parameters that should be passed in the API request.
     * Do not include page offset unless subclass overrides loadPage.
     *
     * @abstract
     * @see loadPage
     */
    protected requestParams(): any;
    /**
     * Update the `this.params` object, calling `refresh` if they have changed.
     * Use `requestParams` for converting `this.params` into API parameters
     *
     * @param newParams
     * @param page
     * @see requestParams
     */
    refreshParams(newParams: any, page: number): any;
    refresh(page?: number): any;
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
    getParams(): any;
    protected getNextPageNumber(): number;
    protected getPrevPageNumber(): number;
    protected paramsChanged(newParams: any): boolean;
    protected getAllItems(): T[];
}
