import app from '../app';
import Component, { ComponentAttrs } from '../Component';
import SearchState from '../states/SearchState';
import extractText from '../utils/extractText';
import ItemList from '../utils/ItemList';
import classList from '../utils/classList';
import Icon from './Icon';
import type Mithril from 'mithril';

export interface SearchAttrs extends ComponentAttrs {
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

    const value = this.searchState.getValue();

    // Search happens in a modal, so this control does not accept text: it is a
    // button that opens the modal, and reads as one to assistive technology
    // rather than as a textbox that refuses input. Where its label is shown —
    // the drawer — it displays the current query in place of the prompt, so a
    // search that has been run stays visible after the modal closes.
    const openSearchModal = () => {
      app.modal.show(() => import('../../common/components/SearchModal'), { searchState: this.searchState, sources: this.sourceItems().toArray() });
    };

    return (
      <div role="search" className={classList('Search', { 'Search--active': !!value })} aria-label={this.attrs.a11yRoleLabel}>
        <button
          type="button"
          // `Button--flat` rather than `Button--link`: this sits among the
          // notification and message controls, which are flat buttons, and
          // should pick up the same hover and focus treatment as them.
          className="Search-input Button Button--flat"
          aria-label={
            value
              ? extractText(app.translator.trans('core.lib.search.search_active_button_accessible_label', { query: value }))
              : extractText(this.attrs.label)
          }
          title={value || extractText(this.attrs.label)}
          onclick={() => {
            // On phones the search button lives inside the slide-out drawer. Close the drawer as soon as
            // search is activated, before the modal is shown, so it isn't left open behind (and overlapping)
            // the full-screen search modal as it animates in. Hiding it here rather than in `openSearchModal`
            // lets the drawer finish sliding out during the delay below. No-op where the drawer is never open.
            app.drawer.hide();
            setTimeout(() => openSearchModal(), 150);
          }}
        >
          <Icon name="fas fa-search" className="Button-icon" />
          <span className="Button-label">
            <span className="Button-labelText">{value || this.attrs.label}</span>
          </span>
        </button>
        {!!value && (
          <button
            type="button"
            className="Search-clear Button Button--icon Button--link"
            aria-label={extractText(app.translator.trans('core.lib.search.search_clear_button_accessible_label'))}
            onclick={() => this.searchState.clear()}
          >
            <Icon name="fas fa-times-circle" />
          </button>
        )}
      </div>
    );
  }

  /**
   * A list of search sources that can be used to query for search results.
   */
  abstract sourceItems(): ItemList<GlobalSearchSource>;
}
