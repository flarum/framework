import app from '../app';
import type { IFormModalAttrs } from './FormModal';
import FormModal from './FormModal';
import type Mithril from 'mithril';
import type SearchState from '../states/SearchState';
import KeyboardNavigatable from '../utils/KeyboardNavigatable';
import SearchManager from '../SearchManager';
import extractText from '../utils/extractText';
import Input from './Input';
import Button from './Button';
import Stream from '../utils/Stream';
import InfoTile from './InfoTile';
import LoadingIndicator from './LoadingIndicator';
import type IGambit from '../query/IGambit';
import ItemList from '../utils/ItemList';
import GambitsAutocomplete from '../utils/GambitsAutocomplete';
import type { GlobalSearchSource } from './AbstractGlobalSearch';

export interface ISearchModalAttrs extends IFormModalAttrs {
  onchange: (value: string) => void;
  searchState: SearchState;
  sources: GlobalSearchSource[];
}

export default class SearchModal<CustomAttrs extends ISearchModalAttrs = ISearchModalAttrs> extends FormModal<CustomAttrs> {
  public static LIMIT = 6;

  protected searchState!: SearchState;

  protected query!: Stream<string>;

  /**
   * An array of SearchSources.
   */
  protected sources!: GlobalSearchSource[];

  /**
   * The key of the currently-active search source.
   */
  protected activeSource!: Stream<GlobalSearchSource>;

  /**
   * The sources that are still loading results.
   */
  protected loadingSources: string[] = [];

  /**
   * The index of the currently-selected <li> in the results list. This can be
   * a unique string (to account for the fact that an item's position may jump
   * around as new results load), but otherwise it will be numeric (the
   * sequential position within the list).
   */
  protected index: number = 0;

  protected navigator!: KeyboardNavigatable;

  protected searchTimeout?: number;

  protected inputScroll = Stream(0);

