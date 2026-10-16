/**
 * The decisions the run screen makes, as functions with no DOM and no network.
 *
 * 🚨 Every one of these was a bug in production, and none of them was testable
 * where it lived.
 *
 * They were expressions buried inside a Mithril component — `!!this.run &&
 * !this.dismissed` deciding whether to replace the entire page, a `catch` block
 * deciding whether a failed poll meant "stalled" or "keep watching", a ternary
 * deciding whether a card shows the button that starts an update. A decision
 * that can only be reached by rendering a component and driving a browser is a
 * decision nobody tests, and all four shipped broken.
 *
 * Pulled out here they are ordinary functions. The components stay thin enough
 * that what is left in them is markup.
 */

export type RunState = 'pending' | 'running' | 'done' | 'failed' | 'rolled-back';

export interface RunLike {
  id?: string;
  state?: RunState | string;
  phase?: string;
  items?: unknown[];
  index?: number;
}

/** States in which work is still being done. */
const LIVE: string[] = ['pending', 'running'];

/** States in which the run is over, however it ended. */
const OVER: string[] = ['done', 'failed', 'rolled-back'];

export function runIsLive(run: RunLike | null | undefined): boolean {
  return !!run && LIVE.includes(String(run.state));
}

export function runIsOver(run: RunLike | null | undefined): boolean {
  return !!run && OVER.includes(String(run.state));
}

/**
 * Whether the run panel is on screen at all.
 *
 * A finished run keeps its panel until dismissed — the log is most useful the
 * moment the run ends.
 */
export function showingRun(run: RunLike | null | undefined, dismissed: boolean): boolean {
  return !!run && !dismissed;
}

/**
 * Whether the panel takes the WHOLE page, hiding the tabs and the extension
 * grid.
 *
 * 🚨 Only while a run is live. Hiding the grid during an update is deliberate —
 * a row of Update buttons beside a running update invites a second one, and the
 * honest answer to the second press is a refusal.
 *
 * Hiding it because a run finished is not the same thing, and that is the bug
 * this function exists to have a name for: `showingRun()` was used for both, so
 * one completed update left the page showing nothing but a log, on every load,
 * with no way to reach the Update buttons at all.
 */
export function hidesPage(run: RunLike | null | undefined, dismissed: boolean): boolean {
  return showingRun(run, dismissed) && runIsLive(run);
}

/**
 * Whether a remembered dismissal applies to the run now on screen.
 *
 * 🚨 Keyed to the run's id, so a NEW run is never silently pre-dismissed, and
 * never restored for a live one — dismissing must not become a way to hide an
 * update that is halfway through applying itself.
 */
export function dismissalApplies(
  run: RunLike | null | undefined,
  storedId: string | null
): boolean {
  if (!run || !run.id || !storedId) return false;
  if (runIsLive(run)) return false;

  return run.id === storedId;
}

/**
 * Whether the run panel should start its polling loop now.
 *
 * 🚨 Asked on EVERY redraw, not only when the panel is created — and that
 * difference is the frozen progress panel.
 *
 * A finished run keeps its panel on screen, and the loop stops for good once it
 * sees that run is over. Pressing Update then hands the NEW run to the SAME
 * component: Mithril updates it in place, `oncreate` never fires again, and
 * nothing ever polled. The screen sat on "Working out what changes" with one
 * line of log while the worker finished the whole update behind it — the
 * second and every later update on a forum, never the first, which is why it
 * "used to work".
 */
export function shouldPoll(run: RunLike | null | undefined, polling: boolean): boolean {
  return !polling && runIsLive(run);
}

export type PollAction = 'drive' | 'watch' | 'stop';

export interface PollOutcome {
  action: PollAction;
  /** Milliseconds until the next attempt. */
  delayMs: number;
  /** Which explanation to show, if any. */
  message: 'none' | 'unauthorised' | 'failing';
}

const BASE_DELAY = 1500;
const MAX_DELAY = 15000;

/**
 * What to do after an attempt to drive the run.
 *
 * 🚨 A failed poll is never a failed update. The run's state is on disk and
 * another driver may well be advancing it; this call simply did not land.
 *
 * 🚨 And a failure to DRIVE is not a reason to stop WATCHING. `/extension-manager/step`
 * is a POST that takes the lock; `/extension-manager/state` is a GET that changes
 * nothing. When the first fails the second still works, and the panel's whole
 * job is to show progress. Falling back is what stops a completed update
 * looking frozen at step one.
 */
export function pollOutcome(status: number | null, misses: number): PollOutcome {
  if (misses === 0) {
    return { action: 'drive', delayMs: BASE_DELAY, message: 'none' };
  }

  return {
    action: 'watch',
    delayMs: Math.min(BASE_DELAY * misses, MAX_DELAY),
    /*
     * A 4xx here is an expired session far more often than anything else: the
     * update is fine and a reload fixes the screen. Saying "nothing has moved"
     * instead sends somebody to look at the update, which is the one place the
     * problem is not.
     */
    message: status !== null && status >= 400 && status < 500 ? 'unauthorised' : 'failing',
  };
}

export interface CardLike {
  update?: { from: string; to: string } | null;
  enabled?: boolean | null;
  pathInstall?: boolean;
  /** What composer.json requires, e.g. "3.5.1", "^3.5", "dev-main". */
  constraint?: string | null;
  /** A Composer library the forum requires itself: no settings, nothing to enable. */
  library?: boolean;
}

/**
 * Whether a requirement admits exactly one version.
 *
 * 🚨 This is the difference between "press Update and it updates" and "press
 * Update and nothing happens, forever". A forum that requires `3.5.1` cannot be
 * moved to 3.6.0 by any resolve — the constraint forbids it — so the card must
 * offer to raise the requirement rather than pretending a resolve will do it.
 *
 * A range is left alone: `^3.5` genuinely can be already-newest, and rewriting
 * it would change a decision nobody asked to change.
 */
export function isExactPin(constraint: string | null | undefined): boolean {
  if (!constraint) return false;
  if (/[\^~*|]|\s-\s/.test(constraint)) return false;

  return /^v?\d+\.\d+\.\d+/.test(constraint);
}

/**
 * What a card in the extension grid offers.
 *
 * 🚨 `pathInstall` beats everything. Composer installs a path repository as a
 * symlink into a checkout on this machine — how every extension developer runs
 * their own work — and the Extension Manager will not replace one. Offering the button and
 * refusing afterwards is a worse way to learn that than not being offered it.
 */
export function cardOffers(card: CardLike) {
  const hasUpdate = !!card.update;

  const canAct = hasUpdate && !card.pathInstall;

  return {
    /** The card's border: a newer version exists, whether or not it can be acted on. */
    highlight: hasUpdate,
    update: canAct,
    /*
     * 🚨 Pressing Update on a pinned package must say what it will really do.
     * The requirement changes first; the resolve follows. Anything less makes
     * the button a no-op that reports success.
     */
    repin: canAct && isExactPin(card.constraint),
    /*
     * Not for a library: nothing says whether the forum's config relies on it
     * (fof/redis is wired in config.php), so removing one could take the site down.
     *
     * Not beside an update either: Update and Remove together wrapped the
     * card's foot onto a second line, and with a newer version waiting the
     * update is the action that matters. Remove comes back once it is current
     * (ClaudiusH, 2026-10-10).
     */
    remove: !card.library && !card.enabled && !card.pathInstall && !hasUpdate,
  };
}

/** Anything with a newer version first — that is what somebody came to see. */
export function sortForGrid<T extends CardLike>(cards: T[]): T[] {
  return [...cards].sort((a, b) => Number(!!b.update) - Number(!!a.update));
}
