import app from './app';
import SearchState from './states/SearchState';
import GambitManager from './GambitManager';

export default class SearchManager<State extends SearchState = SearchState> {
  /**
   * The default minimum query length. The forum's configured minimum
   * (`search_min_length`) takes precedence once the forum is loaded; admin
   * settings search never requires more than this.
   */
  public static MIN_SEARCH_LEN = 3;

  /**
   * Whether experimental CJK search mode is enabled.
   */
  public static isCjkMode(): boolean {
    return app.forum?.attribute<boolean>('searchCjkMode') ?? false;
  }

  /**
   * The minimum query length before sources are searched, as configured by the
   * forum's admin (`search_min_length`).
   */
  public static minSearchLength(): number {
    return app.forum?.attribute<number>('searchMinLength') ?? this.MIN_SEARCH_LEN;
  }

  /**
   * The length of a query in characters, as compared against the minimum.
   * Surrounding whitespace is not counted, since it is trimmed before the
   * search is sent. Counts code points, so a character outside the Basic
   * Multilingual Plane (such as some CJK ideographs) counts once, not twice.
   */
  public static queryLength(query: string): number {
    return Array.from(query.trim()).length;
  }

  /**
   * Time to wait (in milliseconds) after the user stops typing before triggering a search.
   */
  public static SEARCH_DEBOUNCE_TIME_MS = 250;

  /**
   * An object which stores previously searched queries and provides convenient
   * tools for retrieving and managing search values.
   */
  public state: State;

  /**
   * The gambit manager that will convert search query gambits
   * into API filters.
   */
  public gambits = new GambitManager();

  constructor(state: State) {
    this.state = state;
  }
}
