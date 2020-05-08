import Discussion from '../../common/models/Discussion';

export default class DiscussionListState {
  constructor({ params = {} } = {}) {
    this.params = params;

    this.discussions = [];

    this.moreResults = false;

    this.loading = true;

    this.refresh();
  }

  setParams(params) {
    this.params = params;
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
   * Clear and reload the discussion list.
   */
  refresh(clear = true, clearParams = false) {
    if (clear) {
      this.loading = true;
      this.discussions = [];
    }

    if (clearParams) {
      this.params = {};
    }

    return this.loadResults().then(
      (results) => {
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
    const preloadedDiscussions = app.preloadedApiDocument();

    if (preloadedDiscussions) {
      return Promise.resolve(preloadedDiscussions);
    }

    const params = this.requestParams();
    params.page = { offset };
    params.include = params.include.join(',');

    return app.store.find('discussions', params);
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
  }

  /**
   * Add a discussion to the top of the list.
   */
  addDiscussion(discussion) {
    this.discussions.unshift(discussion);
  }
}
