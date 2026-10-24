import type { Installed, HistoryEntry, HostReport, Run, RunMode, Updates } from '../types';
import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type Mithril from 'mithril';
type Tab = 'installed' | 'history' | 'discover' | 'sources' | 'host';
type CheckAge = 'fresh' | 'recent' | 'overdue';
export interface ExtensionManagerPageAttrs extends ExtensionPageAttrs {
    /** The dashboard's update banner links here with ?tab=installed. */
    tab?: string;
}
export default class ExtensionManagerPage extends ExtensionPage<ExtensionManagerPageAttrs> {
    firstLoad: boolean;
    /** Set when the first load fails or never arrives, so the page can say so. */
    loadError: string | null;
    /** The 10-second safety net for the first load, cleared when the page goes. */
    private loadTimeout;
    host: HostReport | null;
    installed: Installed[];
    updates: Updates;
    checking: boolean;
    tab: Tab;
    history: HistoryEntry[];
    run: Run | null;
    driver: string | null;
    busy: boolean;
    stale: boolean;
    starting: boolean;
    rollbackNote: string | null;
    dismissed: boolean;
    /**
     * Dismissal is remembered against WHICH run, and it has to be remembered
     * at all.
     *
     * `dismissed` is component state, so it was gone on the next page load and a
     * finished run's panel came back every single time. Combined with the panel
     * hiding the tabs, that meant one update run ever — even a successful one —
     * left the page showing nothing but a week-old log, with no extension grid,
     * no Update buttons and no way to reach them. Keyed by id so a NEW run is
     * never silently pre-dismissed.
     */
    private static DISMISS_KEY;
    private storedDismissal;
    private rememberDismissed;
    oninit(vnode: Mithril.Vnode<ExtensionManagerPageAttrs, this>): void;
    /** A timer that outlives its page fires into whatever the admin opened next. */
    onremove(vnode: Mithril.VnodeDOM<ExtensionManagerPageAttrs, this>): void;
    load(): void;
    content(): JSX.Element;
    /**
     * A FINISHED run keeps its panel until somebody dismisses it. The screen
     * returning to normal on its own would throw away the log at the exact moment
     * it becomes useful — what changed, in what order, and what to check.
     */
    showingRun(): boolean;
    /**
     * Whether a run is still going.
     *
     * Only a LIVE run may take the page over. Hiding the grid while an update
     * is applying is right — a row of Update buttons beside a running update
     * invites a second one, and the honest answer to that is a refusal. Hiding it
     * because a run finished yesterday is not: the log is worth keeping on screen,
     * the rest of the page is worth having as well.
     */
    runIsLive(): boolean;
    /** The page's own content is suppressed only while something is happening. */
    private hidesPage;
    /**
     * The Extension Manager updating ITSELF leaves this page running the old version's
     * code: the header still said v1.15.0 under a finished v1.16.1 update
     * (ClaudiusH, 2026-10-07). So the page reloads, once per run and outcome.
     * The finished panel comes straight back, because the run is on disk.
     */
    private reloadIfSelf;
    runPanel(): JSX.Element;
    /**
     * Every package with a newer version, in one press.
     *
     * Resolved TOGETHER rather than as a queue of separate updates. Two
     * extensions can each have a newer version and still be uninstallable side by
     * side; asking Composer about all of them at once is the only way to find that
     * out before anything moves, rather than halfway through the second one.
     */
    updateAll(): JSX.Element | null;
    /**
     * Raising a pin edits composer.json, so it is asked for in those words.
     *
     * The new version is NOT sent to the server. The request carries a yes,
     * and the server takes the target from its own update check — so the only
     * version a requirement can move to is the one on the card the admin just
     * read. A version travelling from the browser into composer.json would be an
     * arbitrary string from a client.
     */
    confirmRepin(entry: Installed | Installed[], packages?: string[]): void;
    /**
     * Confirmed, because it is the one action here that takes something away.
     * Everything else can be undone by rolling back and is described as such; a
     * removal can too, but somebody should still mean it.
     */
    confirmRemove(e: Installed): void;
    /**
     * @param mode 'install' adds a package that is not here yet. It is not
     *        cosmetic: `composer update` on an uninstalled package does nothing
     *        and exits 0, so a run in the wrong mode would pass every phase,
     *        change nothing, and report success.
     */
    start(packages: string[], mode?: RunMode, repin?: boolean, nightly?: boolean): void;
    /**
     * The installed tab: whether anything is newer, then Flarum itself, then
     * anything to update, then the cards. The check comes first because it is
     * what people come to this tab for; the sidebar already lists what is
     * installed (ClaudiusH, 2026-10-09).
     *
     * Every entry is keyed AND the falsy ones are removed, because Mithril
     * requires that in a fragment either every vnode has a key or none does — and
     * a `null` counts as one without. `updateAll()` returns null when nothing has
     * a newer version, so this array was legal on a forum with updates waiting and
     * a TypeError on one that was up to date. It rendered nothing at all: the
     * component threw during view, which on a Mithril page means the spinner that
     * was already on screen simply stayed there.
     *
     * Which is to say the page was broken in its most ordinary state, and one
     * load of it in a browser would have shown that immediately.
     */
    installedTab(): (JSX.Element | null)[];
    /**
     * One count everywhere: the extensions "Update all" would update. The tab
     * badge and the line above counted every newer package, Flarum itself
     * included, so they read 17 beside "Update all 16" and stayed one ahead
     * after each update (ClaudiusH, 2026-10-09). Flarum has its own panel, and
     * the line names it separately.
     */
    updateCount(): number;
    coreHasUpdate(): boolean;
    /**
     * The count is always shown WITH its age and its blind spots. "1 newer
     * version" is a claim; "1 newer version, checked 2 hours ago, 9 packages could
     * not be checked" is the truth, and only the second lets somebody decide
     * whether to believe it.
     */
    checkLine(): JSX.Element;
    /**
     * How hard to ask, by how old the last check is (ClaudiusH, 2026-10-09):
     * checked today, a quiet link to check again; within the week, an ordinary
     * button; older than that, or never, a prompt that says why it matters.
     */
    checkAge(): CheckAge;
    checkButton(age: CheckAge): JSX.Element;
    checkNow(): void;
    ago(unix: number): string;
    /** Anything with a newer version first — that is what somebody came to see. */
    sorted(): Installed[];
    cardTop(e: Installed): JSX.Element[];
    grid(): JSX.Element;
}
export {};
