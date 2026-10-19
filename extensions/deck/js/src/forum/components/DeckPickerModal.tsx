import app from 'flarum/forum/app';
import SearchModal, { type ISearchModalAttrs } from 'flarum/common/components/SearchModal';
import SearchState from 'flarum/common/states/SearchState';
import type { GlobalSearchSource } from 'flarum/common/components/AbstractGlobalSearch';
import Icon from 'flarum/common/components/Icon';
import ItemList from 'flarum/common/utils/ItemList';
import classList from 'flarum/common/utils/classList';
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
  static open(
    source: GlobalSearchSource,
    attrs: { title: Mithril.Children; onpick: (model: Model) => void; browse?: boolean; isAdded?: (model: Model) => boolean }
  ): void {
    app.modal.show(DeckPickerModal, { ...attrs, sources: [source], searchState: new SearchState(), onchange: () => {} }, true);
  }

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    if (this.attrs.isAdded) {
      const marked = new Map(this.sources.map((source) => [source, this.marking(source)]));

      this.sources = [...marked.values()];
      // A stream of the original; point it at the marked one.
      this.activeSource(marked.get(this.activeSource())!);
    }
  }

  className(): string {
    return super.className() + ' DeckPickerModal';
  }

  /**
   * The source as it is, with results the deck already holds marked. Core's
   * sources render each result themselves, so the mark goes on afterwards: a
   * class on the item, and a badge after its content.
   */
  protected marking(source: GlobalSearchSource): GlobalSearchSource {
    const picker = this;
    const marked = Object.create(source) as GlobalSearchSource;

    marked.view = function (query: string) {
      const results = source.view.call(this, query);

      return (Array.isArray(results) ? results : [results]).map((result: any) => {
        const id = result?.attrs?.['data-id'];
        const model = id ? app.store.getById(source.resource, id) : null;

        if (!model || !picker.attrs.isAdded!(model)) return result;

        const children = result.text != null ? [result.text] : result.children ?? [];

        return m(result.tag, { ...result.attrs, key: result.key, className: classList(result.attrs.className, 'DeckPickerModal-result--added') }, [
          ...children,
          <span className="DeckPickerModal-added">
            <Icon name="fas fa-check" /> {app.translator.trans('flarum-deck.forum.add_column.in_deck_badge')}
          </span>,
        ]);
      });
    };

    return marked;
  }

  title(): Mithril.Children {
    return this.attrs.title;
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // Captured, so a result's own link never gets the click.
    this.element.addEventListener('click', this.onResultClick, true);
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    this.element.removeEventListener('click', this.onResultClick, true);

    super.onremove(vnode);
  }

  /** One source, so no tabs. */
  tabItems(): ItemList<Mithril.Children> {
    return this.sources.length < 2 ? new ItemList<Mithril.Children>() : super.tabItems();
  }

  activeTabItems(): ItemList<Mithril.Children> {
    const items = super.activeTabItems();

    // "Search all discussions" would leave the page.
    if (items.has('fullPageLink')) items.remove('fullPageLink');

    if (this.attrs.browse && !this.query()) {
      items.setContent(
        'results',
        <div className="SearchModal-section">
          <hr className="Modal-divider" />
          <ul className="Dropdown-menu SearchModal-results">{this.activeSource().view('')}</ul>
        </div>
      );
    }

    return items;
  }

  /** Filters narrow a search, not a choice of one result. */
  gambits(): JSX.Element[] {
    return [];
  }

  gambifyInput(): Mithril.Children {
    return this.query();
  }

  prefill(value: string): string {
    return value;
  }

  defaultActiveSource(): string | null {
    return null;
  }

  defaultFilters(): Record<string, Record<string, any>> {
    return {};
  }

  selectResult() {
    if (this.searchTimeout) clearTimeout(this.searchTimeout);

    const id = this.getItem(this.index).attr('data-id');

    if (id) this.pick(id);
  }

  protected onResultClick = (e: Event): void => {
    const result = (e.target as HTMLElement).closest<HTMLElement>('.SearchModal-results li[data-id]');

    if (!result) return;

    e.preventDefault();
    e.stopPropagation();
    this.pick(result.dataset.id!);
  };

  protected pick(id: string): void {
    const model = app.store.getById(this.activeSource().resource, id);

    if (!model) return;

    this.attrs.onpick(model);
    this.hide();
  }
}
