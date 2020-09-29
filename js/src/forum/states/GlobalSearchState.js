import setRouteWithForcedRefresh from '../../common/utils/setRouteWithForcedRefresh';
import SearchState from './SearchState';

export default class GlobalSearchState extends SearchState {
  constructor(cachedSearches = []) {
    super(cachedSearches);
  }

  getValue() {
    if (this.value === undefined) {
      this.value = this.getInitialSearch() || '';
    }

    return super.getValue();
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
   * Get URL parameters that stick between filter changes.
   *
   * @return {Object}
   */
  stickyParams() {
    return {
      sort: m.route.param('sort'),
      q: m.route.param('q'),
    };
  }

  /**
   * Get parameters to pass to the DiscussionList component.
   *
   * @return {Object}
   */
  params() {
    const params = this.stickyParams();

    params.filter = m.route.param('filter');

    return params;
  }

  /**
   * Redirect to the index page using the given sort parameter.
   *
   * @param {String} sort
   */
  changeSort(sort) {
    const params = this.params();

    if (sort === Object.keys(app.discussions.sortMap())[0]) {
      delete params.sort;
    } else {
      params.sort = sort;
    }

    setRouteWithForcedRefresh(app.route(app.current.get('routeName'), params));
  }

  /**
   * Return the current search query, if any. This is implemented to activate
   * the search box in the header.
   *
   * @see Search
   * @return {String}
   */
  getInitialSearch() {
    return app.current.type && app.current.type.providesInitialSearch && this.params().q;
  }

  /**
   * Redirect to the index page without a search filter. This is called when the
   * 'x' is clicked in the search box in the header.
   *
   * @see Search
   */
  clearInitialSearch() {
    const params = this.params();
    delete params.q;

    setRouteWithForcedRefresh(app.route(app.current.get('routeName'), params));
  }
}
