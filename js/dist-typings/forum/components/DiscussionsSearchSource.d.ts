import { SearchSource } from './Search';
import Mithril from 'mithril';
/**
 * The `DiscussionsSearchSource` finds and displays discussion search results in
 * the search dropdown.
 */
export default class DiscussionsSearchSource implements SearchSource {
    protected results: Map<string, unknown[]>;
    search(query: string): Promise<Map<string, unknown[]>>;
    view(query: string): Array<Mithril.Vnode>;
}
