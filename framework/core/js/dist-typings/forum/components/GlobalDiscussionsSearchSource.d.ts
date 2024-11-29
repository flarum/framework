import type Mithril from 'mithril';
import type Discussion from '../../common/models/Discussion';
import type { GlobalSearchSource } from './GlobalSearch';
/**
 * The `DiscussionsSearchSource` finds and displays discussion search results in
 * the search dropdown.
 */
export default class GlobalDiscussionsSearchSource implements GlobalSearchSource {
    protected results: Map<string, Discussion[]>;
    resource: string;
    title(): string;
    isCached(query: string): boolean;
    search(query: string, limit: number): Promise<void>;
    view(query: string): Array<Mithril.Vnode>;
    customGrouping(): boolean;
    fullPage(query: string): Mithril.Vnode;
    gotoItem(id: string): string | null;
}
