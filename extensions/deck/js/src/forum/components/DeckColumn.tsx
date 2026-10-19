import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Dropdown from 'flarum/common/components/Dropdown';
import Icon from 'flarum/common/components/Icon';
import Separator from 'flarum/common/components/Separator';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import { rowOf } from '../states/DeckState';
import type DeckState from '../states/DeckState';
import type DeckColumnState from '../states/DeckColumnState';
import { DEFAULT_WIDTH, MAX_WIDTH, MIN_WIDTH, widthToShow } from '../utils/deckSizes';

export interface IDeckColumnAttrs extends ComponentAttrs {
  deck: DeckState;
  column: DeckColumnState;
  /** Shown in the single strip phones and short screens get, rather than in a row. */
  flat?: boolean;
}

/** Keyboard resizing, in pixels; with Shift held, the larger step. */
const RESIZE_STEP = 20;
const RESIZE_STEP_LARGE = 80;

export default class DeckColumn<CustomAttrs extends IDeckColumnAttrs = IDeckColumnAttrs> extends Component<CustomAttrs> {
  protected previousHeight = 0;
  /** While the edge is being dragged: where it started, and what the column is sized against. */
  protected resizing: { startX: number; startShown: number; others: number; row: number } | null = null;
  protected previousTop = 0;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    // Coming back to the deck: something may have been read or unfollowed meanwhile.
    this.attrs.column.prune();
    this.attrs.column.load().then(() => m.redraw());
    this.attrs.column.source.start?.();
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    this.body().scrollTop = this.attrs.column.scrollTop;

