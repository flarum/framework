import highlight from '../../common/helpers/highlight';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import Link from '../../common/components/Link';

/**
 * The `UsersSearchSource` finds and displays user search results in the search
 * dropdown.
 *
 * @implements SearchSource
 */
export default class UsersSearchResults {
  constructor() {
    this.results = {};
  }

  search(query) {
    return app.store
      .find('users', {
        filter: { q: query },
        page: { limit: 5 },
      })
      .then((results) => {
        this.results[query] = results;
        m.redraw();
      });
  }

  view(query) {
    query = query.toLowerCase();

    const results = (this.results[query] || [])
      .concat(
        app.store
          .all('users')
          .filter((user) => [user.username(), user.displayName()].some((value) => value.toLowerCase().substr(0, query.length) === query))
      )
      .filter((e, i, arr) => arr.lastIndexOf(e) === i)
      .sort((a, b) => a.displayName().localeCompare(b.displayName()));

    if (!results.length) return '';

    return [
      <li className="Dropdown-header">{app.translator.trans('core.forum.search.users_heading')}</li>,
      results.map((user) => {
        const name = username(user);

        const children = [highlight(name.text, query)];

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
