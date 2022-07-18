import type Mithril from 'mithril';
import { SearchSource } from './Search';
import User from '../../common/models/User';
/**
 * The `UsersSearchSource` finds and displays user search results in the search
 * dropdown.
 */
export default class UsersSearchResults implements SearchSource {
    protected results: Map<string, User[]>;
    search(query: string): Promise<void>;
    view(query: string): Array<Mithril.Vnode>;
}
