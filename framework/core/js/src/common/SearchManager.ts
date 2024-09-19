import SearchState from './states/SearchState';
import GambitManager from './GambitManager';

export default class SearchManager<State extends SearchState = SearchState> {
  /**
   * The minimum query length before sources are searched.
   */
  public static MIN_SEARCH_LEN = 3;

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
