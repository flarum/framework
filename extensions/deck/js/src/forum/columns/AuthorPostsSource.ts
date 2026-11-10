import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import { resolveAuthorSlug } from './AuthorDiscussionsSource';
import PostListSource from './PostListSource';
import { relatedId } from './realtimeEvents';

/** A member's newest comments anywhere, by id so renames don't break it. */
export default class AuthorPostsSource extends PostListSource {
  constructor(protected userId: string) {
    super({ filter: {} });
  }

  protected matches(post: Post): boolean | undefined {
    const authorId = relatedId(post, 'user');

    return authorId === null ? undefined : authorId === this.userId;
  }

  protected mayMatchNewDiscussion(discussion: Discussion): boolean | undefined {
    const authorId = relatedId(discussion, 'user');

    return authorId === null ? undefined : authorId === this.userId;
  }

  protected prepare(): Promise<unknown> {
    return resolveAuthorSlug(this.userId).then((slug) => {
      this.state.getParams().filter = { author: slug };
    });
  }
}