  protected gambitsAutocomplete: Record<string, GambitsAutocomplete> = {};

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.searchState = this.attrs.searchState;
    this.sources = this.attrs.sources;
    this.activeSource = Stream(
      this.defaultActiveSource() ? this.sources.find((source) => source.resource === this.defaultActiveSource()) || this.sources[0] : this.sources[0]
    );
    this.query = Stream(this.prefill(this.searchState.getValue() || '').trim());
  }

  title(): Mithril.Children {
    return app.translator.trans('core.lib.search.title');
  }

  className(): string {
    return 'SearchModal Modal--flat';
  }

  content(): Mithril.Children {
    this.gambitsAutocomplete[this.activeSource().resource] ||= new GambitsAutocomplete(
      this.activeSource().resource,
      () => this.inputElement(),
      this.query,
      (value: string) => this.search(value)
    );

    const searchLabel = extractText(app.translator.trans('core.lib.search.placeholder'));

    return (
      <div className="Modal-body SearchModal-body">
        <div className="SearchModal-form">
          <Input
            key="search"
            type="search"
            loading={!!this.loadingSources.length}
            clearable={true}
            clearLabel={app.translator.trans('core.lib.search.search_clear_button_accessible_label')}
            prefixIcon="fas fa-search"
            aria-label={searchLabel}
            placeholder={searchLabel}
            value={this.query()}
            onchange={(value: string) => {
              this.query(value);
              this.inputScroll(this.inputElement()[0]?.scrollLeft ?? 0);
            }}
            inputAttrs={{ className: 'SearchModal-input' }}
            renderInput={(attrs: any) => (
              <>
                <input {...attrs} onscroll={(e: Event) => this.inputScroll((e.target as HTMLInputElement).scrollLeft)} />
                <div className="SearchModal-visual-wrapper">
                  <div className="SearchModal-visual-input" style={{ left: '-' + this.inputScroll() + 'px' }}>
                    {this.gambifyInput()}
                  </div>
                </div>
              </>
            )}
          />
        </div>
        {this.tabs()}
      </div>
    );
  }

  tabs(): JSX.Element {
    return (
      <div className="Tabs">
        <div className="Tabs-nav">{this.tabItems().toArray()}</div>
        <div className="Tabs-content SearchModal-tabs-content">{this.activeTabItems().toArray()}</div>
      </div>
    );
  }

  tabItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    this.sources?.map((source, index) =>
      items.add(
        source.resource,
        <Button className="Button Button--link" active={this.activeSource() === source} onclick={() => this.switchSource(source)}>
          {source.title()}
        </Button>,
        100 - index
      )
    );

    return items;
  }

  activeTabItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const loading = this.loadingSources.includes(this.activeSource().resource);
    const shouldShowResults = !!this.query() && !loading;
    const gambits = this.gambits();
    const fullPageLink = this.activeSource().fullPage(this.query());
    const results = this.activeSource()?.view(this.query());
    const customGrouping = this.activeSource().customGrouping();

    if (shouldShowResults && fullPageLink) {
      items.add(
        'fullPageLink',
        <div className="SearchModal-section">
          <hr className="Modal-divider" />
          <ul className="Dropdown-menu SearchModal-fullPage">{fullPageLink}</ul>
        </div>,
        80
      );
    }

    if (!!gambits.length) {
      items.add(
        'gambits',
        <div className="SearchModal-section">
          <hr className="Modal-divider" />
          <ul className="Dropdown-menu SearchModal-options" aria-live={gambits.length ? 'polite' : undefined}>
            <li className="Dropdown-header">{app.translator.trans('core.lib.search.options_heading')}</li>
            {gambits}
          </ul>
        </div>,
        60
      );
    }

    items.add(
      'results',
      <div className="SearchModal-section">
        <hr className="Modal-divider" />
        <ul className="Dropdown-menu SearchModal-results" aria-live={shouldShowResults ? 'polite' : undefined}>
          {!customGrouping && <li className="Dropdown-header">{app.translator.trans('core.lib.search.preview_heading')}</li>}
          {!shouldShowResults && (
            <li className="Dropdown-message">
              <InfoTile icon="fas fa-search">{app.translator.trans('core.lib.search.no_search_text')}</InfoTile>
            </li>
          )}
          {shouldShowResults && results}
          {shouldShowResults && !results?.length && (
            <li className="Dropdown-message">
              <InfoTile icon="far fa-tired">{app.translator.trans('core.lib.search.no_results_text')}</InfoTile>
            </li>
          )}
          {loading && (
            <li className="Dropdown-message">
              <LoadingIndicator />
            </li>
          )}
        </ul>
      </div>,
      40
    );

    return items;
  }

  switchSource(source: GlobalSearchSource) {
    if (this.activeSource() !== source) {
      this.activeSource(source);
      this.search(this.query());
      this.inputElement().focus();
      m.redraw();
    }
  }

  gambits(): JSX.Element[] {
    return this.gambitsAutocomplete[this.activeSource().resource].suggestions(this.query());
  }

  /**
   * Transforms a simple search text to wrap valid gambits in a mark tag.
   * @example `lorem ipsum is:unread dolor` => `lorem ipsum <mark>is:unread</mark> dolor`
   */
  gambifyInput(): Mithril.Children {
    const query = this.query();
    let marked = query;

    app.search.gambits.match(this.activeSource().resource, query, (gambit: IGambit, matches: string[], negate: boolean, bit: string) => {
      marked = marked.replace(bit, `<mark>${bit}</mark>`);
    });

    const jsx: Mithril.ChildArray = [];
    marked.split(/(<mark>.*?<\/mark>)/).forEach((chunk) => {
      if (chunk.startsWith('<mark>')) {
        jsx.push(<mark>{chunk.replace(/<\/?mark>/g, '')}</mark>);
      } else {
        jsx.push(chunk);
      }
    });

    return jsx;
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    const component = this;
    this.$('.Dropdown-menu')
      // Whenever the mouse is hovered over a search result, highlight it.
      .on('mouseenter', '> li:not(.Dropdown-header):not(.Dropdown-message)', function () {
        component.setIndex(component.selectableItems().index(this));
      });

    // If there are no sources, the search view is not shown.
    if (!this.sources?.length) return;
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // If there are no sources, we shouldn't initialize logic for
    // search elements, as they will not be shown.
    if (!this.sources?.length) return;

    const search = this.search.bind(this);

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    const $input = this.inputElement() as JQuery<HTMLInputElement>;

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
      .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
      .onSelect(this.selectResult.bind(this), true)
      .onCancel(this.clear.bind(this))
      .bindTo($input);

    // Handle input key events on the search input, triggering results to load.
    $input.on('input focus', function () {
      search(this.value.toLowerCase());
    });
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    this.searchState.setValue(this.query());
    super.onremove(vnode);
  }

  search(query: string) {
    if (!query) return;

    const source = this.activeSource();

    if (this.searchTimeout) clearTimeout(this.searchTimeout);

    this.searchTimeout = window.setTimeout(() => {
      if (source.isCached(query)) return;

      if (query.length >= SearchManager.MIN_SEARCH_LEN) {
        if (!source.search) return;

        this.loadingSources.push(source.resource);

        source.search(query, SearchModal.LIMIT).then(() => {
          this.loadingSources = this.loadingSources.filter((resource) => resource !== source.resource);
          m.redraw();
        });
      }

      this.searchState.cache(query);
      m.redraw();
    }, 250);
  }

  /**
   * Navigate to the currently selected search result and close the list.
   */
  selectResult() {
    if (this.searchTimeout) clearTimeout(this.searchTimeout);

    this.loadingSources = [];

    const item = this.getItem(this.index);
    const isResult = !!item.attr('data-id');
    let selectedUrl = null;

    if (isResult) {
      const id = item.attr('data-id');
      selectedUrl = id && this.activeSource().gotoItem(id as string);
    } else if (item.find('a').length) {
      selectedUrl = item.find('a').attr('href');
    }

    const query = this.query();

    if (query && selectedUrl) {
      m.route.set(selectedUrl);
    } else {
      item.find('button')[0].click();
    }
  }

  /**
   * Clear the search
   */
  clear() {
    this.query('');
  }

  /**
   * Get all of the search result items that are selectable.
   */
  selectableItems(): JQuery {
    return this.$('.Dropdown-menu > li:not(.Dropdown-header):not(.Dropdown-message)');
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

    if (scrollToItem && $dropdown) {
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

  inputElement(): JQuery<HTMLInputElement> {
    return this.$('.SearchModal-input') as JQuery<HTMLInputElement>;
  }

  defaultActiveSource(): string | null {
    const inDiscussion =
      app.current.data.routeName && ['discussion', 'discussion.near'].includes(app.current.data.routeName) && app.current.data.discussion;
    const inUser = app.current.data.routeName && app.current.data.routeName.includes('user.posts') && app.current.data.user;
    const inPosts = app.current.data.routeName && app.current.data.routeName === 'posts';

    if (inDiscussion || inUser || inPosts) {
      return 'posts';
    }

    return null;
  }

  defaultFilters(): Record<string, Record<string, any>> {
    const filters: Record<string, Record<string, any>> = {};

    this.sources.forEach((source) => {
      filters[source.resource] = {};
    });

    if (app.current.data.routeName && ['discussion', 'discussion.near'].includes(app.current.data.routeName) && app.current.data.discussion) {
      filters.posts.discussion = app.current.data.discussion.id();
    }

    if (app.current.data.routeName && app.current.data.routeName.includes('user.posts') && app.current.data.user) {
      filters.posts.author = app.current.data.user.username();
    }

    return filters;
  }

  prefill(value: string): string {
    const newQuery = app.search.gambits.from(this.activeSource().resource, value, this.defaultFilters()[this.activeSource().resource] || {});

    if (!value.includes(newQuery.replace(value, '').trim())) {
      return newQuery;
    }

    return value;
  }
}
