import type Mithril from 'mithril';
import type { GlobalSearchSource } from 'flarum/forum/components/GlobalSearch';
import type DialogMessage from '../../common/models/DialogMessage';
/**
 * Finds the member's private messages by what they say. The API only searches
 * the conversations the member is part of, so nobody else's can turn up.
 */
export default class GlobalMessagesSearchSource implements GlobalSearchSource {
    protected results: Map<string, DialogMessage[]>;
    resource: string;
    title(): string;
    isCached(query: string): boolean;
    search(query: string, limit: number): Promise<void>;
    view(query: string): Array<Mithril.Vnode>;
    customGrouping(): boolean;
    /** Messages have no page of their own to list every result on. */
    fullPage(): null;
    gotoItem(id: string): string | null;
}
