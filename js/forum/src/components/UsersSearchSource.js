import highlight from 'flarum/helpers/highlight';
import avatar from 'flarum/helpers/avatar';

/**
 * The `UsersSearchSource` finds and displays user search results in the search
 * dropdown.
 *
 * @implements SearchSource
 */
export default class UsersSearchResults {
  search(query) {
    return app.store.find('users', {
      filter: {q: query},
      page: {limit: 5}
    });
  }

  view(query) {
    const results = app.store.all('users')
      .filter(user => user.username().toLowerCase().substr(0, query.length) === query);

    if (!results.length) return '';

    return [
      <li className="Dropdown-header">{app.trans('core.users')}</li>,
      results.map(user => (
        <li className="UserSearchResult" data-index={'users' + user.id()}>
          <a href={app.route.user(user)} config={m.route}>
            {avatar(user)}
            {highlight(user.username(), query)}
          </a>
        </li>
      ))
    ];
  }
}
