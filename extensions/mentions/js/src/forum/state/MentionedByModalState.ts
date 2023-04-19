import PaginatedListState, { PaginatedListParams } from 'flarum/common/states/PaginatedListState';
import Post from 'flarum/common/models/Post';

export interface MentionedByModalListParams extends PaginatedListParams {
  filter: {
    mentionedPost: string;
  };
  sort?: string;
  page?: {
    offset?: number;
    limit: number;
  };
}

export default class MentionedByModalState<P extends MentionedByModalListParams = MentionedByModalListParams> extends PaginatedListState<Post, P> {
  constructor(params: P, page: number = 1) {
    const limit = 10;

    params.page = { ...(params.page || {}), limit };

    super(params, page, limit);
  }

  get type(): string {
    return 'posts';
  }
}
