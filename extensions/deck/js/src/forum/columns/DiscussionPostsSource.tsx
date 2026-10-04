import app from 'flarum/forum/app';
import Link from 'flarum/common/components/Link';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import PostListSource from './PostListSource';
import { relatedId } from './realtimeEvents';

/**
 * The newest replies in one discussion. Core's PostStream is bound to window
 * scrolling and can't live in a column, so this is a newest-first post list
 * with a link through to the real discussion page.
 */
export default class DiscussionPostsSource extends PostListSource {
  constructor(protected discussionId: string) {
    super({ filter: { discussion: discussionId } });
  }

  protected matches(post: Post): boolean {
    return relatedId(post, 'discussion') === this.discussionId;
  }

  /** A new discussion is never this one. */
  protected mayMatchNewDiscussion(): boolean {
    return false;
  }

  discussion(): Discussion | undefined {
    return app.store.getById<Discussion>('discussions', this.discussionId);
  }

  protected prepare(): Promise<unknown> {
    return this.discussion() ? Promise.resolve() : app.store.find<Discussion>('discussions', this.discussionId).catch(() => null);
  }

  view() {
    const discussion = this.discussion();

    return (
      <>
        {discussion && (
          <Link className="Button Button--block DeckColumn-open" href={app.route.discussion(discussion)}>
            {app.translator.trans('flarum-deck.forum.column.open_discussion_button')}
          </Link>
        )}
        {super.view()}
      </>
    );
  }
}
