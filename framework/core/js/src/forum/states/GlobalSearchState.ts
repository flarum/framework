import app from '../../forum/app';
import setRouteWithForcedRefresh from '../../common/utils/setRouteWithForcedRefresh';
import SearchState from '../../common/states/SearchState';

type SearchParams = Record<string, string>;

export default class GlobalSearchState extends SearchState {
  private initialValueSet = false;

  constructor(cachedSearches = []) {
    super(cachedSearches);
  }

  getValue(): string {
    // If we are on a search results page, we should initialize the value
    // from the current search, if one is present.
    // We can't do this in the constructor, as this class is instantiated
    // before pages are rendered, and we need app.current.
    if (!this.initialValueSet && this.currPageProvidesSearch()) {
      this.intializeValue();
    }

    return super.getValue();
  }

  protected intializeValue() {
    this.setValue(this.getInitialSearch());
    this.initialValueSet = true;
  }

  protected currPageProvidesSearch(): boolean {
    return app.current.type && 'providesInitialSearch' in app.current.type && (app.current.type as any).providesInitialSearch;
  }

  /**
   * @inheritdoc
   */
  getInitialSearch(): string {
    return this.currPageProvidesSearch() ? this.searchToQuery() : '';
  }

  private searchToQuery(): string {
    const q = this.params().q || '';
    const filter = this.params().filter || {};

    return app.search.gambits.from('users', app.search.gambits.from('discussions', q, filter), filter).trim();
  }

  /**
   * Clear the search input and the current controller's active search.
   */
  clear() {
    super.clear();

    if (this.getInitialSearch()) {
      this.clearInitialSearch();
    } else {
      m.redraw();
    }
  }

  /**
   * Redirect to the index page without a search filter. This is called when the
   * 'x' is clicked in the search box in the header.
   */
  protected clearInitialSearch() {
    const { q, filter, ...params } = this.params();

    setRouteWithForcedRefresh(app.route(app.current.get('routeName'), params));
  }

  /**
   * Get URL parameters that stick between filter changes.
   *
   * This can be used to generate a link that clears filters.
   */
  stickyParams(): SearchParams {
    return {
      sort: m.route.param('sort'),
      q: m.route.param('q'),
      // Objects must be copied, otherwise they are passed by reference.
      // Which could end up undesirably modifying the mithril route params.
      filter: Object.assign({}, m.route.param('filter')),
    };
  }

  /**
   * Get parameters to be used in the current page.
   */
  params(): SearchParams {
    const params = this.stickyParams();

    return params;
  }

  /**
   * Redirect to the index page using the given sort parameter.
   */
  changeSort(sort: string) {
    const params = this.params();

    if (sort === Object.keys(app.discussions.sortMap())[0]) {
      delete params.sort;
    } else {
      params.sort = sort;
    }

    setRouteWithForcedRefresh(app.route(app.current.get('routeName'), params));
  }
}
