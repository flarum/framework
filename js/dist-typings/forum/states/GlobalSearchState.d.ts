import SearchState from './SearchState';
declare type SearchParams = Record<string, string>;
export default class GlobalSearchState extends SearchState {
    private initialValueSet;
    constructor(cachedSearches?: never[]);
    getValue(): string;
    protected intializeValue(): void;
    protected currPageProvidesSearch(): boolean;
    /**
     * @inheritdoc
     */
    getInitialSearch(): string;
    /**
     * Clear the search input and the current controller's active search.
     */
    clear(): void;
    /**
     * Redirect to the index page without a search filter. This is called when the
     * 'x' is clicked in the search box in the header.
     */
    protected clearInitialSearch(): void;
    /**
     * Get URL parameters that stick between filter changes.
     *
     * This can be used to generate a link that clears filters.
     */
    stickyParams(): SearchParams;
    /**
     * Get parameters to be used in the current page.
     */
    params(): SearchParams;
    /**
     * Redirect to the index page using the given sort parameter.
     */
    changeSort(sort: string): void;
}
export {};
