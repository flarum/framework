import SearchModal, { type ISearchModalAttrs } from 'flarum/common/components/SearchModal';
import type { GlobalSearchSource } from 'flarum/common/components/AbstractGlobalSearch';
import ItemList from 'flarum/common/utils/ItemList';
import type Model from 'flarum/common/Model';
import type Mithril from 'mithril';
export interface IDeckPickerModalAttrs extends ISearchModalAttrs {
    title: Mithril.Children;
    /** Called with the chosen result's model, after which the picker closes. */
    onpick: (model: Model) => void;
    /** List every result before anything is typed, for short lists such as tags. */
    browse?: boolean;
}
/**
 * The forum's search modal, choosing a result instead of opening it, so every
 * Deck picker looks and works like search. Results are told apart by their
 * `data-id`, looked up in the store under the source's resource.
 */
export default class DeckPickerModal<CustomAttrs extends IDeckPickerModalAttrs = IDeckPickerModalAttrs> extends SearchModal<CustomAttrs> {
    /** Opens a picker over whatever modal is open. */
    static open(source: GlobalSearchSource, attrs: {
        title: Mithril.Children;
        onpick: (model: Model) => void;
        browse?: boolean;
    }): void;
    className(): string;
    title(): Mithril.Children;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    /** One source, so no tabs. */
    tabItems(): ItemList<Mithril.Children>;
    activeTabItems(): ItemList<Mithril.Children>;
    /** Filters narrow a search, not a choice of one result. */
    gambits(): JSX.Element[];
    gambifyInput(): Mithril.Children;
    prefill(value: string): string;
    defaultActiveSource(): string | null;
    defaultFilters(): Record<string, Record<string, any>>;
    selectResult(): void;
    protected onResultClick: (e: Event) => void;
    protected pick(id: string): void;
}
