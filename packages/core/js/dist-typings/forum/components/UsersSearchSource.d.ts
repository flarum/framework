import { SearchSource } from './Search';
import Mithril from 'mithril';
/**
 * The `UsersSearchSource` finds and displays user search results in the search
 * dropdown.
 */
export default class UsersSearchResults implements SearchSource {
    protected results: Map<string, unknown[]>;
    search(query: string): Promise<void>;
    view(query: string): Array<Mithril.Vnode>;
}
