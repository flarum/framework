import app from '../app';
import LinkButton from '../../common/components/LinkButton';
import type Mithril from 'mithril';
import type Post from '../../common/models/Post';
import type { SearchSource } from './Search';
import extractText from '../../common/utils/extractText';
import MinimalDiscussionListItem from './MinimalDiscussionListItem';

/**
 * The `PostsSearchSource` finds and displays post search results in
 * the search dropdown.
 */
export default class PostsSearchSource implements SearchSource {
  protected results = new Map<string, Post[]>();

  public resource: string = 'posts';

  title(): string {
    return extractText(app.translator.trans('core.lib.search_source.posts.heading'));
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
      include: 'user,discussion.tags',
    };

    return app.store.find<Post[]>('posts', params).then((results) => {
      this.results.set(query, results);
      m.redraw();
    });
  }

  view(query: string): Array<Mithril.Vnode> {
    query = query.toLowerCase();

    return (this.results.get(query) || []).map((post) => {
      return (
        <li className="PostSearchResult" data-index={'posts' + post.id()} data-id={post.id()}>
          <MinimalDiscussionListItem discussion={post.discussion()} post={post} params={{ q: query }} jumpTo={post.number()} author={post.user()} />
        </li>
      );
    }) as Array<Mithril.Vnode>;
  }

  customGrouping(): boolean {
    return false;
  }

  fullPage(query: string): Mithril.Vnode {
    const filter = app.search.gambits.apply('posts', { q: query });
    const q = filter.q || null;
    delete filter.q;

    return (
      <li>
        <LinkButton icon="fas fa-search" href={app.route('posts', { q, filter })}>
          {app.translator.trans('core.lib.search_source.posts.all_button', { query })}
        </LinkButton>
      </li>
    );
  }

  gotoItem(id: string): string | null {
    const post = app.store.getById<Post>('posts', id);

    if (!post) return null;

    return app.route.post(post);
  }
}
