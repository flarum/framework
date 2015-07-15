import highlight from 'flarum/helpers/highlight';
import Button from 'flarum/components/Button';

/**
 * The `DiscussionsSearchSource` finds and displays discussion search results in
 * the search dropdown.
 *
 * @implements SearchSource
 */
export default class DiscussionsSearchSource {
  constructor() {
    this.results = {};
  }

  search(query) {
    this.results[query] = [];

    const params = {
      filter: {q: query},
      page: {limit: 3},
      include: 'relevantPosts,relevantPosts.discussion,relevantPosts.user'
    };

    return app.store.find('discussions', params).then(results => this.results[query] = results);
  }

  view(query) {
    const results = this.results[query] || [];

    return [
      <li className="dropdown-header">Discussions</li>,
      <li>
        {Button.component({
          icon: 'search',
          children: 'Search all discussions for "' + query + '"',
          href: app.route('index', {q: query}),
          config: m.route
        })}
      </li>,
      results.map(discussion => {
        const relevantPosts = discussion.relevantPosts();
        const post = relevantPosts && relevantPosts[0];

        return (
          <li className="discussion-search-result" data-index={'discussions' + discussion.id()}>
            <a href={app.route.discussion(discussion, post && post.number())} config={m.route}>
              <div className="title">{highlight(discussion.title(), query)}</div>
              {post ? <div className="excerpt">{highlight(post.contentPlain(), query, 100)}</div> : ''}
            </a>
          </li>
        );
      })
    ];
  }
}
