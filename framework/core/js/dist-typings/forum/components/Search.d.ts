import Component, { ComponentAttrs } from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import KeyboardNavigatable from '../../common/utils/KeyboardNavigatable';
import SearchState from '../states/SearchState';
import type Mithril from 'mithril';
/**
 * The `SearchSource` interface defines a section of search results in the
 * search dropdown.
 *
 * Search sources should be registered with the `Search` component class
 * by extending the `sourceItems` method. When the user types a
 * query, each search source will be prompted to load search results via the
 * `search` method. When the dropdown is redrawn, it will be constructed by
 * putting together the output from the `view` method of each source.
 */
export interface SearchSource {
    /**
     * Make a request to get results for the given query.
     * The results will be updated internally in the search source, not exposed.
     */
    search(query: string): Promise<void>;
    /**
     * Get an array of virtual <li>s that list the search results for the given
     * query.
     */
    view(query: string): Array<Mithril.Vnode>;
}
export interface SearchAttrs extends ComponentAttrs {
    /** The type of alert this is. Will be used to give the alert a class name of `Alert--{type}`. */
    state: SearchState;
}
/**
 * The `Search` component displays a menu of as-you-type results from a variety
 * of sources.
 *
 * The search box will be 'activated' if the app's search state's
 * getInitialSearch() value is a truthy value. If this is the case, an 'x'
 * button will be shown next to the search field, and clicking it will clear the search.
 *
 * ATTRS:
 *
 * - state: SearchState instance.
 */
export default class Search<T extends SearchAttrs = SearchAttrs> extends Component<T, SearchState> {
    /**
     * The minimum query length before sources are searched.
     */
    protected static MIN_SEARCH_LEN: number;
    /**
     * The instance of `SearchState` for this component.
     */
    protected searchState: SearchState;
    /**
     * The instance of `SearchState` for this component.
     *
     * @deprecated Replace with`this.searchState` instead.
     */
    get state(): SearchState;
    set state(state: SearchState);
    /**
     * Whether or not the search input has focus.
     */
    protected hasFocus: boolean;
    /**
     * An array of SearchSources.
     */
    protected sources?: SearchSource[];
    /**
     * The number of sources that are still loading results.
     */
    protected loadingSources: number;
    /**
     * The index of the currently-selected <li> in the results list. This can be
     * a unique string (to account for the fact that an item's position may jump
     * around as new results load), but otherwise it will be numeric (the
     * sequential position within the list).
     */
    protected index: number;
    protected navigator: KeyboardNavigatable;
    protected searchTimeout?: number;
    private updateMaxHeightHandler?;
    oninit(vnode: Mithril.Vnode<T, this>): void;
    view(): JSX.Element;
    updateMaxHeight(): void;
    onupdate(vnode: Mithril.VnodeDOM<T, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<T, this>): void;
    onremove(vnode: Mithril.VnodeDOM<T, this>): void;
    /**
     * Navigate to the currently selected search result and close the list.
     */
    selectResult(): void;
    /**
     * Clear the search
     */
    clear(): void;
    /**
     * Build an item list of SearchSources.
     */
    sourceItems(): ItemList<SearchSource>;
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
}
