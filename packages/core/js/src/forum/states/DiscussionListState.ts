import PaginatedListState, { Page } from '../../common/states/PaginatedListState';
import Discussion from '../../common/models/Discussion';

export default class DiscussionListState extends PaginatedListState<Discussion> {
  protected extraDiscussions: Discussion[] = [];

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

  clear() {
    super.clear();

    this.extraDiscussions = [];
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

    const index = this.extraDiscussions.indexOf(discussion);

    if (index !== -1) {
      this.extraDiscussions.splice(index);
    }

    m.redraw();
  }

  /**
   * Add a discussion to the top of the list.
   */
  addDiscussion(discussion: Discussion) {
    this.removeDiscussion(discussion);
    this.extraDiscussions.unshift(discussion);

    m.redraw();
  }

  protected getAllItems(): Discussion[] {
    return this.extraDiscussions.concat(super.getAllItems());
  }

  public getPages(): Page<Discussion>[] {
    const pages = super.getPages();

    if (this.extraDiscussions.length) {
      return [
        {
          number: -1,
          items: this.extraDiscussions,
        },
        ...pages,
      ];
    }

    return pages;
  }
}
