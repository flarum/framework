export default class SearchState {
    protected cachedSearches: Set<string>;
    protected value: string;
    constructor(cachedSearches?: string[]);
    /**
     * If we are displaying the full results of a search (not just a preview),
     * this value should return the query that prompted that search.
     *
     * In this generic class, full page searching is not supported.
     * This method should be implemented by subclasses that do support it.
     *
     * @see Search
     */
    getInitialSearch(): string;
    getValue(): string;
    setValue(value: string): void;
    /**
     * Clear the search value.
     */
    clear(): void;
    /**
     * Mark that we have already searched for this query so that we don't
     * have to ping the endpoint again.
     */
    cache(query: string): void;
    /**
     * Check if this query has been searched before.
     */
    isCached(query: string): boolean;
}
