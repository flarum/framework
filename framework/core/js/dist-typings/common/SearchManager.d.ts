import SearchState from './states/SearchState';
import GambitManager from './GambitManager';
export default class SearchManager<State extends SearchState = SearchState> {
    /**
     * The minimum query length before sources are searched.
     */
    static MIN_SEARCH_LEN: number;
    /**
     * An object which stores previously searched queries and provides convenient
     * tools for retrieving and managing search values.
     */
    state: State;
    /**
     * The gambit manager that will convert search query gambits
     * into API filters.
     */
    gambits: GambitManager;
    constructor(state: State);
}
