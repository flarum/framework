import type Mithril from 'mithril';
import type User from '../../common/models/User';
import type { SearchSource } from './Search';
/**
 * The `UsersSearchSource` finds and displays user search results in the search
 * dropdown.
 */
export default class UsersSearchSource implements SearchSource {
    protected results: Map<string, User[]>;
    resource: string;
    title(): string;
    isCached(query: string): boolean;
    search(query: string, limit: number): Promise<void>;
    view(query: string): Array<Mithril.Vnode>;
    fullPage(query: string): null;
    gotoItem(id: string): string | null;
}
