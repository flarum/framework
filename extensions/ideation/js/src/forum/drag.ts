/**
 * Dragging an idea card to another column, with a mouse or a finger.
 *
 * Adapted from Bug Tracker's board drag. 🚨 Pointer events rather than HTML5
 * drag-and-drop: `draggable` + dragstart/drop never fires on touch, so a board
 * built on it is dead on a phone. 🚨 The ghost is positioned inline, because
 * it is a clone of the card and would otherwise match the card's own rules
 * and sit in the layout instead of under the pointer.
 */

const COLUMN = '[data-rm-column]';
const CARD = '[data-rm-idea]';

/** Touch needs a deliberate hold, or scrolling the board picks up a card. */
const HOLD_MS = 180;
/** A mouse needs a deliberate movement, or every click reads as a drag. */
const SLOP_PX = 5;

export default class BoardDrag {
  private ghost: HTMLElement | null = null;
  private source: HTMLElement | null = null;
  private id = '';
  private startX = 0;
  private startY = 0;
  private offsetX = 0;
  private offsetY = 0;
  private armed = false;
  private dragging = false;
  private holdTimer: number | null = null;
  private over: string | null = null;

  constructor(private root: HTMLElement, private onOver: (column: string | null) => void, private onDrop: (id: string, column: string) => void) {
    this.down = this.down.bind(this);
    this.move = this.move.bind(this);
    this.up = this.up.bind(this);
    root.addEventListener('pointerdown', this.down);
  }

  destroy() {
    this.root.removeEventListener('pointerdown', this.down);
    this.cleanup();
  }

  private down(e: PointerEvent) {
    if (e.button !== 0 && e.pointerType === 'mouse') return;

    const target = e.target as HTMLElement;
    const card = target.closest?.(CARD) as HTMLElement | null;

    // A link or button inside a card is a link or a button, not a handle.
    if (!card || target.closest('a, button, input')) return;

    this.source = card;
    this.id = card.getAttribute('data-rm-idea') || '';
    this.startX = e.clientX;
    this.startY = e.clientY;

    const rect = card.getBoundingClientRect();
    this.offsetX = e.clientX - rect.left;
    this.offsetY = e.clientY - rect.top;
    this.armed = true;

    window.addEventListener('pointermove', this.move, { passive: false });
    window.addEventListener('pointerup', this.up);
    window.addEventListener('pointercancel', this.up);

    if (e.pointerType !== 'mouse') {
      this.holdTimer = window.setTimeout(() => this.begin(e.clientX, e.clientY), HOLD_MS);
    }
  }

  private begin(x: number, y: number) {
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
      boxShadow: '0 12px 28px rgba(0,0,0,.28)',
    });

    document.body.appendChild(ghost);
    this.ghost = ghost;
    this.source.classList.add('IdeationCard--dragging');
    document.body.classList.add('IdeationDragging');

    this.place(x, y);
  }

  private move(e: PointerEvent) {
    if (!this.armed) return;

    if (!this.dragging) {
      const far = Math.abs(e.clientX - this.startX) > SLOP_PX || Math.abs(e.clientY - this.startY) > SLOP_PX;

      if (e.pointerType !== 'mouse') {
        // Moved before the hold elapsed: that was a scroll, not a pick-up.
        if (far) this.cleanup();
        return;
      }

      if (!far) return;
      this.begin(e.clientX, e.clientY);
    }

    // Once a card is in the air the page must not scroll under it.
    e.preventDefault();
    this.place(e.clientX, e.clientY);

    const column = (Array.from(this.root.querySelectorAll(COLUMN)) as HTMLElement[]).find((c) => {
      const r = c.getBoundingClientRect();
      return e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
    });

    const over = column?.getAttribute('data-rm-column') || null;

    if (over !== this.over) {
      this.over = over;
      this.onOver(over);
    }
  }

  private place(x: number, y: number) {
    if (!this.ghost) return;
    this.ghost.style.left = x - this.offsetX + 'px';
    this.ghost.style.top = y - this.offsetY + 'px';
  }

  private up() {
    const dropped = this.dragging ? this.over : null;
    const id = this.id;

    this.cleanup();

    if (dropped) this.onDrop(id, dropped);
  }

  private cleanup() {
    if (this.holdTimer) window.clearTimeout(this.holdTimer);
    this.holdTimer = null;

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
