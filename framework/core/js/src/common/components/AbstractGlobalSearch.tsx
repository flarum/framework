import app from '../app';
import Component, { ComponentAttrs } from '../Component';
import SearchState from '../states/SearchState';
import extractText from '../utils/extractText';
import ItemList from '../utils/ItemList';
import Input from './Input';
import type Mithril from 'mithril';

export interface SearchAttrs extends ComponentAttrs {
  /** The type of alert this is. Will be used to give the alert a class name of `Alert--{type}`. */
  state: SearchState;
  label: string;
  a11yRoleLabel: string;
}

/**
 * The `SearchSource` interface defines a tab of search results in the
 * search modal.
 *
 * Search sources should be registered with the `GlobalSearch` component class
 * by extending the `sourceItems` method. When the user types a
 * query, each search source will be prompted to load search results via the
 * `search` method. When the search modal's dropdown is redrawn, it will be constructed by
 * putting together the output from the `view` method of each source.
 */
export interface GlobalSearchSource {
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
   * Whether the search results view uses custom grouping of the results.
   * Prevents the `Search Preview` default group from display.
   */
  customGrouping(): boolean;

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
 * The `Search` component displays a primary search input at the top of the frontend (forum or admin).
 * When clicked, it opens an advanced search modal with results from various sources.
 *
 * Must be extended and the abstract methods implemented per-frontend.
 */
export default abstract class AbstractGlobalSearch<T extends SearchAttrs = SearchAttrs> extends Component<T, SearchState> {
  /**
   * The instance of `SearchState` for this component.
   */
  protected searchState!: SearchState;

  oninit(vnode: Mithril.Vnode<T, this>) {
    super.oninit(vnode);

    this.searchState = this.attrs.state;
  }

  view() {
    // Hide the search view if no sources were loaded
    if (this.sourceItems().isEmpty()) return <div></div>;

    const openSearchModal = () => {
      this.$('input').blur() &&
        app.modal.show(() => import('../../common/components/SearchModal'), { searchState: this.searchState, sources: this.sourceItems().toArray() });
    };

    return (
      <div
        role="search"
        className="Search"
        aria-label={this.attrs.a11yRoleLabel}
        onclick={() => {
          this.$('input').blur();
          setTimeout(() => openSearchModal(), 150);
        }}
      >
        <Input
          type="search"
          className="Search-input"
          clearable={this.searchState.getValue()}
          clearLabel={app.translator.trans('core.lib.search.search_clear_button_accessible_label')}
          prefixIcon="fas fa-search"
          aria-label={this.attrs.label}
          readonly={true}
          placeholder={this.attrs.label}
          value={this.searchState.getValue()}
          onchange={(value: string) => {
            if (!value) this.searchState.clear();
            else this.searchState.setValue(value);
          }}
          inputAttrs={{
            // for keyboard navigation, click event would be triggered on keydown
            onkeydown: (e: KeyboardEvent) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                this.$('input').blur() && openSearchModal();
              }
            },
          }}
        />
      </div>
    );
  }

  /**
   * A list of search sources that can be used to query for search results.
   */
  abstract sourceItems(): ItemList<GlobalSearchSource>;
}
