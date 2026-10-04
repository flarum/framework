import app from 'flarum/forum/app';
import Page, { type IPageAttrs } from 'flarum/common/components/Page';
import DeckPageStructure from './DeckPageStructure';
import DeckSidebar from './DeckSidebar';
import DeckPageHero from './DeckPageHero';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type Sortable from 'sortablejs';
import DeckRow from './DeckRow';
import DeckState from '../states/DeckState';
import { canUseDeck } from '../utils/deckLayout';
import type DeckColumnState from '../states/DeckColumnState';

export interface IDeckPageAttrs extends IPageAttrs {}

/** Below this height two rows of columns are too cramped, so they share one strip. */
const SHORT_SCREEN = '(max-height: 640px)';

/** Kept between visits so the deck is exactly as the member left it. */
let deck: { userId: string; state: DeckState; scrollLeft: Record<string, number> } | null = null;

export default class DeckPage<CustomAttrs extends IDeckPageAttrs = IDeckPageAttrs> extends Page<CustomAttrs> {
  protected deck!: DeckState;
  protected observer: IntersectionObserver | null = null;
  protected sortable: typeof Sortable | null = null;
  protected chipSortable: Sortable | null = null;
  protected media: MediaQueryList[] = [];
  /** Rows, by key, whose columns don't fit across the screen. */
  protected overflowing = new Set<string>();
  /** The column filling the screen in the phone layout. */
  protected activeIndex = 0;
  /** Once the hero is gone the toolbar names the page instead. */
  protected heroDismissed = DeckPageHero.isHidden();

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    if (!canUseDeck()) {
      m.route.set(app.route('index'));
      return;
    }

    this.bodyClass = 'App--deck';

    // The tag list would turn the horizontal nav into a very long strip.
    app.current.set('noTagsList', true);

    const userId = app.session.user!.id()!;

    if (deck?.userId !== userId) {
      deck = { userId, state: new DeckState(), scrollLeft: {} };
    } else {
      deck.state.sync();
    }

    this.deck = deck.state;

    const title = extractText(app.translator.trans('flarum-deck.forum.page.title'));
    app.setTitle(title);
    app.setTitleCount(0);
    app.history.push('deck', title);
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    if (!this.deck) return;

    // Columns just off screen count as visible, so swiping or scrolling to a
    // neighbour shows it already up to date.
    this.observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          const column = this.columnFor(entry.target as HTMLElement);

          if (!column) return;

          column.visible = entry.isIntersecting;

