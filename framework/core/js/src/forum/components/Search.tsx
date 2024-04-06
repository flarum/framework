import app from '../../forum/app';
import Component, { ComponentAttrs } from '../../common/Component';
import extractText from '../../common/utils/extractText';
import Input from '../../common/components/Input';
import SearchState from '../../common/states/SearchState';
import SearchModal from './SearchModal';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
import DiscussionsSearchSource from './DiscussionsSearchSource';
import UsersSearchSource from './UsersSearchSource';

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
  protected searchState!: SearchState;

  oninit(vnode: Mithril.Vnode<T, this>) {
    super.oninit(vnode);

    this.searchState = this.attrs.state;
  }

  view() {
    // Hide the search view if no sources were loaded
    if (this.sourceItems().isEmpty()) return <div></div>;

    const searchLabel = extractText(app.translator.trans('core.forum.header.search_placeholder'));

    return (
      <div role="search" className="Search" aria-label={app.translator.trans('core.forum.header.search_role_label')}>
        <Input
          type="search"
          className="Search-input"
          clearable={this.searchState.getValue()}
          clearLabel={app.translator.trans('core.forum.header.search_clear_button_accessible_label')}
          prefixIcon="fas fa-search"
          aria-label={searchLabel}
          readonly={true}
          placeholder={searchLabel}
          value={this.searchState.getValue()}
          onchange={(value: string) => {
            if (!value) this.searchState.clear();
            else this.searchState.setValue(value);
          }}
          inputAttrs={{
            onfocus: () =>
              setTimeout(() => {
                this.$('input').blur() &&
                  app.modal.show(() => import('./SearchModal'), { searchState: this.searchState, sources: this.sourceItems().toArray() });
              }, 150),
          }}
        />
      </div>
    );
  }

  /**
   * A list of search sources that can be used to query for search results.
   */
  sourceItems(): ItemList<SearchSource> {
    const items = new ItemList<SearchSource>();

    if (app.forum.attribute('canViewForum')) {
      items.add('discussions', new DiscussionsSearchSource());
    }

    if (app.forum.attribute('canSearchUsers')) {
      items.add('users', new UsersSearchSource());
    }

    return items;
  }
}
