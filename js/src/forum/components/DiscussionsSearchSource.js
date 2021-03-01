import highlight from '../../common/helpers/highlight';
import LinkButton from '../../common/components/LinkButton';
import Link from '../../common/components/Link';

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
    query = query.toLowerCase();

    this.results[query] = [];

    const params = {
      filter: { q: query },
      page: { limit: 3 },
      include: 'mostRelevantPost',
    };

    return app.store.find('discussions', params).then((results) => (this.results[query] = results));
  }

  view(query) {
    query = query.toLowerCase();

    const results = this.results[query] || [];

    return [
      <li className="Dropdown-header">{app.translator.trans('core.forum.search.discussions_heading')}</li>,
      <li>
        {LinkButton.component(
          {
            icon: 'fas fa-search',
            href: app.route('index', { q: query }),
          },
          app.translator.trans('core.forum.search.all_discussions_button', { query })
        )}
      </li>,
      results.map((discussion) => {
        const mostRelevantPost = discussion.mostRelevantPost();

        return (
          <li className="DiscussionSearchResult" data-index={'discussions' + discussion.id()}>
            <Link href={app.route.discussion(discussion, mostRelevantPost && mostRelevantPost.number())}>
              <div className="DiscussionSearchResult-title">{highlight(discussion.title(), query)}</div>
              {mostRelevantPost ? <div className="DiscussionSearchResult-excerpt">{highlight(mostRelevantPost.contentPlain(), query, 100)}</div> : ''}
            </Link>
          </li>
        );
      }),
    ];
  }
}
