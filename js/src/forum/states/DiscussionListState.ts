import PaginatedListState from '../../common/states/PaginatedListState';
import Discussion from '../../common/models/Discussion';

export default class DiscussionListState extends PaginatedListState<Discussion> {
  constructor(params: any, page: number) {
    super(params, page, 20);
  }

  get type(): string {
    return 'discussions';
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

  /**
   * In the last request, has the user searched for a discussion?
   */
  isSearchResults() {
    return !!this.params.q;
  }

  removeDiscussion(discussion: Discussion) {
    for (const page of this.pages) {
      const index = page.items.indexOf(discussion);

      if (index !== -1) {
        page.items.splice(index, 1);
        break;
      }
    }

    m.redraw();
  }

  /**
   * Add a discussion to the top of the list.
   */
  addDiscussion(discussion: Discussion) {
    // TODO: do we want an extra field for the added discussions that is cleared on refresh?
    // that way we don't do weird stuff with pagination.
    // We can have an extra method that adds the extra field to the array of discussions returned.
    const page = this.pages[0];

    if (page) {
      page.items.unshift(discussion);

      m.redraw();
    }
  }

  /**
   * Are there discussions stored in the discussion list state?
   *
   * @see isEmpty
   * @deprecated
   */
  hasDiscussions(): boolean {
    return this.hasItems();
  }

  /**
   * Have the search results come up empty?
   * @deprecated
   */
  empty(): boolean {
    return this.isEmpty();
  }
}
