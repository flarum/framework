import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';
import type Sortable from 'sortablejs';
import DeckColumn from './DeckColumn';
import type DeckState from '../states/DeckState';
import type DeckColumnState from '../states/DeckColumnState';

export interface IDeckRowAttrs extends ComponentAttrs {
  deck: DeckState;
  columns: DeckColumnState[];
  /** The row's share of the deck's height, with two rows. */
  grow?: number;
  /** Which row this is, or null for the single strip phones and short screens get. */
  row: number | null;
  /** SortableJS, once its chunk has loaded; null until then, or to turn dragging off. */
  sortable: typeof Sortable | null;
  scrollLeft: number;
  onscrolled: (scrollLeft: number) => void;
  ondrag: (dragging: boolean) => void;
  onoverflow: (overflowing: boolean) => void;
  /** Phone only: the index of the column filling the screen. */
  onactive?: (index: number) => void;
}

/**
 * One horizontally scrolling strip of columns. It shows which way there's
 * more (edge fades and scroll buttons), and lets columns be dragged within it
 * and to the other row.
 */
export default class DeckRow<CustomAttrs extends IDeckRowAttrs = IDeckRowAttrs> extends Component<CustomAttrs> {
  protected sortableInstance: Sortable | null = null;
  protected resizeObserver: ResizeObserver | null = null;
  protected overflowing: boolean | null = null;
  protected active = 0;

  view() {
    const { columns, deck, row } = this.attrs;

    return (
      <div className={classList('DeckRow', { 'DeckRow--empty': !columns.length })} style={{ '--deck-row-grow': this.attrs.grow ?? 1 }}>
        <Button
          className="Button Button--icon DeckRow-scroll DeckRow-scroll--left"
          icon="fas fa-chevron-left"
          aria-label={extractText(app.translator.trans('flarum-deck.forum.page.scroll_left_label'))}
          onclick={() => this.scroll(-1)}
        />

        <div className="DeckRow-columns" data-row={row ?? ''} onscroll={(e: Event & { redraw?: boolean }) => this.onscroll(e)}>
          {columns.map((column) => (
            <DeckColumn key={column.config.id} deck={deck} column={column} flat={row === null} />
          ))}
          {!columns.length && <div className="DeckRow-dropHint">{app.translator.trans('flarum-deck.forum.page.drop_row_hint')}</div>}
        </div>

        <Button
          className="Button Button--icon DeckRow-scroll DeckRow-scroll--right"
          icon="fas fa-chevron-right"
          aria-label={extractText(app.translator.trans('flarum-deck.forum.page.scroll_right_label'))}
          onclick={() => this.scroll(1)}
        />
      </div>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    this.container().scrollLeft = this.attrs.scrollLeft;

    this.resizeObserver = new ResizeObserver(() => this.updateScrollState());
    this.resizeObserver.observe(this.container());

    this.setUpSortable();
    this.updateScrollState();
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    this.setUpSortable();
    this.updateScrollState();
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    this.attrs.onscrolled(this.container().scrollLeft);
    this.resizeObserver?.disconnect();
    this.sortableInstance?.destroy();
  }

  protected container(): HTMLElement {
    return this.element.querySelector('.DeckRow-columns') as HTMLElement;
  }

  protected scroll(direction: -1 | 1): void {
    const container = this.container();

    container.scrollBy({ left: direction * container.clientWidth * 0.8, behavior: 'smooth' });
  }

  protected onscroll(e: Event & { redraw?: boolean }): void {
    e.redraw = false;

    this.updateScrollState();

    if (this.attrs.onactive) {
      const container = this.container();
      const index = Math.round(Math.abs(container.scrollLeft) / Math.max(1, container.clientWidth));

      if (index !== this.active) {
        this.active = index;
        this.attrs.onactive(index);
      }
    }
  }

  /**
   * Toggled on the element directly: this runs on every scroll frame, and
   * nothing else on the page depends on it.
   */
  protected updateScrollState(): void {
    const container = this.container();
    const max = container.scrollWidth - container.clientWidth;
    const position = Math.abs(container.scrollLeft);
    const rtl = getComputedStyle(container).direction === 'rtl';
    const moreAtStart = position > 1;
    const moreAtEnd = position < max - 1;

    this.element.classList.toggle('DeckRow--moreLeft', rtl ? moreAtEnd : moreAtStart);
    this.element.classList.toggle('DeckRow--moreRight', rtl ? moreAtStart : moreAtEnd);

    const overflowing = max > 1;

    if (overflowing !== this.overflowing) {
      this.overflowing = overflowing;
      this.attrs.onoverflow(overflowing);
    }
  }

  protected setUpSortable(): void {
    const Sortable = this.attrs.sortable;

    if (!Sortable) {
      this.sortableInstance?.destroy();
      this.sortableInstance = null;
      return;
    }

    if (this.sortableInstance) return;

    this.sortableInstance = Sortable.create(this.container(), {
      group: 'flarum-deck-columns',
      handle: '.DeckColumn-handle',
      draggable: '.DeckColumn',
      direction: 'horizontal',
      animation: 150,
      ghostClass: 'DeckColumn--ghost',
      chosenClass: 'DeckColumn--chosen',
      onStart: () => this.attrs.ondrag(true),
      onEnd: (event) => {
        this.attrs.ondrag(false);

        const { item, from, to, oldIndex = 0, newIndex = 0 } = event;

        // Put the element back where it was and let Mithril re-render from the
        // new layout; Sortable and Mithril both moving it would disagree.
        const sameList = from === to;
        from.insertBefore(item, from.children[sameList && oldIndex > newIndex ? oldIndex + 1 : oldIndex] ?? null);

        if (sameList && event.oldDraggableIndex === event.newDraggableIndex) return;

        const id = item.dataset.columnId!;
        const index = event.newDraggableIndex ?? 0;
        const row = (to as HTMLElement).dataset.row;

        if (row === '') {
          this.attrs.deck.moveFlat(id, index);
        } else {
          this.attrs.deck.moveTo(id, Number(row), index);
        }
      },
    });
  }
}
