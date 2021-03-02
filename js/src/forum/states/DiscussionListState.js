export default class DiscussionListState {
  constructor(params = {}, app = window.app) {
    this.params = params;

    this.app = app;

    this.discussions = [];

    this.moreResults = false;

    this.loading = false;
  }

  /**
   * Get the parameters that should be passed in the API request to get
   * discussion results.
   *
   * @api
   */
  requestParams() {
    const params = { include: ['user', 'lastPostedUser'], filter: {} };

    params.sort = this.sortMap()[this.params.sort];

    if (this.params.q) {
      params.filter.q = this.params.q;

      params.include.push('mostRelevantPost', 'mostRelevantPost.user');
    }

    return params;
  }

  /**
   * Get a map of sort keys (which appear in the URL, and are used for
   * translation) to the API sort value that they represent.
   */
  sortMap() {
    const map = {};

    if (this.params.q) {
      map.relevance = '';
    }
    map.latest = '-lastPostedAt';
    map.top = '-commentCount';
    map.newest = '-createdAt';
    map.oldest = 'createdAt';

    return map;
  }

  /**
   * Get the search parameters.
   */
  getParams() {
    return this.params;
  }

  /**
   * Clear cached discussions.
   */
  clear() {
    this.discussions = [];
    m.redraw();
  }

  /**
   * If there are no cached discussions or the new params differ from the
   * old ones, update params and refresh the discussion list from the database.
   */
  refreshParams(newParams) {
    if (!this.hasDiscussions() || Object.keys(newParams).some((key) => this.getParams()[key] !== newParams[key])) {
      this.params = newParams;

      this.refresh();
    }
  }

  /**
   * Clear and reload the discussion list. Passing the option `deferClear: true`
   * will clear discussions only after new data has been received.
   * This can be used to refresh discussions without loading animations.
   */
  refresh({ deferClear = false } = {}) {
    this.loading = true;

    if (!deferClear) {
      this.clear();
    }

    return this.loadResults().then(
      (results) => {
        // This ensures that any changes made while waiting on this request
        // are ignored. Otherwise, we could get duplicate discussions.
        // We don't use `this.clear()` to avoid an unnecessary redraw.
        this.discussions = [];
        this.parseResults(results);
      },
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }

  /**
   * Load a new page of discussion results.
   *
   * @param offset The index to start the page at.
   */
  loadResults(offset) {
    const preloadedDiscussions = this.app.preloadedApiDocument();

    if (preloadedDiscussions) {
      return Promise.resolve(preloadedDiscussions);
    }

    const params = this.requestParams();
    params.page = { offset };
    params.include = params.include.join(',');

    return this.app.store.find('discussions', params);
  }

  /**
   * Load the next page of discussion results.
   */
  loadMore() {
    this.loading = true;

    this.loadResults(this.discussions.length).then(this.parseResults.bind(this));
  }

  /**
   * Parse results and append them to the discussion list.
   */
  parseResults(results) {
    this.discussions.push(...results);

    this.loading = false;
    this.moreResults = !!results.payload.links && !!results.payload.links.next;

    m.redraw();

    return results;
  }

  /**
   * Remove a discussion from the list if it is present.
   */
  removeDiscussion(discussion) {
    const index = this.discussions.indexOf(discussion);

    if (index !== -1) {
      this.discussions.splice(index, 1);
    }

    m.redraw();
  }

  /**
   * Add a discussion to the top of the list.
   */
  addDiscussion(discussion) {
    this.discussions.unshift(discussion);
    m.redraw();
  }

  /**
   * Are there discussions stored in the discussion list state?
   */
  hasDiscussions() {
    return this.discussions.length > 0;
  }

  /**
   * Are discussions currently being loaded?
   */
  isLoading() {
    return this.loading;
  }

  /**
   * In the last request, has the user searched for a discussion?
   */
  isSearchResults() {
    return !!this.params.q;
  }

  /**
   * Have the search results come up empty?
   */
  empty() {
    return !this.hasDiscussions() && !this.isLoading();
  }
}
