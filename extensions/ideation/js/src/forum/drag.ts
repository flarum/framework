/**
 * Dragging an idea card to another column, with a mouse or a finger.
 *
 * Adapted from Bug Tracker's board drag. Pointer events rather than HTML5
 * drag-and-drop: `draggable` + dragstart/drop never fires on touch, so a board
 * built on it is dead on a phone. The ghost is positioned inline, because
 * it is a clone of the card and would otherwise match the card's own rules
 * and sit in the layout instead of under the pointer.
 *
 * A mouse can pick a card up anywhere on it. A finger or pen picks it up only
 * by its handle: the handle has `touch-action: none`, so the browser never
 * turns that drag into a scroll and cancels it, while a swipe anywhere else on
 * the card scrolls the board as usual.
 */

const COLUMN = '[data-ideation-column]';
const CARD = '[data-ideation-idea]';
const HANDLE = '[data-ideation-handle]';

/** A deliberate movement, or every click or tap reads as a drag. */
const SLOP_PX = 5;

export default class BoardDrag {
  protected ghost: HTMLElement | null = null;
  protected source: HTMLElement | null = null;
  protected id = '';
  protected startX = 0;
  protected startY = 0;
  protected offsetX = 0;
  protected offsetY = 0;
  protected armed = false;
  protected dragging = false;
  protected over: string | null = null;

  constructor(
    protected root: HTMLElement,
    protected onOver: (column: string | null) => void,
    protected onDrop: (id: string, column: string) => void
  ) {
    this.down = this.down.bind(this);
    this.move = this.move.bind(this);
    this.up = this.up.bind(this);
    root.addEventListener('pointerdown', this.down);
  }

  destroy() {
    this.root.removeEventListener('pointerdown', this.down);
    this.cleanup();
  }

  protected down(e: PointerEvent) {
    if (e.button !== 0 && e.pointerType === 'mouse') return;

    const target = e.target as HTMLElement;
    const card = target.closest?.(CARD) as HTMLElement | null;

    // A link or button inside a card is a link or a button, not a handle;
    // and a moderator whose permission is scoped to some tags moves only
    // the ideas in them.
    if (!card || !card.hasAttribute('data-ideation-movable') || target.closest('a, button, input')) return;
    if (e.pointerType !== 'mouse' && !target.closest(HANDLE)) return;

    this.source = card;
    this.id = card.getAttribute('data-ideation-idea') || '';
    this.startX = e.clientX;
    this.startY = e.clientY;

    const rect = card.getBoundingClientRect();
    this.offsetX = e.clientX - rect.left;
    this.offsetY = e.clientY - rect.top;
    this.armed = true;

    window.addEventListener('pointermove', this.move, { passive: false });
    window.addEventListener('pointerup', this.up);
    window.addEventListener('pointercancel', this.up);
  }

  protected begin(x: number, y: number) {
    if (this.dragging || !this.source) return;

    this.dragging = true;

    const rect = this.source.getBoundingClientRect();
    const ghost = this.source.cloneNode(true) as HTMLElement;

    Object.assign(ghost.style, {
      position: 'fixed',
      left: rect.left + 'px',
      top: rect.top + 'px',
      width: rect.width + 'px',
      margin: '0',
      pointerEvents: 'none',
      zIndex: '2147483000',
      transform: 'rotate(1.5deg) scale(1.02)',
      boxShadow: '0 12px 28px var(--shadow-color)',
    });

    document.body.appendChild(ghost);
    this.ghost = ghost;
    this.source.classList.add('IdeationCard--dragging');
    document.body.classList.add('IdeationDragging');

    this.place(x, y);
  }

  protected move(e: PointerEvent) {
    if (!this.armed) return;

    if (!this.dragging) {
      if (Math.abs(e.clientX - this.startX) <= SLOP_PX && Math.abs(e.clientY - this.startY) <= SLOP_PX) return;
      this.begin(e.clientX, e.clientY);
    }

    // Once a card is in the air the page must not scroll under it.
    e.preventDefault();
    this.place(e.clientX, e.clientY);

    const column = (Array.from(this.root.querySelectorAll(COLUMN)) as HTMLElement[]).find((c) => {
      const r = c.getBoundingClientRect();
      return e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
    });

    const over = column?.getAttribute('data-ideation-column') || null;

    if (over !== this.over) {
      this.over = over;
      this.onOver(over);
    }
  }

  protected place(x: number, y: number) {
    if (!this.ghost) return;
    this.ghost.style.left = x - this.offsetX + 'px';
    this.ghost.style.top = y - this.offsetY + 'px';
  }

  protected up() {
    const dropped = this.dragging ? this.over : null;
    const id = this.id;

    this.cleanup();

    if (dropped) this.onDrop(id, dropped);
  }

  protected cleanup() {
    window.removeEventListener('pointermove', this.move);
    window.removeEventListener('pointerup', this.up);
    window.removeEventListener('pointercancel', this.up);

    this.ghost?.remove();
    this.ghost = null;
    this.source?.classList.remove('IdeationCard--dragging');
    this.source = null;
    document.body.classList.remove('IdeationDragging');

    if (this.over !== null) this.onOver(null);

    this.armed = false;
    this.dragging = false;
    this.over = null;
  }
}