    // After core's own handler, which places the menu for the window only.
    this.$('.DeckColumn-menu')
      .on('shown.bs.dropdown', () => this.pinMenu())
      .on('hidden.bs.dropdown', () => this.unpinMenu());
  }

  onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onbeforeupdate(vnode);

    // Measured before Mithril patches the DOM, so onupdate can tell how much
    // was inserted above the reader.
    const body = this.body();
    this.previousHeight = body.scrollHeight;
    this.previousTop = body.scrollTop;
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    // Realtime items arrive at the top. Someone reading further down keeps
    // their place; someone at the top sees them appear.
    if (this.attrs.column.consumeTopInsert() && this.previousTop > 0) {
      const body = this.body();
      body.scrollTop = this.previousTop + (body.scrollHeight - this.previousHeight);
    }
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    this.unpinMenu();

    this.attrs.column.scrollTop = this.body().scrollTop;
    this.attrs.column.source.stop?.();
  }

  view() {
    const { column } = this.attrs;
    const { config, type } = column;

    return (
      <section
        // Extension column keys contain dots, which a class selector can't.
        className={classList('DeckColumn', `DeckColumn--${config.type.replace(/[^\w-]/g, '-')}`)}
        // Variables, so the phone layout can still give every column the screen.
        style={{ '--deck-column-width': `${config.width}px`, '--deck-column-grow': config.width }}
        data-column-id={config.id}
        aria-label={column.title()}
      >
        <header className="DeckColumn-header">
          <span
            className="DeckColumn-handle"
            title={extractText(app.translator.trans('flarum-deck.forum.column.drag_handle_label', { title: column.title() }))}
          >
            <Icon name="fas fa-grip-vertical" />
          </span>
          <h2 className="DeckColumn-title">
            {type.badge?.(config) || <Icon name={type.icon} className="DeckColumn-icon" />}
            <span className="DeckColumn-titleText">{column.title()}</span>
          </h2>
          <div className="DeckColumn-controls">{this.controlItems().toArray()}</div>
        </header>

        {column.newCount > 0 && (
          <div className="DeckColumn-new">
            <Button className="Button Button--primary Button--rounded DeckColumn-newButton" icon="fas fa-arrow-up" onclick={() => this.showNew()}>
              {app.translator.trans('flarum-deck.forum.column.new_items_button', { count: column.newCount })}
            </Button>
          </div>
        )}

        <div className="DeckColumn-body">{column.source.view()}</div>

        {this.resizeHandle()}
      </section>
    );
  }

  controlItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'menu',
      <Dropdown
        className="DeckColumn-menu"
        buttonClassName="Button Button--icon Button--flat"
        menuClassName="Dropdown-menu--right"
        icon="fas fa-ellipsis-vertical"
        accessibleToggleLabel={extractText(app.translator.trans('flarum-deck.forum.column.controls_label', { title: this.attrs.column.title() }))}
      >
        {this.menuItems().toArray()}
      </Dropdown>
    );

    return items;
  }

  menuItems(): ItemList<Mithril.Children> {
    const { deck, column } = this.attrs;
    const items = new ItemList<Mithril.Children>();
    const id = column.config.id;

    items.add(
      'refresh',
      <Button icon="fas fa-rotate" onclick={() => this.showNew()}>
        {app.translator.trans('flarum-deck.forum.column.refresh_button')}
      </Button>,
      100
    );

    const sourceControls = new ItemList<Mithril.Children>();
    column.source.controls?.(sourceControls);

    if (!sourceControls.isEmpty()) {
      items.add('sourceControls', sourceControls.toArray(), 90);
    }

    items.add('separator', <Separator />, 50);

    // Widths only apply side by side; the phone layout hides this.
    if (column.config.width !== DEFAULT_WIDTH) {
      items.add(
        'resetWidth',
        <Button icon="fas fa-arrows-left-right" onclick={() => deck.setWidth(id, DEFAULT_WIDTH)}>
          {app.translator.trans('flarum-deck.forum.column.reset_width_button')}
        </Button>,
        20
      );
    }

    items.add('separator3', <Separator />, 17);

    // The keyboard and screen reader way to rearrange, as dragging needs a pointer.
    const flat = !!this.attrs.flat;
    // Left and right as the reader sees them: in a right-to-left layout, left is later.
    const right = this.isRtl() ? -1 : 1;

    if (deck.canMoveBy(id, -right, flat)) {
      items.add(
        'moveLeft',
        <Button icon="fas fa-arrow-left" onclick={() => this.move(() => deck.moveBy(id, -right, flat))}>
          {app.translator.trans('flarum-deck.forum.column.move_left_button')}
        </Button>,
        16
      );
    }

    if (deck.canMoveBy(id, right, flat)) {
      items.add(
        'moveRight',
        <Button icon="fas fa-arrow-right" onclick={() => this.move(() => deck.moveBy(id, right, flat))}>
          {app.translator.trans('flarum-deck.forum.column.move_right_button')}
        </Button>,
        15.5
      );
    }

    const row = rowOf(column.config);
    const targetRow = row === 0 ? 1 : 0;

    items.add(
      'moveRow',
      <Button
        icon={targetRow === 1 ? 'fas fa-arrow-down' : 'fas fa-arrow-up'}
        onclick={() => this.move(() => deck.moveTo(id, targetRow, deck.rows()[targetRow].length))}
      >
        {targetRow === 1
          ? app.translator.trans('flarum-deck.forum.column.move_to_bottom_row_button')
          : app.translator.trans('flarum-deck.forum.column.move_to_top_row_button')}
      </Button>,
      15
    );

    items.add('separator2', <Separator />, 10);

    items.add(
      'remove',
      <Button icon="fas fa-xmark" onclick={() => deck.removeColumn(id)}>
        {app.translator.trans('flarum-deck.forum.column.remove_button')}
      </Button>,
      0
    );

    return items;
  }

  /**
   * Moves the column from its menu, then says where it went and gives focus
   * back to its menu, which the move took out of the page and put back.
   */
  protected move(callback: () => void): void {
    const { deck, column } = this.attrs;
    const id = column.config.id;
    const flat = !!this.attrs.flat;

    callback();

    const place = deck.placeOf(id, flat);

    if (place) {
      const position = place.index + 1;
      const count = place.count;

      deck.announcement = extractText(
        flat
          ? app.translator.trans('flarum-deck.forum.column.moved_announcement', { position, count })
          : place.row === 0
          ? app.translator.trans('flarum-deck.forum.column.moved_in_top_row_announcement', { position, count })
          : app.translator.trans('flarum-deck.forum.column.moved_in_bottom_row_announcement', { position, count })
      );
    }

    // After the dropdown has closed, which would otherwise take focus back.
    setTimeout(() => {
      m.redraw.sync();
      document.querySelector<HTMLElement>(`.DeckColumn[data-column-id="${id}"] .DeckColumn-menu .Dropdown-toggle`)?.focus();
    });
  }

  /** The column's edge: drag it, or focus it and use the arrow keys, to resize. */
  protected resizeHandle(): Mithril.Children {
    const { deck, column } = this.attrs;

    return (
      <div
        className="DeckColumn-resize"
        role="separator"
        aria-orientation="vertical"
        tabindex="0"
        aria-label={extractText(app.translator.trans('flarum-deck.forum.column.resize_label', { title: column.title() }))}
        aria-valuemin={MIN_WIDTH}
        aria-valuemax={MAX_WIDTH}
        aria-valuenow={column.config.width}
        onpointerdown={(e: PointerEvent) => this.startResize(e)}
        onpointermove={(e: PointerEvent & { redraw?: boolean }) => this.resize(e)}
        onpointerup={() => this.endResize()}
        onpointercancel={() => this.endResize()}
        onkeydown={(e: KeyboardEvent & { redraw?: boolean }) => this.resizeByKey(e)}
        ondblclick={() => deck.setWidth(column.config.id, DEFAULT_WIDTH)}
      />
    );
  }

  protected startResize(e: PointerEvent): void {
    if (e.button !== 0) return;

    e.preventDefault();
    (e.currentTarget as HTMLElement).setPointerCapture?.(e.pointerId);

    const { deck, column } = this.attrs;

    this.resizing = {
      startX: e.clientX,
      startShown: this.element.getBoundingClientRect().width,
      others: deck.neighbours(column.config.id, !!this.attrs.flat).reduce((sum, other) => sum + other.config.width, 0),
      row: (this.element.parentElement as HTMLElement).clientWidth,
    };

    this.element.classList.add('DeckColumn--resizing');
  }

  protected resize(e: PointerEvent & { redraw?: boolean }): void {
    // Moving over the edge without dragging it changes nothing.
    e.redraw = false;

    if (!this.resizing) return;

    const { startX, startShown, others, row } = this.resizing;
    const shown = startShown + (e.clientX - startX) * (this.isRtl() ? -1 : 1);

    this.attrs.deck.setWidth(this.attrs.column.config.id, widthToShow(shown, others, row), false);
  }

  protected endResize(): void {
    if (!this.resizing) return;

    this.resizing = null;
    this.element.classList.remove('DeckColumn--resizing');

    const { config } = this.attrs.column;
    this.attrs.deck.setWidth(config.id, config.width);
  }

  protected resizeByKey(e: KeyboardEvent & { redraw?: boolean }): void {
    const { config } = this.attrs.column;
    const step = e.shiftKey ? RESIZE_STEP_LARGE : RESIZE_STEP;
    const wider = this.isRtl() ? 'ArrowLeft' : 'ArrowRight';
    const narrower = this.isRtl() ? 'ArrowRight' : 'ArrowLeft';

    const width = { [wider]: config.width + step, [narrower]: config.width - step, Home: DEFAULT_WIDTH }[e.key];

    if (width === undefined) {
      e.redraw = false;
      return;
    }

    e.preventDefault();
    this.attrs.deck.setWidth(config.id, width);
  }

  /**
   * A row scrolls sideways, so it clips anything that overflows it, and a
   * column's menu would be cut off at the bottom of its row. Pinning it to the
   * window instead lets it overlap the row below, opening upwards when there's
   * more room there and scrolling when there's room for neither.
   */
  protected pinMenu(): void {
    const menu = this.menu();
    const toggle = this.element.querySelector<HTMLElement>('.DeckColumn-menu > .Dropdown-toggle');

    // Phones get core's sheet along the bottom of the screen instead.
    if (!menu || !toggle || getComputedStyle(menu).position !== 'absolute') return;

    const gap = 4;
    const edge = 8;
    const rect = toggle.getBoundingClientRect();
    const below = window.innerHeight - rect.bottom - gap - edge;
    const above = rect.top - gap - edge;
    const up = menu.scrollHeight > below && above > below;

    menu.classList.remove('Dropdown-menu--top');

    Object.assign(menu.style, {
      position: 'fixed',
      margin: '0',
      top: up ? 'auto' : `${rect.bottom + gap}px`,
      bottom: up ? `${window.innerHeight - rect.top + gap}px` : 'auto',
      left: this.isRtl() ? `${rect.left}px` : 'auto',
      right: this.isRtl() ? 'auto' : `${document.documentElement.clientWidth - rect.right}px`,
      maxHeight: `${Math.max(up ? above : below, 0)}px`,
      overflowY: 'auto',
    });

    window.addEventListener('scroll', this.onScrollWhilePinned, true);
    window.addEventListener('resize', this.closeMenu);
  }

  protected unpinMenu(): void {
    this.menu()?.removeAttribute('style');

    window.removeEventListener('scroll', this.onScrollWhilePinned, true);
    window.removeEventListener('resize', this.closeMenu);
  }

  protected menu(): HTMLElement | null {
    return this.element.querySelector<HTMLElement>('.DeckColumn-menu > .Dropdown-menu');
  }

  /** A pinned menu would stay put while the deck moved under it. */
  protected onScrollWhilePinned = (e: Event): void => {
    if (!this.menu()?.contains(e.target as Node)) this.closeMenu();
  };

  protected closeMenu = (): void => {
    const dropdown = this.$('.DeckColumn-menu');

    // @ts-ignore - missing dropdown types
    if (dropdown.hasClass('open')) dropdown.find('.Dropdown-toggle').dropdown('toggle');
  };

  protected isRtl(): boolean {
    return getComputedStyle(this.element ?? document.documentElement).direction === 'rtl';
  }

  showNew() {
    this.body().scrollTop = 0;
    this.attrs.column.showNew();
  }

  protected body(): HTMLElement {
    return this.element.querySelector('.DeckColumn-body') as HTMLElement;
  }
}
