import type { GlobalSearchSource } from 'flarum/common/components/AbstractGlobalSearch';
import type Mithril from 'mithril';
/**
 * Tags for flarum/deck's picker, filtered from the ones already loaded rather
 * than searched for, as the forum payload has every tag the member can see.
 */
export default class DeckTagSearchSource implements GlobalSearchSource {
    resource: string;
    title(): string;
    /** Nothing to fetch. */
    isCached(): boolean;
    search(): Promise<void>;
    view(query: string): Array<Mithril.Vnode>;
    customGrouping(): boolean;
    fullPage(): null;
    gotoItem(): null;
}
