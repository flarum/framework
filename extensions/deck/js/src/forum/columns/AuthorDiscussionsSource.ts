import app from 'flarum/forum/app';
import type User from 'flarum/common/models/User';
import DiscussionListSource from './DiscussionListSource';

/** The member's current slug, for the `author` filters, which take slugs. */
export function resolveAuthorSlug(userId: string): Promise<string> {
  const known = app.store.getById<User>('users', userId);

  return (known ? Promise.resolve(known) : app.store.find<User>('users', userId)).then((user) => user.slug());
}

/**
 * Discussions started by one member. The column stores the member's id, not
 * their username, and looks up their current slug when it loads, so renaming
 * the member (or changing the forum's slug driver) doesn't break it.
 */
export default class AuthorDiscussionsSource extends DiscussionListSource {
  constructor(protected userId: string) {
    super({ filter: {} });
  }

  protected prepare(): Promise<unknown> {
    return resolveAuthorSlug(this.userId).then((slug) => {
      this.state.getParams().filter = { author: slug };
    });
  }
}
