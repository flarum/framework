import deckColumnTypes from '../columns/deckColumnTypes';
import { MIN_CHECK_INTERVAL } from './DeckState';
import type { DeckColumnConfig, DeckColumnSource, DeckColumnType, DeckRealtimeEvent } from '../columns/DeckColumnType';

/** What a column without its own `onRealtime()` is checked on. */
const DEFAULT_TRIGGERS = ['Flarum\\Discussion\\Event\\Started', 'Flarum\\Post\\Event\\Posted'];
/** How long a burst of events settles before a column asks the server. */
const CHECK_DEBOUNCE = 3000;
/** Spreads out the decks that all hear about the same post at the same moment. */
const JITTER = 5000;

export default class DeckColumnState {
  public readonly type: DeckColumnType;
  public readonly source: DeckColumnSource;

  /** Items waiting behind the "new" pill. */
  public newCount = 0;
  /** Something may have changed while the column was off screen. */
  public stale = false;
  public visible = false;
  public scrollTop = 0;

  /** Items were just inserted above the reader; the component keeps their place. */
  protected insertedAtTop = false;

  protected checking: Promise<void> | null = null;
  protected checkTimer: number | null = null;
  protected lastChecked = 0;
  protected loaded: Promise<unknown> | null = null;

  constructor(public config: DeckColumnConfig) {
    this.type = deckColumnTypes.get(config.type);
    this.source = this.type.createSource(config);
  }

  title(): string {
    return this.type.title(this.config);
  }

  load(): Promise<unknown> {
    return (this.loaded ??= this.source.load().catch(() => {
      this.loaded = null;
    }));
  }

  /**
   * Hands a realtime event to the source. Most are handled from the payload
   * alone; those only the server can place queue a check of this column.
   *
   * @param live Whether realtime is connected. Live columns take new items
   *             straight away; otherwise they wait behind the "new" pill.
   */
  handleRealtime(event: DeckRealtimeEvent, live: boolean): void {
    const result = this.source.onRealtime ? this.source.onRealtime(event) : DEFAULT_TRIGGERS.includes(event.name) ? 'check' : undefined;

    if (result === 'inserted') this.insertedAtTop = true;
    if (result === 'check') this.queueCheck(live);
  }

  /** However many events arrive, a column asks the server at most every MIN_CHECK_INTERVAL. */
  queueCheck(live: boolean): void {
    if (!this.visible) {
      this.stale = true;
      return;
    }

    if (this.checkTimer) return;

    const wait = Math.max(CHECK_DEBOUNCE, MIN_CHECK_INTERVAL - (Date.now() - this.lastChecked)) + Math.random() * JITTER;

    this.checkTimer = window.setTimeout(() => {
      this.checkTimer = null;

      if (document.hidden) {
        this.stale = true;
      } else {
        this.check(live);
      }
    }, wait);
  }

  cancelCheck(): void {
    if (this.checkTimer) clearTimeout(this.checkTimer);
    this.checkTimer = null;
  }

  check(live: boolean): Promise<void> {
    if (!this.visible) {
      this.stale = true;

      return Promise.resolve();
    }

    this.stale = false;
    this.lastChecked = Date.now();

    const apply = live && this.source.applyNew ? this.source.applyNew() : Promise.resolve(null);

    return (this.checking ??= apply
      .then((added) => {
        if (added === null) {
          return this.source.checkForNew().then((count) => {
            this.newCount = count;
          });
        }

        this.newCount = 0;
        if (added > 0) this.insertedAtTop = true;

        return undefined;
      })
      .catch(() => {})
      .finally(() => {
        this.checking = null;
        m.redraw();
      }));
  }

  consumeTopInsert(): boolean {
    const inserted = this.insertedAtTop;
    this.insertedAtTop = false;

    return inserted;
  }

  showNew(): Promise<unknown> {
    this.newCount = 0;

    return this.source.showNew().finally(() => m.redraw());
  }
}
