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
  /** Whether the queued check has to be a full one, not only `recheck()`. */
  protected fullCheckDue = false;
  /**
   * Checks and rechecks ask the server one at a time, so an answer that
   * arrives late can't undo a newer one: a discussion locked, then unlocked.
   */
  protected requests: Promise<void> = Promise.resolve();
  /** A check was asked for while one was running; it runs once that's done. */
  protected checkAgain = false;
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
    if (result === 'recheck') this.queueCheck(live, !!this.source.recheck);
  }

  /**
   * However many events arrive, a column asks the server at most every MIN_CHECK_INTERVAL.
   *
   * @param recheckOnly Only `recheck()` is needed, unless something else in
   *                    the same burst asks for a full check.
   */
  queueCheck(live: boolean, recheckOnly: boolean = false): void {
    if (!recheckOnly) this.fullCheckDue = true;

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
      } else if (this.fullCheckDue) {
        this.check(live);
      } else {
        this.recheck();
      }
    }, wait);
  }

  cancelCheck(): void {
    if (this.checkTimer) clearTimeout(this.checkTimer);
    this.checkTimer = null;
  }

  /** See DeckColumnSource.prune(). */
  prune(): void {
    this.source.prune?.();
  }

  check(live: boolean): Promise<void> {
    this.prune();

    if (!this.visible) {
      this.stale = true;

      return Promise.resolve();
    }

    this.fullCheckDue = false;

    // Whatever arrived since the running check began would be missed by it.
    if (this.checking) {
      this.checkAgain = true;

      return this.checking;
    }

    this.stale = false;
    this.lastChecked = Date.now();

    // Activity first: a discussion that joins by recheck moves the column's
    // activity key on, so asking afterwards would skip older replies.
    return (this.checking = this.serially(() =>
      (live && this.source.applyNew ? this.source.applyNew() : Promise.resolve(null))
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
        // A failed activity check still leaves the recheck worth doing, and the
        // reverse: a failed recheck keeps its items for next time.
        .catch(() => {})
        .then(() => this.runRecheck())
    )
      .catch(() => {})
      .finally(() => {
        this.checking = null;
        m.redraw();

        if (this.checkAgain) {
          this.checkAgain = false;
          this.queueCheck(live);
        }
      }));
  }

  /** Only asks again about the items the source noted; see DeckColumnSource.recheck(). */
  protected recheck(): Promise<void> {
    this.lastChecked = Date.now();

    return this.serially(() => this.runRecheck())
      .catch(() => {})
      .finally(() => m.redraw());
  }

  protected runRecheck(): Promise<void> {
    if (!this.source.recheck) return Promise.resolve();

    return this.source.recheck().then((atTop) => {
      if (atTop) this.insertedAtTop = true;
    });
  }

  protected serially(request: () => Promise<void>): Promise<void> {
    const run = this.requests.then(request);

    this.requests = run.catch(() => {});

    return run;
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
