import SearchState from './states/SearchState';
import GambitManager from './GambitManager';
export default class SearchManager<State extends SearchState = SearchState> {
    /**
     * The default minimum query length. The forum's configured minimum
     * (`search_min_length`) takes precedence once the forum is loaded; admin
     * settings search never requires more than this.
     */
    static MIN_SEARCH_LEN: number;
    /**
     * Whether experimental CJK search mode is enabled.
     */
    static isCjkMode(): boolean;
    /**
     * The minimum query length before sources are searched, as configured by the
     * forum's admin (`search_min_length`).
     */
    static minSearchLength(): number;
    /**
     * The length of a query in characters, as compared against the minimum.
     * Surrounding whitespace is not counted, since it is trimmed before the
     * search is sent. Counts code points, so a character outside the Basic
     * Multilingual Plane (such as some CJK ideographs) counts once, not twice.
     */
    static queryLength(query: string): number;
    /**
     * Time to wait (in milliseconds) after the user stops typing before triggering a search.
     */
    static SEARCH_DEBOUNCE_TIME_MS: number;
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
