import app from '../app';
import FormModal from '../../common/components/FormModal';
import type { IFormModalAttrs } from '../../common/components/FormModal';
import type Mithril from 'mithril';
import type SearchState from '../../common/states/SearchState';
import KeyboardNavigatable from '../../common/utils/KeyboardNavigatable';
import SearchManager from '../../common/SearchManager';
import extractText from '../../common/utils/extractText';
import Input from '../../common/components/Input';
import Button from '../../common/components/Button';
import Stream from '../../common/utils/Stream';
import InfoTile from '../../common/components/InfoTile';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import type { SearchSource } from './Search';

export interface ISearchModalAttrs extends IFormModalAttrs {
  onchange: (value: string) => void;
  searchState: SearchState;
  sources: SearchSource[];
}

export default class SearchModal<CustomAttrs extends ISearchModalAttrs = ISearchModalAttrs> extends FormModal<CustomAttrs> {
  public static LIMIT = 6;

  protected searchState!: SearchState;

  /**
   * An array of SearchSources.
   */
  protected sources!: SearchSource[];

  /**
   * The key of the currently-active search source.
   */
  protected activeSource!: Stream<SearchSource>;

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

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.searchState = this.attrs.searchState;
    this.sources = this.attrs.sources;
  }

  title(): Mithril.Children {
    return app.translator.trans('core.forum.search.title');
  }

  className(): string {
    return 'SearchModal Modal--flat';
  }

  content(): Mithril.Children {
    // Initialize the active source.
    if (!this.activeSource) this.activeSource = Stream(this.sources[0]);

    const searchLabel = extractText(app.translator.trans('core.forum.search.placeholder'));

    return (
      <div className="Modal-body SearchModal-body">
        <div className="SearchModal-form">
          <Input
            type="search"
            loading={!!this.loadingSources.length}
            clearable={true}
            clearLabel={app.translator.trans('core.forum.header.search_clear_button_accessible_label')}
            prefixIcon="fas fa-search"
            aria-label={searchLabel}
            placeholder={searchLabel}
            value={this.searchState.getValue()}
            onchange={(value: string) => this.searchState.setValue(value)}
            inputAttrs={{ className: 'SearchModal-input' }}
          />
        </div>
        {this.tabs()}
      </div>
    );
  }

  tabs(): JSX.Element {
    return (
      <div className="SearchModal-tabs">
        <div className="SearchModal-tabs-nav">
          {this.sources?.map((source) => (
            <Button className="Button Button--link" active={this.activeSource() === source} onclick={() => this.switchSource(source)}>
              {source.title()}
            </Button>
          ))}
        </div>
        {this.activeTab()}
      </div>
    );
  }

  activeTab(): JSX.Element {
    const loading = this.loadingSources.includes(this.activeSource().resource);
    const shouldShowResults = !!this.searchState.getValue() && !loading;
    const shouldShowOptions = false;
    const fullPageLink = this.activeSource().fullPage(this.searchState.getValue());
    const results = this.activeSource()?.view(this.searchState.getValue());

    return (
      <div className="SearchModal-tabs-content">
        {shouldShowResults && fullPageLink && (
          <div className="SearchModal-section">
            <hr className="Modal-divider" />
            <ul className="Dropdown-menu SearchModal-fullPage">{fullPageLink}</ul>
          </div>
        )}
        {shouldShowOptions && (
          <div className="SearchModal-section">
            <ul className="Dropdown-menu SearchModal-options" aria-live={shouldShowOptions ? 'polite' : undefined}>
              <li className="Dropdown-header">{app.translator.trans('core.forum.search.options_heading')}</li>
            </ul>
          </div>
        )}
        <div className="SearchModal-section">
          <hr className="Modal-divider" />
          <ul className="Dropdown-menu SearchModal-results" aria-live={shouldShowResults ? 'polite' : undefined}>
            <li className="Dropdown-header">{app.translator.trans('core.forum.search.preview_heading')}</li>
            {!shouldShowResults && (
              <li className="Dropdown-message">
                <InfoTile icon="fas fa-search">{app.translator.trans('core.forum.search.no_search_text')}</InfoTile>
              </li>
            )}
            {shouldShowResults && results}
            {shouldShowResults && !results?.length && (
              <li className="Dropdown-message">
                <InfoTile icon="far fa-tired">{app.translator.trans('core.forum.search.no_results_text')}</InfoTile>
              </li>
            )}
            {loading && (
              <li className="Dropdown-message">
                <LoadingIndicator />
              </li>
            )}
          </ul>
        </div>
      </div>
    );
  }

  switchSource(source: SearchSource) {
    if (this.activeSource() !== source) {
      this.activeSource(source);
      this.search(this.searchState.getValue());
      this.$('input').focus();
      m.redraw();
    }
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    // If there are no sources, the search view is not shown.
    if (!this.sources?.length) return;
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // If there are no sources, we shouldn't initialize logic for
    // search elements, as they will not be shown.
    if (!this.sources?.length) return;

    const component = this;
    const search = this.search.bind(this);

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    this.$('.Dropdown-menu')
      // Whenever the mouse is hovered over a search result, highlight it.
      .on('mouseenter', '> li:not(.Dropdown-header):not(.Dropdown-message)', function () {
        component.setIndex(component.selectableItems().index(this));
      });

    const $input = this.$('input') as JQuery<HTMLInputElement>;

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
      .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
      .onRight(() => this.switchSource(this.sources![(this.sources!.indexOf(this.activeSource()) + 1) % this.sources!.length]))
      .onLeft(() => this.switchSource(this.sources![(this.sources!.indexOf(this.activeSource()) - 1 + this.sources!.length) % this.sources!.length]))
      .onSelect(this.selectResult.bind(this), true)
      .onCancel(this.clear.bind(this))
      .bindTo($input);

    // Handle input key events on the search input, triggering results to load.
    $input
      .on('input focus', function () {
        search(this.value.toLowerCase());
      })

      .on('focus', function () {
        $(this)
          .one('mouseup', (e) => e.preventDefault())
          .trigger('select');
      });
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
}
