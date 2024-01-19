import Component, { ComponentAttrs } from '../../common/Component';
import SearchState from '../../common/states/SearchState';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export interface SearchAttrs extends ComponentAttrs {
    /** The type of alert this is. Will be used to give the alert a class name of `Alert--{type}`. */
    state: SearchState;
}
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
     * The resource type that this search source is responsible for.
     */
    resource: string;
    /**
     * Get the title for this search source.
     */
    title(): string;
    /**
     * Check if a query has been cached for this search source.
     */
    isCached(query: string): boolean;
    /**
     * Make a request to get results for the given query.
     * The results will be updated internally in the search source, not exposed.
     */
    search(query: string, limit: number): Promise<void>;
    /**
     * Get an array of virtual <li>s that list the search results for the given
     * query.
     */
    view(query: string): Array<Mithril.Vnode>;
    /**
     * Get a list item for the full search results page.
     */
    fullPage(query: string): Mithril.Vnode | null;
    /**
     * Get to the result item page. Only called if each list item has a data-id.
     */
    gotoItem(id: string): string | null;
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
     * The instance of `SearchState` for this component.
     */
    protected searchState: SearchState;
    oninit(vnode: Mithril.Vnode<T, this>): void;
    view(): JSX.Element;
    /**
     * A list of search sources that can be used to query for search results.
     */
    sourceItems(): ItemList<SearchSource>;
}
