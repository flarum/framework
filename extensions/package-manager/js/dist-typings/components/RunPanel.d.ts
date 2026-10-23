import type { RollbackPayload, Run, StepPayload } from '../types';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
export interface RunPanelAttrs extends ComponentAttrs {
    run: Run | null;
    driver: string | null;
    busy: boolean;
    stale: boolean;
    rollbackNote: string | null;
    onprogress: (data: StepPayload) => void;
    ondone: (run: Run | null) => void;
    onrollback: (data: RollbackPayload) => void;
    ondismiss: () => void;
}
/**
 * What is happening, while it happens.
 *
 * This panel is the product. An update that works but shows a spinner is the
 * thing being replaced — the complaint that started all of this was not "it
 * failed", it was "the status has been showing running for a while and I don't
 * know what is going on". So every piece of state the server knows is on screen:
 * which phase, which item of how many, every line of the log, how long since
 * anything moved, and which driver is turning the handle.
 *
 * It also never advertises progress it has not been told about. When a poll
 * comes back saying another driver holds the run, that is shown as exactly that
 * rather than as a stalled bar.
 */
export default class RunPanel extends Component<RunPanelAttrs> {
    /** Consecutive failed polls. A host hiccup is not a failed update. */
    private misses;
    private polling;
    private rollingBack;
    /** True once we have fallen back to reading progress instead of driving it. */
    private watching;
    /** The HTTP status of the last failed drive, so the panel can be specific. */
    private lastStatus;
    /** What the last failure means and what to do next — see runState.pollOutcome. */
    private outcome;
    /** The run the loop is following, so a new one starts with a clean slate. */
    private following;
    oncreate(vnode: Mithril.VnodeDOM<RunPanelAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<RunPanelAttrs, this>): void;
    private follow;
    onremove(): void;
    /** Which extensions this run is for: the names, or the first few and a count. */
    target(packages: string[]): string;
    view(): JSX.Element | null;
    /**
     * A removal only takes things away (it resolves with
     * `--no-update-with-dependencies`), so it has no Downloading step. Shown and
     * then skipped, it read as something that had gone wrong (ClaudiusH,
     * 2026-10-13).
     */
    shownPhases(run: Run): string[];
    phases(run: Run): JSX.Element;
    bar(run: Run): JSX.Element;
    poll(): void;
    tick(): void;
    /**
     * Read-only progress, for when we cannot drive.
     *
     * Keeps polling on the same backoff so a transient failure recovers into
     * driving again by itself.
     */
    private watch;
    /** Undoing a run that worked is asked for, and says whether the database goes back too. */
    confirmRollback(run: Run): void;
    rollback(): void;
}
