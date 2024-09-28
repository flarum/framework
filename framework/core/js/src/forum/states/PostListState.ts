import app from '../../forum/app';
import PaginatedListState, { Page, PaginatedListParams, PaginatedListRequestParams } from '../../common/states/PaginatedListState';
import Post from '../../common/models/Post';
import { ApiResponsePlural } from '../../common/Store';
import EventEmitter from '../../common/utils/EventEmitter';

export interface PostListParams extends PaginatedListParams {
  sort?: string;
}

const globalEventEmitter = new EventEmitter();

export default class PostListState<P extends PostListParams = PostListParams> extends PaginatedListState<Post, P> {
  protected extraPosts: Post[] = [];
  protected eventEmitter: EventEmitter;

  constructor(params: P, page: number = 1, pageSize: number | null = null) {
    super(params, page, pageSize);

    this.eventEmitter = globalEventEmitter;
  }

  get type(): string {
    return 'posts';
  }

  requestParams(): PaginatedListRequestParams {
    const params = {
      include: ['user', 'discussion'],
      filter: {
        type: 'comment',
        ...(this.params.filter || {}),
      },
      sort: this.sortMap()[this.params.sort ?? ''] || '-createdAt',
    };

    if (this.params.q) {
      params.filter.q = this.params.q;
    }

    return params;
  }

  protected loadPage(page: number = 1): Promise<ApiResponsePlural<Post>> {
    const preloadedPosts = app.preloadedApiDocument<Post[]>();

    if (preloadedPosts) {
      this.initialLoading = false;
      this.pageSize = preloadedPosts.payload.meta?.perPage || PostListState.DEFAULT_PAGE_SIZE;

      return Promise.resolve(preloadedPosts);
    }

    return super.loadPage(page);
  }

  clear(): void {
    super.clear();

    this.extraPosts = [];
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

    map.newest = '-createdAt';
    map.oldest = 'createdAt';

    return map;
  }

  protected getAllItems(): Post[] {
    return this.extraPosts.concat(super.getAllItems());
  }

  public getPages(): Page<Post>[] {
    const pages = super.getPages();

    if (this.extraPosts.length) {
      return [
        {
          number: -1,
          items: this.extraPosts,
        },
        ...pages,
      ];
    }

    return pages;
  }
}
