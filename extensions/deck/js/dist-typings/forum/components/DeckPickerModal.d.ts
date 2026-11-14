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
    /** Marks results the deck already has a column for. They can still be chosen. */
    isAdded?: (model: Model) => boolean;
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
        isAdded?: (model: Model) => boolean;
    }): void;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    /**
     * The source as it is, with results the deck already holds marked. Core's
     * sources render each result themselves, so the mark goes on afterwards: a
     * class on the item, and a badge after its content.
     */
    protected marking(source: GlobalSearchSource): GlobalSearchSource;
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
