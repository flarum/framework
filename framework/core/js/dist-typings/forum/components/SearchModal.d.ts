import type { IFormModalAttrs } from '../../common/components/FormModal';
import FormModal from '../../common/components/FormModal';
import type Mithril from 'mithril';
import type SearchState from '../../common/states/SearchState';
import KeyboardNavigatable from '../../common/utils/KeyboardNavigatable';
import Stream from '../../common/utils/Stream';
import type { SearchSource } from './Search';
import ItemList from '../../common/utils/ItemList';
import GambitsAutocomplete from '../../common/utils/GambitsAutocomplete';
export interface ISearchModalAttrs extends IFormModalAttrs {
    onchange: (value: string) => void;
    searchState: SearchState;
    sources: SearchSource[];
}
export default class SearchModal<CustomAttrs extends ISearchModalAttrs = ISearchModalAttrs> extends FormModal<CustomAttrs> {
    static LIMIT: number;
    protected searchState: SearchState;
    protected query: Stream<string>;
    /**
     * An array of SearchSources.
     */
    protected sources: SearchSource[];
    /**
     * The key of the currently-active search source.
     */
    protected activeSource: Stream<SearchSource>;
    /**
     * The sources that are still loading results.
     */
    protected loadingSources: string[];
    /**
     * The index of the currently-selected <li> in the results list. This can be
     * a unique string (to account for the fact that an item's position may jump
     * around as new results load), but otherwise it will be numeric (the
     * sequential position within the list).
     */
    protected index: number;
    protected navigator: KeyboardNavigatable;
    protected searchTimeout?: number;
    protected inputScroll: Stream<number>;
    protected gambitsAutocomplete: Record<string, GambitsAutocomplete>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    title(): Mithril.Children;
    className(): string;
    content(): Mithril.Children;
    tabs(): JSX.Element;
    tabItems(): ItemList<Mithril.Children>;
    activeTabItems(): ItemList<Mithril.Children>;
    switchSource(source: SearchSource): void;
    gambits(): JSX.Element[];
    /**
     * Transforms a simple search text to wrap valid gambits in a mark tag.
     * @example `lorem ipsum is:unread dolor` => `lorem ipsum <mark>is:unread</mark> dolor`
     */
    gambifyInput(): Mithril.Children;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    search(query: string): void;
    /**
     * Navigate to the currently selected search result and close the list.
     */
    selectResult(): void;
    /**
     * Clear the search
     */
    clear(): void;
    /**
     * Get all of the search result items that are selectable.
     */
    selectableItems(): JQuery;
    /**
     * Get the position of the currently selected search result item.
     * Returns zero if not found.
     */
    getCurrentNumericIndex(): number;
    /**
     * Get the <li> in the search results with the given index (numeric or named).
     */
    getItem(index: number): JQuery;
    /**
     * Set the currently-selected search result item to the one with the given
     * index.
     */
    setIndex(index: number, scrollToItem?: boolean): void;
    inputElement(): JQuery<HTMLInputElement>;
    defaultActiveSource(): string | null;
    defaultFilters(): Record<string, Record<string, any>>;
    prefill(value: string): string;
}
