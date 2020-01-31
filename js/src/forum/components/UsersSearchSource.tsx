import highlight from '../../common/helpers/highlight';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import SearchSource from './SearchSource';
import User from '../../common/models/User';

/**
 * The `UsersSearchSource` finds and displays user search results in the search
 * dropdown.
 *
 * @implements SearchSource
 */
export default class UsersSearchSource extends SearchSource {
    protected results: { [key: string]: User[] } = {};

    search(query: string) {
        return app.store
            .find<User>('users', {
                filter: { q: query },
                page: { limit: 5 },
            })
            .then(results => {
                this.results[query] = results;
                m.redraw();
            });
    }

    view(query: string) {
        query = query.toLowerCase();

        const results = (this.results[query] || [])
            .concat(
                app.store
                    .all<User>('users')
                    .filter(user => [user.username(), user.displayName()].some(value => value.toLowerCase().substr(0, query.length) === query))
            )
            .filter((e, i, arr) => arr.lastIndexOf(e) === i)
            .sort((a, b) => a.displayName().localeCompare(b.displayName()));

        if (!results.length) return '';

        return [
            <li className="Dropdown-header">{app.translator.trans('core.forum.search.users_heading')}</li>,
            results.map(user => {
                const name = username(user);

                if (!name.children) {
                    name.children = [name.text];
                    delete name.text;
                }

                name.children[0] = highlight(name.children[0], query);

                return (
                    <li className="UserSearchResult" data-index={'users' + user.id()}>
                        <m.route.Link href={app.route.user(user)}>
                            {avatar(user)}
                            {name}
                        </m.route.Link>
                    </li>
                );
            }),
        ];
    }
}
