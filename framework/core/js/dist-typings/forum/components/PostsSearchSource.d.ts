import type Mithril from 'mithril';
import type Post from '../../common/models/Post';
import type { SearchSource } from './Search';
/**
 * The `PostsSearchSource` finds and displays post search results in
 * the search dropdown.
 */
export default class PostsSearchSource implements SearchSource {
    protected results: Map<string, Post[]>;
    resource: string;
    title(): string;
    isCached(query: string): boolean;
    search(query: string, limit: number): Promise<void>;
    view(query: string): Array<Mithril.Vnode>;
    fullPage(query: string): Mithril.Vnode;
    gotoItem(id: string): string | null;
}
