import SearchState from './states/SearchState';
import type Mithril from 'mithril';
import GambitManager from './GambitManager';
import DiscussionsSearchSource from './query/DiscussionsSearchSource';
import UsersSearchSource from './query/UsersSearchSource';
import ItemList from './utils/ItemList';
import app from '../forum/app';

/**
 * The `SearchSource` interface defines a section of search results in the
 * search dropdown.
 *
 * Search sources should be registered with the `Search` component class
 * by extending the `sourceItems` method. When the user types a
 * query, each search source will be prompted to load search results via the
 * `search` method. When the dropdown is redrawn, it will be constructed by
 * putting together the output from the `view` method of each source.
 */
export interface SearchSource {
  /**
   * The resource type that this search source is responsible for.
   */
  resource: string;

  /**
   * Get the title for this search source.
   */
  title(): string;

  /**
   * Check if a query has been cached for this search source.
   */
  isCached(query: string): boolean;

  /**
   * Make a request to get results for the given query.
   * The results will be updated internally in the search source, not exposed.
   */
  search(query: string, limit: number): Promise<void>;

  /**
   * Get an array of virtual <li>s that list the search results for the given
   * query.
   */
  view(query: string): Array<Mithril.Vnode>;

  /**
   * Get a list item for the full search results page.
   */
  fullPage(query: string): Mithril.Vnode | null;
}

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

  /**
   * A list of search sources that can be used to query for search results.
   */
  sources(): ItemList<SearchSource> {
    const items = new ItemList<SearchSource>();

    if (app.forum.attribute('canViewForum')) {
      items.add('discussions', new DiscussionsSearchSource());
    }

    if (app.forum.attribute('canSearchUsers')) {
      items.add('users', new UsersSearchSource());
    }

    return items;
  }
}
