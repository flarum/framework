import PaginatedListState from '../../common/states/PaginatedListState';
import Discussion from '../../common/models/Discussion';

export default class DiscussionListState extends PaginatedListState<Discussion> {
  constructor(params: any, page: number) {
    super('discussions', 20);

    this.params = params;

    this.location = { page };
  }

  requestParams() {
    const params: any = { include: ['user', 'lastPostedUser'], filter: {} };

    params.sort = this.sortMap()[this.params.sort];

    if (this.params.q) {
      params.filter.q = this.params.q;

      params.include.push('mostRelevantPost', 'mostRelevantPost.user');
    }
    return params;
  }

  /**
   * In the last request, has the user searched for a discussion?
   */
  isSearchResults() {
    return !!this.params.q;
  }

  protected loadPage(page: number = 1): any {
    const preloadedDiscussions = app.preloadedApiDocument();

    if (preloadedDiscussions) {
      this.initialLoading = false;

      return Promise.resolve(preloadedDiscussions);
    }

    return super.loadPage(page);
  }

  /**
   * Get a map of sort keys (which appear in the URL, and are used for
   * translation) to the API sort value that they represent.
   */
  sortMap() {
    const map: any = {};

    if (this.params.q) {
      map.relevance = '';
    }
    map.latest = '-lastPostedAt';
    map.top = '-commentCount';
    map.newest = '-createdAt';
    map.oldest = 'createdAt';

    return map;
  }
}
