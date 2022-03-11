import app from '../../forum/app';
import Component, { ComponentAttrs } from '../../common/Component';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import extractText from '../../common/utils/extractText';
import KeyboardNavigatable from '../utils/KeyboardNavigatable';
import icon from '../../common/helpers/icon';
import SearchState from '../states/SearchState';
import DiscussionsSearchSource from './DiscussionsSearchSource';
import UsersSearchSource from './UsersSearchSource';
import { fireDeprecationWarning } from '../../common/helpers/fireDebugWarning';
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
  protected static MIN_SEARCH_LEN = 3;

  /**
   * The instance of `SearchState` for this component.
   */
  protected searchState!: SearchState;

  /**
   * The instance of `SearchState` for this component.
   *
   * @deprecated Replace with`this.searchState` instead.
   */
  // TODO: [Flarum 2.0] Remove this.
  // @ts-expect-error This is a get accessor, while superclass defines this as a property. This is needed to prevent breaking changes, however.
  protected get state() {
    fireDeprecationWarning('`state` property of the Search component is deprecated', '3212');
    return this.searchState;
  }
  protected set state(state: SearchState) {
    // Workaround to prevent triggering deprecation warnings due to Mithril
    // setting state to undefined when creating components
    state !== undefined && fireDeprecationWarning('`state` property of the Search component is deprecated', '3212');
    this.searchState = state;
  }

  /**
   * Whether or not the search input has focus.
   */
  protected hasFocus = false;

  /**
   * An array of SearchSources.
   */
  protected sources?: SearchSource[];

  /**
   * The number of sources that are still loading results.
   */
  protected loadingSources = 0;

  /**
   * The index of the currently-selected <li> in the results list. This can be
   * a unique string (to account for the fact that an item's position may jump
   * around as new results load), but otherwise it will be numeric (the
   * sequential position within the list).
   */
  protected index: number = 0;

  protected navigator!: KeyboardNavigatable;

  protected searchTimeout?: NodeJS.Timeout;

  private updateMaxHeightHandler?: () => void;

  oninit(vnode: Mithril.Vnode<T, this>) {
    super.oninit(vnode);

    this.searchState = this.attrs.state;
  }

  view() {
    const currentSearch = this.searchState.getInitialSearch();

    // Initialize search sources in the view rather than the constructor so
    // that we have access to app.forum.
    if (!this.sources) this.sources = this.sourceItems().toArray();

    // Hide the search view if no sources were loaded
    if (!this.sources.length) return <div></div>;

    const searchLabel = extractText(app.translator.trans('core.forum.header.search_placeholder'));

    const isActive = !!currentSearch;
    const shouldShowResults = !!(!this.loadingSources && this.searchState.getValue() && this.hasFocus);
    const shouldShowClearButton = !!(!this.loadingSources && this.searchState.getValue());

    return (
      <div
        role="search"
        aria-label={app.translator.trans('core.forum.header.search_role_label')}
        className={classList('Search', {
          open: this.searchState.getValue() && this.hasFocus,
          focused: this.hasFocus,
          active: isActive,
          loading: !!this.loadingSources,
        })}
      >
        <div className="Search-input">
          <input
            aria-label={searchLabel}
            className="FormControl"
            type="search"
            placeholder={searchLabel}
            value={this.searchState.getValue()}
            oninput={(e: InputEvent) => this.searchState.setValue((e?.target as HTMLInputElement)?.value)}
            onfocus={() => (this.hasFocus = true)}
            onblur={() => (this.hasFocus = false)}
          />
          {!!this.loadingSources && <LoadingIndicator size="small" display="inline" containerClassName="Button Button--icon Button--link" />}
          {shouldShowClearButton && (
            <button
              className="Search-clear Button Button--icon Button--link"
              onclick={this.clear.bind(this)}
              aria-label={app.translator.trans('core.forum.header.search_clear_button_accessible_label')}
              type="button"
            >
              {icon('fas fa-times-circle')}
            </button>
          )}
        </div>
        <ul
          className="Dropdown-menu Search-results"
          aria-hidden={!shouldShowResults || undefined}
          aria-live={shouldShowResults ? 'polite' : undefined}
        >
          {shouldShowResults && this.sources.map((source) => source.view(this.searchState.getValue()))}
        </ul>
      </div>
    );
  }

  updateMaxHeight() {
    // Since extensions might add elements above the search box on mobile,
    // we need to calculate and set the max height dynamically.
    const resultsElementMargin = 14;
    const maxHeight =
      window.innerHeight - this.element.querySelector('.Search-input>.FormControl')!.getBoundingClientRect().bottom - resultsElementMargin;

    this.element.querySelector<HTMLElement>('.Search-results')?.style?.setProperty('max-height', `${maxHeight}px`);
  }

  onupdate(vnode: Mithril.VnodeDOM<T, this>) {
    super.onupdate(vnode);

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    // If there are no sources, the search view is not shown.
    if (!this.sources?.length) return;

    this.updateMaxHeight();
  }

  oncreate(vnode: Mithril.VnodeDOM<T, this>) {
    super.oncreate(vnode);

    // If there are no sources, we shouldn't initialize logic for
    // search elements, as they will not be shown.
    if (!this.sources?.length) return;

    const search = this;
    const state = this.searchState;

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    this.$('.Search-results')
      .on('mousedown', (e) => e.preventDefault())
      .on('click', () => this.$('input').trigger('blur'))

      // Whenever the mouse is hovered over a search result, highlight it.
      .on('mouseenter', '> li:not(.Dropdown-header)', function () {
        search.setIndex(search.selectableItems().index(this));
      });

    const $input = this.$('input') as JQuery<HTMLInputElement>;

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
      .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
      .onSelect(this.selectResult.bind(this), true)
      .onCancel(this.clear.bind(this))
      .bindTo($input);

    // Handle input key events on the search input, triggering results to load.
    $input
      .on('input focus', function () {
        const query = this.value.toLowerCase();

        if (!query) return;

        if (search.searchTimeout) clearTimeout(search.searchTimeout);
        search.searchTimeout = setTimeout(() => {
          if (state.isCached(query)) return;

          if (query.length >= (search.constructor as typeof Search).MIN_SEARCH_LEN) {
            search.sources?.map((source) => {
              if (!source.search) return;

              search.loadingSources++;

              source.search(query).then(() => {
                search.loadingSources = Math.max(0, search.loadingSources - 1);
                m.redraw();
              });
            });
          }

          state.cache(query);
          m.redraw();
        }, 250);
      })

      .on('focus', function () {
        $(this)
          .one('mouseup', (e) => e.preventDefault())
          .trigger('select');
      });

    this.updateMaxHeightHandler = this.updateMaxHeight.bind(this);
    window.addEventListener('resize', this.updateMaxHeightHandler);
  }

  onremove(vnode: Mithril.VnodeDOM<T, this>) {
    super.onremove(vnode);

    if (this.updateMaxHeightHandler) {
      window.removeEventListener('resize', this.updateMaxHeightHandler);
    }
  }

  /**
   * Navigate to the currently selected search result and close the list.
   */
  selectResult() {
    if (this.searchTimeout) clearTimeout(this.searchTimeout);

    this.loadingSources = 0;

    const selectedUrl = this.getItem(this.index).find('a').attr('href');
    if (this.searchState.getValue() && selectedUrl) {
      m.route.set(selectedUrl);
    } else {
      this.clear();
    }

    this.$('input').blur();
  }

  /**
   * Clear the search
   */
  clear() {
    this.searchState.clear();
  }

  /**
   * Build an item list of SearchSources.
   */
  sourceItems(): ItemList<SearchSource> {
    const items = new ItemList<SearchSource>();

    if (app.forum.attribute('canViewForum')) items.add('discussions', new DiscussionsSearchSource());
    if (app.forum.attribute('canSearchUsers')) items.add('users', new UsersSearchSource());

    return items;
  }

  /**
   * Get all of the search result items that are selectable.
   */
  selectableItems(): JQuery {
    return this.$('.Search-results > li:not(.Dropdown-header)');
  }

  /**
   * Get the position of the currently selected search result item.
   * Returns zero if not found.
   */
  getCurrentNumericIndex(): number {
    return Math.max(0, this.selectableItems().index(this.getItem(this.index)));
  }

  /**
   * Get the <li> in the search results with the given index (numeric or named).
   */
  getItem(index: number): JQuery {
    const $items = this.selectableItems();
    let $item = $items.filter(`[data-index="${index}"]`);

    if (!$item.length) {
      $item = $items.eq(index);
    }

    return $item;
  }

  /**
   * Set the currently-selected search result item to the one with the given
   * index.
   */
  setIndex(index: number, scrollToItem: boolean = false) {
    const $items = this.selectableItems();
    const $dropdown = $items.parent();

    let fixedIndex = index;
    if (index < 0) {
      fixedIndex = $items.length - 1;
    } else if (index >= $items.length) {
      fixedIndex = 0;
    }

    const $item = $items.removeClass('active').eq(fixedIndex).addClass('active');

    this.index = parseInt($item.attr('data-index') as string) || fixedIndex;

    if (scrollToItem) {
      const dropdownScroll = $dropdown.scrollTop()!;
      const dropdownTop = $dropdown.offset()!.top;
      const dropdownBottom = dropdownTop + $dropdown.outerHeight()!;
      const itemTop = $item.offset()!.top;
      const itemBottom = itemTop + $item.outerHeight()!;

      let scrollTop;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'), 10);
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'), 10);
      }

      if (typeof scrollTop !== 'undefined') {
        $dropdown.stop(true).animate({ scrollTop }, 100);
      }
    }
  }
}
