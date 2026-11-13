import app from 'flarum/forum/app';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import usernameHelper from 'flarum/common/helpers/username';
import Avatar from 'flarum/common/components/Avatar';
import highlight from 'flarum/common/helpers/highlight';
import MentionableModel from './MentionableModel';
import getCleanDisplayName from '../utils/getCleanDisplayName';
import AtMentionFormat from './formats/AtMentionFormat';

export default class UserMention extends MentionableModel<User, AtMentionFormat> {
  type(): string {
    return 'user';
  }

  initialResults(): User[] {
    return Array.from(app.store.all<User>('users'));
  }

  /**
   * Uses the user ID so autocomplete works regardless of the username's characters.
   */
  public replacement(user: User): string {
    const cleanText = getCleanDisplayName(user);
    return this.format.format(cleanText, '', user.id());
  }

  suggestion(model: User, typed: string): Mithril.Children {
    const username = usernameHelper(model, (name: string) => highlight(name, typed));

    return (
      <>
        <Avatar user={model} />
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
