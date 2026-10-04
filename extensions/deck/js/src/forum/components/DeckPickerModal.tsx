import app from 'flarum/forum/app';
import SearchModal, { type ISearchModalAttrs } from 'flarum/common/components/SearchModal';
import SearchState from 'flarum/common/states/SearchState';
import type { GlobalSearchSource } from 'flarum/common/components/AbstractGlobalSearch';
import ItemList from 'flarum/common/utils/ItemList';
import type Model from 'flarum/common/Model';
import type Mithril from 'mithril';

export interface IDeckPickerModalAttrs extends ISearchModalAttrs {
  title: Mithril.Children;
  /** Called with the chosen result's model, after which the picker closes. */
  onpick: (model: Model) => void;
  /** List every result before anything is typed, for short lists such as tags. */
  browse?: boolean;
}

/**
 * The forum's search modal, choosing a result instead of opening it, so every
 * Deck picker looks and works like search. Results are told apart by their
 * `data-id`, looked up in the store under the source's resource.
 */
export default class DeckPickerModal<CustomAttrs extends IDeckPickerModalAttrs = IDeckPickerModalAttrs> extends SearchModal<CustomAttrs> {
  /** Opens a picker over whatever modal is open. */
  static open(source: GlobalSearchSource, attrs: { title: Mithril.Children; onpick: (model: Model) => void; browse?: boolean }): void {
    app.modal.show(DeckPickerModal, { ...attrs, sources: [source], searchState: new SearchState(), onchange: () => {} }, true);
  }

  className(): string {
    return super.className() + ' DeckPickerModal';
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
