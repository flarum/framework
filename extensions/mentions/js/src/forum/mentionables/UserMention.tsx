import app from 'flarum/forum/app';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import usernameHelper from 'flarum/common/helpers/username';
import avatar from 'flarum/common/helpers/avatar';
import highlight from 'flarum/common/helpers/highlight';
import MentionableModel from './MentionableModel';
import getCleanDisplayName, { shouldUseOldFormat } from '../utils/getCleanDisplayName';
import AtMentionFormat from './formats/AtMentionFormat';

export default class UserMention extends MentionableModel<User, AtMentionFormat> {
  type(): string {
    return 'user';
  }

  initialResults(): User[] {
    return Array.from(app.store.all<User>('users'));
  }

  /**
   * Automatically determines which mention syntax to be used based on the option in the
   * admin dashboard. Also performs display name clean-up automatically.
   *
   * @"Display name"#UserID or `@username`
   *
   * @example <caption>New display name syntax</caption>
   * // '@"user"#1'
   * forUser(User) // User is ID 1, display name is 'User'
   *
   * @example <caption>Using old syntax</caption>
   * // '@username'
   * forUser(user) // User's username is 'username'
   */
  public replacement(user: User): string {
    if (shouldUseOldFormat()) {
      const cleanText = getCleanDisplayName(user, false);
      return this.format.format(cleanText);
    }

    const cleanText = getCleanDisplayName(user);
    return this.format.format(cleanText, '', user.id());
  }

  suggestion(model: User, typed: string): Mithril.Children {
    const username = usernameHelper(model);

    if (typed) {
      username.children = [highlight((username.text ?? '') as string, typed)];
      delete username.text;
    }

    return (
      <>
        {avatar(model)}
        {username}
      </>
    );
  }

  matches(model: User, typed: string): boolean {
    if (!typed) return false;

    const names = [model.username(), model.displayName()];

    return names.some((name) => name.toLowerCase().substr(0, typed.length) === typed);
  }

  maxStoreMatchedResults(): null {
    return null;
  }

  async search(typed: string): Promise<User[]> {
    return await app.store.find<User[]>('users', { filter: { q: typed }, page: { limit: 5 } });
  }

  enabled(): boolean {
    return true;
  }
}
