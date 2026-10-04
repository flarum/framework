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
import type { DeckColumnWidth } from '../columns/DeckColumnType';

export interface IDeckColumnAttrs extends ComponentAttrs {
  deck: DeckState;
  column: DeckColumnState;
  /** Shown in the single strip phones and short screens get, rather than in a row. */
  flat?: boolean;
}

const widthLabels = (): Record<DeckColumnWidth, Mithril.Children> => ({
  narrow: app.translator.trans('flarum-deck.forum.column.width_narrow_button'),
  normal: app.translator.trans('flarum-deck.forum.column.width_normal_button'),
  wide: app.translator.trans('flarum-deck.forum.column.width_wide_button'),
});

export default class DeckColumn<CustomAttrs extends IDeckColumnAttrs = IDeckColumnAttrs> extends Component<CustomAttrs> {
  protected previousHeight = 0;
  protected previousTop = 0;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.attrs.column.load().then(() => m.redraw());
    this.attrs.column.source.start?.();
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    this.body().scrollTop = this.attrs.column.scrollTop;
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

    this.attrs.column.scrollTop = this.body().scrollTop;
    this.attrs.column.source.stop?.();
  }

  view() {
    const { column } = this.attrs;
    const { config, type } = column;

    return (
      <section
        // Extension column keys contain dots, which a class selector can't.
        className={classList('DeckColumn', `DeckColumn--${config.type.replace(/[^\w-]/g, '-')}`, `DeckColumn--${config.width}`)}
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

    // Widths only apply side by side; the phone layout hides these items.
    Object.entries(widthLabels()).forEach(([width, label], i) => {
      items.add(
        `width-${width}`,
        <Button icon={column.config.width === width ? 'fas fa-check' : true} onclick={() => deck.setWidth(id, width as DeckColumnWidth)}>
          {label}
        </Button>,
        20 - i
      );
    });

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
