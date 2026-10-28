import app from '../../app';
import Component, { type ComponentAttrs } from '../../Component';
import Button from '../../components/Button';
import LoadingIndicator from '../../components/LoadingIndicator';
import Switch from '../../components/Switch';
import type Model from '../../Model';
import type Stream from '../../utils/Stream';
import type Mithril from 'mithril';

export interface RelationOption {
  value: string;
  label: string;
  disabled?: boolean;
}

export interface RelationInputAttrs extends ComponentAttrs {
  /** The related resource type, e.g. `groups`. */
  type: string;
  multiple: boolean;
  /** The attribute shown as the option label. */
  display: string;
  stream: Stream<Model | Model[] | null>;
  name?: string;
  placeholder?: string;
  /** Offer an empty choice on single pickers. */
  nullable?: boolean;
  disabled?: boolean;
  /**
   * Show the search box, which sends `filter[q]` to the type's fulltext filter. Defaults to
   * `RelationInput.isSearchable(type)`.
   */
  search?: boolean;
  /** Rows per request; "Load more" fetches the next page. Default 20. */
  pageSize?: number;
}

/**
 * An async picker over `app.store.find(type, { filter: { q }, page })`. Single relations render a
 * select, multiple relations a list of switches; a search box (fulltext) narrows the options and
 * "Load more" appends the next page. The current selection is always offered, loaded or not.
 *
 * @experimental
 */
export default class RelationInput<Attrs extends RelationInputAttrs = RelationInputAttrs> extends Component<Attrs> {
  static SEARCH_DELAY = 250;
  static PAGE_SIZE = 20;

  /**
   * Related types whose list endpoint has no fulltext filter, so `filter[q]` would fail: their
   * pickers render without a search box unless the spec says otherwise. Empty here, since this
   * code knows no resource types; the code that owns a type registers it, e.g.
   * `RelationInput.UNSEARCHABLE_TYPES.add('tags')` (the admin user list registers core's groups).
   */
  static UNSEARCHABLE_TYPES = new Set<string>();

  /**
   * Whether pickers for `type` search by default.
   */
  static isSearchable(type: string): boolean {
    return !RelationInput.UNSEARCHABLE_TYPES.has(type);
  }

  isLoading: boolean = false;
  loaded: boolean = false;
  items: Model[] = [];
  /** Whether the API has rows after the loaded ones (a `next` link). */
  hasMore: boolean = false;
  query: string = '';

