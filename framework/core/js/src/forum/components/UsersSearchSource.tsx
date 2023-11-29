import type Mithril from 'mithril';

import app from '../app';
import highlight from '../../common/helpers/highlight';
import username from '../../common/helpers/username';
import Link from '../../common/components/Link';
import type User from '../../common/models/User';
import Avatar from '../../common/components/Avatar';
import type { SearchSource } from './Search';
import extractText from '../../common/utils/extractText';
import listItems from '../../common/helpers/listItems';

/**
 * The `UsersSearchSource` finds and displays user search results in the search
 * dropdown.
 */
export default class UsersSearchResults implements SearchSource {
  protected results = new Map<string, User[]>();

  public resource: string = 'users';

  title(): string {
    return extractText(app.translator.trans('core.lib.search_source.users.heading'));
  }

  isCached(query: string): boolean {
    return this.results.has(query.toLowerCase());
  }

  async search(query: string, limit: number): Promise<void> {
    return app.store
      .find<User[]>('users', {
        filter: { q: query },
        page: { limit },
      })
      .then((results) => {
        this.results.set(query, results);
        m.redraw();
      });
  }

  view(query: string): Array<Mithril.Vnode> {
    query = query.toLowerCase();

    const results = (this.results.get(query) || [])
      .concat(
        app.store
          .all<User>('users')
          .filter((user) => [user.username(), user.displayName()].some((value) => value.toLowerCase().substr(0, query.length) === query))
      )
      .filter((e, i, arr) => arr.lastIndexOf(e) === i)
      .sort((a, b) => a.displayName().localeCompare(b.displayName()));

    if (!results.length) return [];

    return results.map((user) => {
      const name = username(user, (name: string) => highlight(name, query));

      return (
        <li className="UserSearchResult" data-index={'users' + user.id()} data-id={user.id()}>
          <Link href={app.route.user(user)}>
            <Avatar user={user} />
            <div className="UserSearchResult-name">
              {name}
              <div className="badges badges--packed">{listItems(user.badges().toArray())}</div>
            </div>
          </Link>
        </li>
      );
    });
  }

  fullPage(query: string): null {
    return null;
  }

  gotoItem(id: string): string | null {
    const user = app.store.getById<User>('users', id);

    if (!user) return null;

    return app.route.user(user);
  }
}
