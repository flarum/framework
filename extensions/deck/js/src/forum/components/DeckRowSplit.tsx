import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';
import type DeckState from '../states/DeckState';
import { MAX_SPLIT, MIN_SPLIT } from '../utils/deckSizes';

export interface IDeckRowSplitAttrs extends ComponentAttrs {
  deck: DeckState;
}

/** Keyboard resizing, as a share of the deck's height. */
const STEP = 0.05;

/**
 * The divider between the two rows: drag it, or focus it and use the arrow
 * keys, to share the height between them. Double-click (or Home) evens it.
 */
export default class DeckRowSplit<CustomAttrs extends IDeckRowSplitAttrs = IDeckRowSplitAttrs> extends Component<CustomAttrs> {
  protected dragging = false;

  view() {
    const split = this.attrs.deck.rowSplit();

    return (
      <div
        className="DeckRowSplit"
        role="separator"
        aria-orientation="horizontal"
        tabindex="0"
        aria-label={extractText(app.translator.trans('flarum-deck.forum.page.resize_rows_label'))}
        aria-valuemin={Math.round(MIN_SPLIT * 100)}
        aria-valuemax={Math.round(MAX_SPLIT * 100)}
        aria-valuenow={Math.round(split * 100)}
        onpointerdown={(e: PointerEvent) => this.start(e)}
        onpointermove={(e: PointerEvent & { redraw?: boolean }) => this.move(e)}
        onpointerup={() => this.end()}
        onpointercancel={() => this.end()}
        onkeydown={(e: KeyboardEvent & { redraw?: boolean }) => this.key(e)}
        ondblclick={() => this.attrs.deck.setRowSplit(null)}
      />
    );
  }

  protected start(e: PointerEvent): void {
    if (e.button !== 0) return;

    e.preventDefault();
    (e.currentTarget as HTMLElement).setPointerCapture?.(e.pointerId);

    this.dragging = true;
    this.element.classList.add('DeckRowSplit--dragging');
  }

  protected move(e: PointerEvent & { redraw?: boolean }): void {
    e.redraw = false;

    if (!this.dragging) return;

    const deck = (this.element.parentElement as HTMLElement).getBoundingClientRect();

    this.attrs.deck.setRowSplit((e.clientY - deck.top) / deck.height, false);
  }

  protected end(): void {
    if (!this.dragging) return;

    this.dragging = false;
    this.element.classList.remove('DeckRowSplit--dragging');
    this.attrs.deck.setRowSplit(this.attrs.deck.rowSplit());
  }

  protected key(e: KeyboardEvent & { redraw?: boolean }): void {
    const split = this.attrs.deck.rowSplit();
    const value = { ArrowUp: split - STEP, ArrowDown: split + STEP, Home: null }[e.key];

    if (value === undefined) {
      e.redraw = false;
      return;
    }

    e.preventDefault();
    this.attrs.deck.setRowSplit(value);
  }
}
