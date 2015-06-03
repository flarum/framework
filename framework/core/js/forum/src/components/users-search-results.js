import highlight from 'flarum/helpers/highlight';
import avatar from 'flarum/helpers/avatar';

export default class UsersSearchResults {
  search(string) {
    return app.store.find('users', {q: string, page: {limit: 5}});
  }

  view(string) {
    var results = app.store.all('users').filter(user => user.username().toLowerCase().substr(0, string.length) === string);

    return results.length ? [
      m('li.dropdown-header', 'Users'),
      results.map(user => m('li.user-search-result', {'data-index': 'users'+user.id()},
        m('a', {
          href: app.route.user(user),
          config: m.route
        }, avatar(user), highlight(user.username(), string))
      ))
    ] : '';
  }
}