          if (column.visible && column.stale) column.check(this.deck.isLive());
        });
      },
      { rootMargin: '0px 100% 0px 100%' }
    );

    this.media = [window.matchMedia(this.phoneQuery()), window.matchMedia(SHORT_SCREEN)];
    this.media.forEach((query) => query.addEventListener('change', this.onLayoutChange));

    import('../utils/loadSortable').then(({ default: Sortable }) => {
      this.sortable = Sortable;
      m.redraw();
    });

    this.observeColumns();
    this.deck.start();
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    this.observeColumns();
    this.setUpChipSortable();
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    if (!this.deck) return;

    this.observer?.disconnect();
    this.chipSortable?.destroy();
    this.media.forEach((query) => query.removeEventListener('change', this.onLayoutChange));
    this.deck.stop();
  }

  view() {
    if (!this.deck) return <div />;

    const columns = this.deck.columns();
    const phone = this.isPhone();
    const flat = phone || this.isShort();
    const showTabs = columns.length > 1 && (phone || this.overflowing.size > 0);

    return (
      // Page--vertical puts core's side nav above the content, as on the Tags
      // and Messages pages, so the forum's navigation stays to hand without
      // costing the deck any width.
      <DeckPageStructure
        className={classList('DeckPage Page--vertical', { 'DeckPage--flat': flat, 'DeckPage--phone': phone })}
        hero={() => <DeckPageHero ondismiss={() => (this.heroDismissed = true) && m.redraw()} />}
        sidebar={() => <DeckSidebar canAddColumn={this.deck.canAddColumn()} onaddcolumn={() => this.openAddColumn()} />}
        deck={() => [
          <div className="DeckPage-toolbar">
            {this.heroDismissed ? (
              <h1 className="DeckPage-title">
                <Icon name="fas fa-table-columns" />
                {app.translator.trans('flarum-deck.forum.page.title')}
              </h1>
            ) : (
              <span />
            )}
            <div className="DeckPage-actions">{this.actionItems().toArray()}</div>
          </div>,

          showTabs && (
            <nav className="DeckPage-tabs" aria-label={extractText(app.translator.trans('flarum-deck.forum.page.navigator_label'))}>
              {this.tabs(columns, phone)}
            </nav>
          ),

          columns.length ? (
            <div className="Deck">
              {flat ? this.row('flat', null, columns, phone) : this.deck.rows().map((row, i) => this.row(`row${i}`, i, row, false))}
            </div>
          ) : (
            <div className="DeckPage-empty">
              <p>{app.translator.trans('flarum-deck.forum.page.empty_text')}</p>
              {this.addButton()}
            </div>
          ),
        ]}
      />
    );
  }

  protected row(key: string, row: number | null, columns: DeckColumnState[], phone: boolean): Mithril.Children {
    return (
      <DeckRow
        key={key}
        deck={this.deck}
        row={row}
        columns={columns}
        // On phones columns fill the screen and swipe; they're reordered with the chips instead.
        sortable={phone ? null : this.sortable}
        scrollLeft={deck!.scrollLeft[key] ?? 0}
        onscrolled={(left: number) => (deck!.scrollLeft[key] = left)}
        ondrag={(dragging: boolean) => this.element.querySelector('.Deck')?.classList.toggle('Deck--dragging', dragging)}
        onoverflow={(overflowing: boolean) => {
          const had = this.overflowing.has(key);

          overflowing ? this.overflowing.add(key) : this.overflowing.delete(key);

          if (had !== overflowing) m.redraw();
        }}
        onactive={
          phone
            ? (index: number) => {
                this.activeIndex = index;
                m.redraw();
              }
            : undefined
        }
      />
    );
  }

  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'reset',
      <Button
        className="Button DeckPage-reset"
        icon="fas fa-rotate-left"
        disabled={!this.deck.isCustomised()}
        aria-label={extractText(app.translator.trans('flarum-deck.forum.page.reset_button'))}
        onclick={() => {
          if (confirm(extractText(app.translator.trans('flarum-deck.forum.page.reset_confirmation')))) {
            this.deck.reset();
          }
        }}
      >
        <span className="DeckPage-actionLabel">{app.translator.trans('flarum-deck.forum.page.reset_button')}</span>
      </Button>,
      90
    );

    return items;
  }

  protected addButton(): Mithril.Children {
    const canAdd = this.deck.canAddColumn();

    return (
      <Button
        className="Button Button--primary DeckPage-add"
        icon="fas fa-plus"
        disabled={!canAdd}
        aria-label={extractText(app.translator.trans('flarum-deck.forum.page.add_column_button'))}
        title={canAdd ? undefined : extractText(app.translator.trans('flarum-deck.forum.page.column_limit_reached_text'))}
        onclick={() => this.openAddColumn()}
      >
        <span className="DeckPage-actionLabel">{app.translator.trans('flarum-deck.forum.page.add_column_button')}</span>
      </Button>
    );
  }

  protected openAddColumn(): void {
    app.modal.show(() => import('./AddColumnModal'), {
      deck: this.deck,
      onadd: (config: { id: string }) => setTimeout(() => this.scrollToColumn(config.id), 50),
    });
  }

  /**
   * A chip per column with its pending count: on phones the way to switch (and,
   * dragged, to reorder) columns; elsewhere a way to jump to columns scrolled
   * out of sight.
   */
  protected tabs(columns: DeckColumnState[], phone: boolean): Mithril.Children {
    return columns.map((column, index) => {
      const active = phone && index === this.activeIndex;

      return (
        <Button
          className={classList('Button Button--rounded DeckPage-tab', { 'Button--primary': active })}
          data-column-id={column.config.id}
          aria-current={active ? 'true' : undefined}
          onclick={() => this.scrollToColumn(column.config.id)}
        >
          {column.title()}
          {column.newCount > 0 && <span className="Bubble Bubble--primary DeckPage-tabCount">{column.newCount}</span>}
        </Button>
      );
    });
  }

  /** On phones, chips are dragged to reorder (after a short press, so a tap still switches). */
  protected setUpChipSortable(): void {
    const nav = this.element.querySelector<HTMLElement>('.DeckPage-tabs');
    const wanted = this.sortable && nav && this.isPhone() ? nav : null;

    if (this.chipSortable && (!wanted || this.chipSortable.el !== wanted)) {
      this.chipSortable.destroy();
      this.chipSortable = null;
    }

    if (!wanted || this.chipSortable) return;

    this.chipSortable = this.sortable!.create(wanted, {
      draggable: '.DeckPage-tab',
      direction: 'horizontal',
      animation: 150,
      delay: 250,
      delayOnTouchOnly: true,
      ghostClass: 'DeckPage-tab--ghost',
      onEnd: (event) => {
        const { item, from, oldIndex = 0, newIndex = 0 } = event;

        from.insertBefore(item, from.children[oldIndex > newIndex ? oldIndex + 1 : oldIndex] ?? null);

        if (oldIndex !== newIndex) this.deck.moveFlat(item.dataset.columnId!, newIndex);
      },
    });
  }

  protected onLayoutChange = (): void => {
    this.overflowing.clear();
    m.redraw();
  };

  /** Core's phone breakpoint, read from its CSS variable so themes that move it are followed. */
  protected phoneQuery(): string {
    const max = getComputedStyle(document.documentElement).getPropertyValue('--screen-phone-max').trim() || '767.98px';

    return `(max-width: ${max})`;
  }

  protected isPhone(): boolean {
    return this.media[0]?.matches ?? window.matchMedia(this.phoneQuery()).matches;
  }

  protected isShort(): boolean {
    return this.media[1]?.matches ?? window.matchMedia(SHORT_SCREEN).matches;
  }

  protected scrollToColumn(id: string): void {
    const element = this.element.querySelector(`.DeckColumn[data-column-id="${id}"]`);

    element?.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
  }

  protected observeColumns(): void {
    this.element.querySelectorAll('.DeckColumn').forEach((element) => this.observer?.observe(element));
  }

  protected columnFor(element: HTMLElement): DeckColumnState | undefined {
    return this.deck.columns().find((column) => column.config.id === element.dataset.columnId);
  }
}
