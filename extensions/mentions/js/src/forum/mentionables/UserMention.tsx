import app from 'flarum/forum/app';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import usernameHelper from 'flarum/common/helpers/username';
import avatar from 'flarum/common/helpers/avatar';
import highlight from 'flarum/common/helpers/highlight';
import type IMentionableModel from './IMentionableModel';
import MentionTextGenerator from '../utils/MentionTextGenerator';

export default class UserMention implements IMentionableModel<User> {
  type(): string {
    return 'user';
  }

  initialResults(): User[] {
    return Array.from(app.store.all<User>('users'));
  }

  replacement(model: User): string {
    return MentionTextGenerator.forUser(model);
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
