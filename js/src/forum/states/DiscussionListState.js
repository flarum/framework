import Pagination from '../../common/utils/Pagination';

export default class DiscussionListState {
  static DISCUSSIONS_PER_PAGE = 20;

  constructor(params = {}, app = window.app) {
    this.params = params;

    this.app = app;

    this.discussions = [];

    this.moreResults = false;

    this.pagination = new Pagination(this.load.bind(this));
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
    this.pagination.loading.next = true;

    if (!deferClear) {
      this.clear();
    }

    return this.pagination.refresh(Number(m.route.param('page')) || 1).then(this.parse.bind(this));
  }

  load(page) {
    const preloadedDiscussions = this.app.preloadedApiDocument();

    if (preloadedDiscussions) {
      return Promise.resolve(preloadedDiscussions);
    }

    const params = this.requestParams();
    params.page = { offset: DiscussionListState.DISCUSSIONS_PER_PAGE * (page - 1) };
    params.include = params.include.join(',');

    return this.app.store.find('discussions', params);
  }

  loadPrev() {
    return this.pagination.loadPrev().then(this.parse.bind(this));
  }

  loadMore() {
    return this.pagination.loadNext().then(this.parse.bind(this));
  }

  /**
   * Parse results and append them to the discussion list.
   */
  parse() {
    const discussions = [];
    const { first, last } = this.pagination.pages;

    for (let page = first; page <= last; page++) {
      const results = this.pagination.data[page];

      if (Array.isArray(results)) discussions.push(...results);
    }

    this.discussions = discussions;

    const results = this.pagination.data[last];
    this.moreResults = !!results.payload.links.next;

    m.redraw();

    return discussions;
  }

  /**
   * Remove a discussion from the list if it is present.
   */
  removeDiscussion(discussion) {
    Object.keys(this.pagination.data).forEach((key) => {
      const index = this.pagination.data[key].indexOf(discussion);

      this.pagination.data[key].splice(index, 1);
    });

    this.parse();

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
    return this.pagination.loading.next;
  }

  isLoadingPrev() {
    return this.pagination.loading.prev;
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
