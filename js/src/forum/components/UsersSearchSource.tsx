import highlight from '../../common/helpers/highlight';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import Link from '../../common/components/Link';
import { SearchSource } from './Search';
import Mithril from 'mithril';

/**
 * The `UsersSearchSource` finds and displays user search results in the search
 * dropdown.
 */
export default class UsersSearchResults implements SearchSource {
  protected results = new Map<string, unknown[]>();

  search(query: string) {
    return app.store
      .find('users', {
        filter: { q: query },
        page: { limit: 5 },
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
          .all('users')
          .filter((user) => [user.username(), user.displayName()].some((value) => value.toLowerCase().substr(0, query.length) === query))
      )
      .filter((e, i, arr) => arr.lastIndexOf(e) === i)
      .sort((a, b) => a.displayName().localeCompare(b.displayName()));

    if (!results.length) return [];

    return [
      <li className="Dropdown-header">{app.translator.trans('core.forum.search.users_heading')}</li>,
      ...results.map((user) => {
        const name = username(user);

        const children = [highlight(name.text as string, query)];

        return (
          <li className="UserSearchResult" data-index={'users' + user.id()}>
            <Link href={app.route.user(user)}>
              {avatar(user)}
              {{ ...name, text: undefined, children }}
            </Link>
          </li>
        );
      }),
    ];
  }
}
