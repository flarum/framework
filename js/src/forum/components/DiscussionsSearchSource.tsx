import highlight from '../../common/helpers/highlight';
import LinkButton from '../../common/components/LinkButton';
import SearchSource from "./SearchSource";
import Discussion from "../../common/models/Discussion";

/**
 * The `DiscussionsSearchSource` finds and displays discussion search results in
 * the search dropdown.
 */
export default class DiscussionsSearchSource extends SearchSource {
  protected results: { [key: string]: Discussion[] } = {};

  search(query: string) {
    query = query.toLowerCase();

    this.results[query] = [];

    const params = {
      filter: {q: query},
      page: {limit: 3},
      include: 'mostRelevantPost'
    };

    return app.store.find('discussions', params).then(results => this.results[query] = results);
  }

  view(query: string) {
    query = query.toLowerCase();

    const results = this.results[query] || [];

    return [
      <li className="Dropdown-header">{app.translator.trans('core.forum.search.discussions_heading')}</li>,
      <li>
        {LinkButton.component({
          icon: 'fas fa-search',
          children: app.translator.trans('core.forum.search.all_discussions_button', {query}),
          href: app.route('index', {q: query})
        })}
      </li>,
      results.map(discussion => {
        const mostRelevantPost = discussion.mostRelevantPost();

        return (
          <li className="DiscussionSearchResult" data-index={'discussions' + discussion.id()}>
            <a href={app.route.discussion(discussion, mostRelevantPost && mostRelevantPost.number())} config={m.route}>
              <div className="DiscussionSearchResult-title">{highlight(discussion.title(), query)}</div>
              {mostRelevantPost ? <div className="DiscussionSearchResult-excerpt">{highlight(mostRelevantPost.contentPlain(), query, 100)}</div> : ''}
            </a>
          </li>
        );
      })
    ];
  }
}
