import app from './app';
import SearchState from './states/SearchState';
import GambitManager from './GambitManager';

export default class SearchManager<State extends SearchState = SearchState> {
  /**
   * The minimum query length before sources are searched.
   */
  public static MIN_SEARCH_LEN = 3;

  /**
   * Whether experimental CJK search mode is enabled.
   */
  public static isCjkMode(): boolean {
    const settings = app.data.settings as Record<string, unknown> | undefined;

    return (settings?.['search_cjk_mode'] as unknown as boolean | undefined) ?? false;
  }

  /**
   * The minimum query length for the current forum. Experimental CJK search mode
   * drops it to 1, since a single character is often a whole word in languages
   * without word spaces; the substring match backing that mode has no minimum.
   */
  public static minSearchLength(): number {
    return this.isCjkMode() ? 1 : this.MIN_SEARCH_LEN;
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
