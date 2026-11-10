import type { Installed, HistoryEntry, HostReport, RollbackPayload, Run, RunMode, Updates } from '../types';
import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import ItemList from 'flarum/common/utils/ItemList';
import type { TabItem } from 'flarum/common/components/Tabs';
import type Mithril from 'mithril';
type CheckAge = 'fresh' | 'recent' | 'overdue';
export interface ExtensionManagerPageAttrs extends ExtensionPageAttrs {
    /** The dashboard's update banner links here with ?tab=installed. */
    tab?: string;
}
export default class ExtensionManagerPage extends ExtensionPage<ExtensionManagerPageAttrs> {
    firstLoad: boolean;
    /** Set when the first load fails or never arrives, so the page can say so. */
    loadError: string | null;
    /** The exception's own text, shown under the translated message when there is one. */
    loadErrorDetail: string | null;
    /** The 10-second safety net for the first load, cleared when the page goes. */
    protected loadTimeout: ReturnType<typeof setTimeout> | null;
    host: HostReport | null;
    installed: Installed[];
    updates: Updates;
    checking: boolean;
    history: HistoryEntry[];
    run: Run | null;
    driver: string | null;
    busy: boolean;
    stale: boolean;
    starting: boolean;
    rollbackNote: string | null;
    dismissed: boolean;
    /** An undo started from the History tab, while the run panel is dismissed. */
    rollingBack: boolean;
    /** Only the newest `/state` reply is used; see runState.requestSequence. */
    protected loads: {
        next: () => number;
        isCurrent: (ticket: number) => boolean;
        invalidate: () => void;
    };
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
    protected static DISMISS_KEY: string;
    protected storedDismissal(): string | null;
    protected rememberDismissed(id: string | null): void;
    protected forgetDismissed(): void;
    oninit(vnode: Mithril.Vnode<ExtensionManagerPageAttrs, this>): void;
    /** The first load, and each "Try again": guarded by the timeout, and never allowed to throw. */
    attemptLoad(): void;
    /**
     * Gives the first load ten seconds before saying it never arrived. Armed
     * again by "Try again", so a retry that hangs says so too instead of
     * spinning forever.
     */
    armLoadTimeout(): void;
    /** A timer that outlives its page fires into whatever the admin opened next. */
    onremove(vnode: Mithril.VnodeDOM<ExtensionManagerPageAttrs, this>): void;
    load(): void;
    /**
     * The page around the tabs: the first load, a load that failed, and the run
     * panel. While a run is live the panel is the ONLY thing on screen and the
     * tab bar goes away. A grid of Update buttons beside a running update
     * invites somebody to start a second one, and the honest answer to the
     * second press is a refusal — better not to offer it.
     */
    pageTabs(vnode: Mithril.VnodeDOM<ExtensionManagerPageAttrs, this>, tabs: TabItem[]): Mithril.Children;
    /**
     * The page's tabs, with the number of updates waiting on Installed.
     *
     * Finding extensions is what most people open this page for (ClaudiusH,
     * 2026-10-09), so Discover comes first and is shown by default. The
     * dashboard's update banner links here with ?tab=installed.
     */
    pageTabItems(): ItemList<TabItem>;
    pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionManagerPageAttrs, this>): Mithril.Children;
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
    protected hidesPage(): boolean;
    /**
     * The Extension Manager updating ITSELF leaves this page running the old version's
     * code: the header still said v1.15.0 under a finished v1.16.1 update
     * (ClaudiusH, 2026-10-07). So the page reloads, once per run and outcome.
     * The finished panel comes straight back, because the run is on disk.
     */
    protected reloadIfSelf(run: Run | null): void;
    runPanel(): JSX.Element;
    rolledBack(data: RollbackPayload): void;
    /**
     * The run History may offer to undo: the latest one, when it finished or
     * failed and its panel has been dismissed.
     *
     * Dismissing a failed run is remembered, and the panel held the only Roll
     * back button, so a dismissed failure could no longer be undone from this
     * page at all. Only the latest run, because that is the one the rollback
     * endpoint undoes, and only while the panel is away, so there is never a
     * second button for the same undo on screen.
     */
    undoableFromHistory(): string | null;
    rollBackFromHistory(): void;
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
    /** The check line's first sentence: what the last check found. */
    checkSummary(n: number): string;
    /**
     * How hard to ask, by how old the last check is (ClaudiusH, 2026-10-09):
     * within the week, an ordinary button; older than that, or never, a primary
     * button and a prompt that says why it matters. Always a button, never a
     * link: it is the main action on the Installed tab (ClaudiusH, 2026-10-27).
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
