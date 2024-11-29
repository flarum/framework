import app from '../app';
import LinkButton from '../../common/components/LinkButton';
import type Mithril from 'mithril';
import type Discussion from '../../common/models/Discussion';
import type { GlobalSearchSource } from './GlobalSearch';
import extractText from '../../common/utils/extractText';
import MinimalDiscussionListItem from './MinimalDiscussionListItem';

/**
 * The `DiscussionsSearchSource` finds and displays discussion search results in
 * the search dropdown.
 */
export default class GlobalDiscussionsSearchSource implements GlobalSearchSource {
  protected results = new Map<string, Discussion[]>();

  public resource: string = 'discussions';

  title(): string {
    return extractText(app.translator.trans('core.lib.search_source.discussions.heading'));
  }

  isCached(query: string): boolean {
    return this.results.has(query.toLowerCase());
  }

  async search(query: string, limit: number): Promise<void> {
    query = query.toLowerCase();

    this.results.set(query, []);

    const params = {
      filter: { q: query },
      page: { limit },
      include: 'mostRelevantPost,user,firstPost,tags',
    };

    return app.store.find<Discussion[]>('discussions', params).then((results) => {
      this.results.set(query, results);
      m.redraw();
    });
  }

  view(query: string): Array<Mithril.Vnode> {
    query = query.toLowerCase();

    return (this.results.get(query) || []).map((discussion) => {
      return (
        <li className="DiscussionSearchResult" data-index={'discussions' + discussion.id()} data-id={discussion.id()}>
          <MinimalDiscussionListItem discussion={discussion} params={{ q: query }} />
        </li>
      );
    }) as Array<Mithril.Vnode>;
  }

  customGrouping(): boolean {
    return false;
  }

  fullPage(query: string): Mithril.Vnode {
    const filter = app.search.gambits.apply('discussions', { q: query });
    const q = filter.q || null;
    delete filter.q;

    return (
      <li>
        <LinkButton icon="fas fa-search" href={app.route('index', { q, filter })}>
          {app.translator.trans('core.lib.search_source.discussions.all_button', { query })}
        </LinkButton>
      </li>
    );
  }

  gotoItem(id: string): string | null {
    const discussion = app.store.getById<Discussion>('discussions', id);

    if (!discussion) return null;

    return app.route.discussion(discussion);
  }
}