  protected searchTimeout: ReturnType<typeof setTimeout> | null = null;
  protected requestCount: number = 0;

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    if (this.attrs.multiple) {
      this.loadItems();
    }
  }

  onremove(vnode: Mithril.VnodeDOM<Attrs, this>) {
    super.onremove(vnode);

    if (this.searchTimeout) clearTimeout(this.searchTimeout);
  }

  view(): Mithril.Children {
    return (
      <div className="ResourceRelationInput">
        {this.searchable() && (
          <input
            className="FormControl ResourceRelationInput-search"
            type="search"
            value={this.query}
            placeholder={app.translator.trans('core.lib.search.placeholder', {}, true)}
            aria-label={app.translator.trans('core.lib.search.placeholder', {}, true)}
            disabled={this.attrs.disabled}
            oninput={(e: InputEvent) => this.search((e.target as HTMLInputElement).value)}
            onkeydown={(e: KeyboardEvent) => this.onsearchkeydown(e)}
          />
        )}
        {this.attrs.multiple ? this.multipleView() : this.singleView()}
        {this.hasMore && this.moreButton()}
      </div>
    );
  }

  searchable(): boolean {
    return this.attrs.search ?? RelationInput.isSearchable(this.attrs.type);
  }

  singleView(): Mithril.Children {
    const selected = this.selected()[0] ?? null;
    const value = selected ? selected.id() : '';

    // A native select over ordered options: core's Select takes an object, which would put
    // integer-like ids first and lose the search relevance and "Load more" order.
    return (
      <span className="Select">
        <select
          className="Select-input FormControl"
          name={this.attrs.name}
          value={value}
          disabled={this.attrs.disabled}
          onchange={(e: Event) => this.select((e.target as HTMLSelectElement).value)}
          onfocus={() => this.loaded || this.loadItems()}
        >
          {this.options().map((option) => (
            <option value={option.value} selected={option.value === value} disabled={option.disabled}>
              {option.label}
            </option>
          ))}
        </select>
      </span>
    );
  }

  multipleView(): Mithril.Children {
    if (this.isLoading && !this.items.length) {
      return <LoadingIndicator display="inline" size="small" />;
    }

    const selectedIds = this.selected().map((item) => item.id());

    return (
      <div className="ResourceRelationInput-list">
        {this.candidates().map((item) => (
          <div key={item.id()} className="ResourceRelationInput-item">
            <Switch state={selectedIds.includes(item.id())} disabled={this.attrs.disabled} onchange={() => this.toggle(item)}>
              {this.itemLabel(item)}
            </Switch>
          </div>
        ))}
      </div>
    );
  }

  moreButton(): Mithril.Children {
    return (
      <Button
        className="Button Button--link ResourceRelationInput-more"
        loading={this.isLoading}
        disabled={this.attrs.disabled}
        onclick={() => this.loadMore()}
      >
        {app.translator.trans('core.lib.resource.relation.load_more')}
      </Button>
    );
  }

  itemLabel(item: Model): string {
    return String(item.attribute(this.attrs.display) ?? item.id());
  }

  selected(): Model[] {
    const value = this.attrs.stream();

    if (Array.isArray(value)) return value;

    return value ? [value] : [];
  }

  /** Loaded items plus the current selection, without duplicates. */
  candidates(): Model[] {
    const ids = new Set(this.items.map((item) => item.id()));

    return [...this.selected().filter((item) => !ids.has(item.id())), ...this.items];
  }

  /**
   * Enter searches right away instead of submitting the form the picker sits in.
   */
  onsearchkeydown(e: KeyboardEvent) {
    if (e.key !== 'Enter') return;

    e.preventDefault();

    if (this.searchTimeout) clearTimeout(this.searchTimeout);

    this.loadItems();
  }

  /**
   * The single picker's options in display order: the empty choice, the candidates, then a
   * disabled loading row.
   */
  options(): RelationOption[] {
    const options: RelationOption[] = [];

    if (this.attrs.nullable || !this.selected().length) {
      options.push({ value: '', label: this.attrs.placeholder ?? '' });
    }

    this.candidates().forEach((item) => options.push({ value: item.id()!, label: this.itemLabel(item) }));

    if (this.isLoading) {
      options.push({ value: 'loading', label: app.translator.trans('core.lib.loading_indicator.accessible_label', {}, true), disabled: true });
    }

    return options;
  }

  select(id: string) {
    this.attrs.stream(this.candidates().find((item) => item.id() === id) ?? null);
  }

  toggle(item: Model) {
    const current = this.selected();

    this.attrs.stream(current.some((i) => i.id() === item.id()) ? current.filter((i) => i.id() !== item.id()) : [...current, item]);
  }

  search(query: string) {
    this.query = query;
    // The loaded rows belong to the previous query; "Load more" would append at their offset.
    this.hasMore = false;

    if (this.searchTimeout) clearTimeout(this.searchTimeout);

    this.searchTimeout = setTimeout(() => this.loadItems(), RelationInput.SEARCH_DELAY);
  }

  /**
   * One page of rows from `offset`, and whether the API has more (a `next` link).
   */
  fetchPage(offset: number): Promise<{ items: Model[]; hasMore: boolean }> {
    const params: Record<string, unknown> = { page: { offset, limit: this.attrs.pageSize ?? RelationInput.PAGE_SIZE } };

    if (this.query) params.filter = { q: this.query };

    return app.store.find<Model[]>(this.attrs.type, params).then((results) => ({
      items: [...results],
      hasMore: !!results.payload?.links?.next,
    }));
  }

  /** The first page for the current query, replacing what was loaded. */
  loadItems(): Promise<void> {
    return this.load(false);
  }

  /** The next page, appended. */
  loadMore(): Promise<void> {
    return this.load(true);
  }

  protected async load(append: boolean): Promise<void> {
    // Only the latest request may land: a slow earlier response must not overwrite newer results.
    const request = ++this.requestCount;

    this.isLoading = true;

    try {
      const page = await this.fetchPage(append ? this.items.length : 0);

      if (request !== this.requestCount) return;

      const known = new Set(append ? this.items.map((item) => item.id()) : []);
      this.items = append ? [...this.items, ...page.items.filter((item) => !known.has(item.id()))] : page.items;
      this.hasMore = page.hasMore;
    } catch (error) {
      console.error(`Error loading ${this.attrs.type} for ${this.attrs.name ?? 'relation'}:`, error);
    } finally {
      if (request === this.requestCount) {
        this.isLoading = false;
        this.loaded = true;
        m.redraw();
      }
    }
  }
}
