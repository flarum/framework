import app from '../../forum/app';
import LinkButton from '../../common/components/LinkButton';
import { SearchSource } from './Search';
import type Mithril from 'mithril';
import Discussion from '../../common/models/Discussion';
import DiscussionsSearchItem from './DiscussionsSearchItem';

/**
 * The `DiscussionsSearchSource` finds and displays discussion search results in
 * the search dropdown.
 */
export default class DiscussionsSearchSource implements SearchSource {
  protected results = new Map<string, Discussion[]>();
  queryString: string | null = null;

  async search(query: string): Promise<void> {
    query = query.toLowerCase();

    this.results.set(query, []);

    this.setQueryString(query);

    const params = {
      filter: { q: this.queryString || query },
      page: { limit: this.limit() },
      include: this.includes().join(','),
    };

    return app.store.find<Discussion[]>('discussions', params).then((results) => {
      this.results.set(query, results);
      m.redraw();
    });
  }

  view(query: string): Array<Mithril.Vnode> {
    query = query.toLowerCase();

    this.setQueryString(query);

    const results = (this.results.get(query) || []).map((discussion) => {
      const mostRelevantPost = discussion.mostRelevantPost();

      return <DiscussionsSearchItem query={query} discussion={discussion} mostRelevantPost={mostRelevantPost} />;
    }) as Array<Mithril.Vnode>;

    return [
      <li className="Dropdown-header">{app.translator.trans('core.forum.search.discussions_heading')}</li>,
      <li>
        <LinkButton icon="fas fa-search" href={app.route('index', { q: this.queryString })}>
          {app.translator.trans('core.forum.search.all_discussions_button', { query })}
        </LinkButton>
      </li>,
      ...results,
    ];
  }

  includes(): string[] {
    return ['mostRelevantPost'];
  }

  limit(): number {
    return 3;
  }

  queryMutators(): string[] {
    return [];
  }

  setQueryString(query: string): void {
    this.queryString = query + ' ' + this.queryMutators().join(' ');
  }
}
