import app from 'flarum/admin/app';
import apiUrl from '../apiUrl';
import { alertError } from '../errors';
import { pollOutcome, runIsOver, shouldPoll } from '../runState';
import type { RollbackPayload, Run, StatePayload, StepPayload } from '../types';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import type RequestError from 'flarum/common/utils/RequestError';
import type Mithril from 'mithril';
import { confirmAction } from './ConfirmModal';

const PHASES = ['plan', 'fetch', 'apply', 'finalise'];

/** The runner's own step names ("register", "code cache"), which have a label for people. */
const STEPS = [
  'check_the_site',
  'work_out_what_changes',
  'check_file_permissions',
  'register',
  'migrations',
  'assets',
  'caches',
  'code_cache',
  'check_the_site_again',
  'tidy_the_trash',
];

/** A step's name for people. A package name is shown as it is. */
function stepLabel(item: string): string {
  const step = String(item).replace(/ /g, '_');

  return STEPS.includes(step) ? app.translator.trans('flarum-extension-manager.admin.run.steps.' + step, {}, true) : item;
}

/** "finalise → register" from the runner, as "Finishing up → Updating Composer's records". */
function whereLabel(where: string | null): string {
  if (!where) return '';
  const [raw, item] = String(where).split(' → ');
  const phase = raw.replace(/ \(planning\)$/, '');
  const named = PHASES.includes(phase) ? app.translator.trans('flarum-extension-manager.admin.run.phases.' + phase, {}, true) : phase;

  return item ? named + ' → ' + stepLabel(item) : named;
}

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
  private misses = 0;
  private polling = false;
  private rollingBack = false;
  /** True once we have fallen back to reading progress instead of driving it. */
  private watching = false;
  /** The HTTP status of the last failed drive, so the panel can be specific. */
  private lastStatus: number | null = null;
  /** What the last failure means and what to do next — see runState.pollOutcome. */
  private outcome = pollOutcome(null, 0);

  /** The run the loop is following, so a new one starts with a clean slate. */
  private following: string | null = null;

  oncreate(vnode: Mithril.VnodeDOM<RunPanelAttrs, this>) {
    super.oncreate(vnode);
    this.follow();
  }

  /*
   * Checked after every redraw, not only on creation. A finished run keeps
   * this panel mounted, so the next Update arrives as a new `run` attr on the
   * SAME component — and when polling only began in oncreate, nothing polled
   * that run at all. See runState.shouldPoll.
   */
  onupdate(vnode: Mithril.VnodeDOM<RunPanelAttrs, this>) {
    super.onupdate(vnode);
    this.follow();
  }

  private follow() {
    const run = this.attrs.run;

    if (run?.id && run.id !== this.following) {
      // A different run: the last one's failures say nothing about this one.
      this.following = run.id;
      this.misses = 0;
      this.watching = false;
      this.lastStatus = null;
      this.outcome = pollOutcome(null, 0);
    }

    if (!this.rollingBack && shouldPoll(run, this.polling)) {
      this.poll();
    }
  }

  onremove() {
    // Stops the loop rescheduling itself once the page is gone.
    this.polling = false;
  }

  /** Which extensions this run is for: the names, or the first few and a count. */
  target(packages: string[]): string {
    if (packages.length <= 3) return packages.join(', ');

    return app.translator.trans(
      'flarum-extension-manager.admin.run.target.more',
      { shown: packages.slice(0, 2).join(', '), count: packages.length - 2 },
      true
    );
  }

  view() {
    const run = this.attrs.run;
    if (!run) return null;

    const failed = run.state === 'failed';
    const done = run.state === 'done';
    const rolled = run.state === 'rolled-back';
    const mode = ['install', 'remove'].includes(run.mode) ? run.mode : 'update';
    const target = this.target(run.packages || []);

    return (
      <div className={'ExtensionManager-run' + (failed ? ' ExtensionManager-run--failed' : '')}>
        <div className="ExtensionManager-runHead">
          <div>
            <h3>
              {done
                ? app.translator.trans('flarum-extension-manager.admin.run.done')
                : rolled
                  ? app.translator.trans('flarum-extension-manager.admin.run.rolled_back')
                  : failed
                    ? app.translator.trans('flarum-extension-manager.admin.run.failed')
                    : target
                      ? app.translator.trans('flarum-extension-manager.admin.run.target.' + mode, { packages: target })
                      : app.translator.trans('flarum-extension-manager.admin.run.working')}
            </h3>
            {target && (done || failed || rolled) ? <div className="ExtensionManager-runTarget">{target}</div> : null}
          </div>
          {this.attrs.driver ? <span className="ExtensionManager-driver">{this.attrs.driver}</span> : null}
        </div>

        {!done && !failed && !rolled ? this.phases(run) : null}
        {!done && !failed && !rolled ? this.bar(run) : null}

        {failed ? (
          <div className="ExtensionManager-error">
            <div className="ExtensionManager-errorWhere">
              {app.translator.trans('flarum-extension-manager.admin.run.failed_at', { step: whereLabel(run.errorStep) })}
            </div>
            <pre className="ExtensionManager-errorText">{run.error}</pre>
            {/*
             * Offered on a FAILURE, not just on a finished run. The journal
             * records each move before it is made, so a half-finished apply is
             * exactly as reversible as a complete one — and this is the moment
             * somebody most needs to know that.
             */}
            <Button className="Button" disabled={this.rollingBack} onclick={() => this.rollback()}>
              {this.rollingBack
                ? app.translator.trans('flarum-extension-manager.admin.run.rolling_back')
                : app.translator.trans('flarum-extension-manager.admin.run.roll_back')}
            </Button>
          </div>
        ) : null}

        {/*
         * A rolled-back run shows its reason too, and this used to be the
         * one state that did not.
         *
         * When the admin pressed the button they knew why. Now a run can undo
         * ITSELF — the site stopped answering and it put everything back — and
         * "Rolled back" on its own leaves them with a forum that works, an
         * update that vanished, and no idea which of the two is the problem.
         * The sentence names the extension and the line.
         *
         * No Roll back button here: the tree is already correct, and offering
         * to undo it again is the first thing a worried admin would press.
         */}
        {rolled && run.error ? (
          <div className="ExtensionManager-error">
            <div className="ExtensionManager-errorWhere">
              {app.translator.trans('flarum-extension-manager.admin.run.failed_at', { step: whereLabel(run.errorStep) })}
            </div>
            <pre className="ExtensionManager-errorText">{run.error}</pre>
          </div>
        ) : null}

        {this.attrs.rollbackNote ? <div className="ExtensionManager-next">{this.attrs.rollbackNote}</div> : null}

        {this.misses > 2 && !done && !failed && !rolled ? (
          <div className="ExtensionManager-stall">
            {/*
             * Says WHICH problem. A 400 or 401 here is an expired session —
             * the update is fine and a reload fixes the screen — and telling
             * somebody "nothing has moved" instead sends them looking at the
             * update.
             */}
            {this.outcome.message === 'unauthorised'
              ? app.translator.trans('flarum-extension-manager.admin.run.poll_unauthorised')
              : app.translator.trans('flarum-extension-manager.admin.run.poll_failing', { count: this.misses })}
            {this.watching ? ' ' + app.translator.trans('flarum-extension-manager.admin.run.watching_only', {}, true) : ''}
          </div>
        ) : null}

        {this.attrs.busy && !done && !failed && !rolled ? (
          <div className="ExtensionManager-stall">{app.translator.trans('flarum-extension-manager.admin.run.another_driver')}</div>
        ) : null}

        {/*
         * Every one of these is silenced once the run has ended. "Nothing
         * has moved for a couple of minutes" is true of a failed run and
         * useless — it points somebody at a stall when the panel above already
         * says exactly what went wrong.
         */}
        {this.attrs.stale && !done && !failed && !rolled ? (
          <div className="ExtensionManager-stall">{app.translator.trans('flarum-extension-manager.admin.run.nothing_moved')}</div>
        ) : null}

        {/*
         * A FINISHED update can be undone too, and the screen used to
         * offer that only on a failure. The endpoint, the journal and the
         * trash all exist for exactly this — "it worked, and I wish it had
         * not" is the rollback people actually need — so a button that only
         * appears when something broke left the feature unreachable.
         *
         * But on a finished run it is the last resort, not the next step:
         * Dismiss leads, and Undo is a quiet link after it. "Put everything
         * back" sat first, as a full button, and read to a tester like a
         * cleanup of leftover files (ClaudiusH, 2026-10-07).
         */}
        {done || rolled || failed ? (
          <div className="ExtensionManager-runActions">
            <Button className="Button" onclick={() => this.attrs.ondismiss()}>
              {app.translator.trans('flarum-extension-manager.admin.run.dismiss')}
            </Button>
            {/* The page still holds each changed extension's old admin code and
                version until it reloads (ClaudiusH). */}
            {done || rolled ? (
              <Button
                className="Button Button--link ExtensionManager-undo"
                title={app.translator.trans('flarum-extension-manager.admin.run.reload_page_why', {}, true)}
                onclick={() => window.location.reload()}
              >
                {app.translator.trans('flarum-extension-manager.admin.run.reload_page')}
              </Button>
            ) : null}
            {done ? (
              <Button className="Button Button--link ExtensionManager-undo" disabled={this.rollingBack} onclick={() => this.confirmRollback(run)}>
                {this.rollingBack
                  ? app.translator.trans('flarum-extension-manager.admin.run.rolling_back')
                  : app.translator.trans('flarum-extension-manager.admin.run.roll_back')}
              </Button>
            ) : null}
          </div>
        ) : null}

        {/*
         * The step-by-step log, for the curious: the phases and the bar above
         * already say what is happening (ClaudiusH, 2026-10-09). Open on a
         * failure, where it is the evidence.
         */}
        <details className="ExtensionManager-details" open={failed || rolled}>
          <summary>{app.translator.trans('flarum-extension-manager.admin.run.details')}</summary>
          <ol className="ExtensionManager-log">
            {(run.log || []).map((line, i) => (
              <li key={i}>{line}</li>
            ))}
          </ol>
        </details>
      </div>
    );
  }

  /**
   * A removal only takes things away (it resolves with
   * `--no-update-with-dependencies`), so it has no Downloading step. Shown and
   * then skipped, it read as something that had gone wrong (ClaudiusH,
   * 2026-10-13).
   */
  shownPhases(run: Run): string[] {
    return run.mode === 'remove' ? PHASES.filter((p) => p !== 'fetch') : PHASES;
  }

  phases(run: Run) {
    const shown = this.shownPhases(run);
    // Where the run is, in the list as shown; a hidden phase counts as passed.
    const at = shown.indexOf(run.phase) === -1 ? shown.indexOf('apply') : shown.indexOf(run.phase);

    // How far through the current phase, which fills its own segment.
    const total = (run.items || []).length;
    const fill = total ? Math.round((Math.min(run.index, total) / total) * 100) : 0;

    /*
     * One bar in sections, not a row of pills. Pills read as tags or tabs
     * (ClaudiusH); a tracker whose sections complete says where the run is and
     * how much is left. The current section fills as its steps finish, which
     * replaces the separate thin bar that used to sit under it.
     */
    return (
      <ol className="ExtensionManager-phases" aria-label={app.translator.trans('flarum-extension-manager.admin.run.phases_label', {}, true)}>
        {shown.map((p, i) => {
          const state = i < at ? 'done' : i === at ? 'now' : 'next';

          return (
            <li
              key={p}
              className={'ExtensionManager-phase is-' + state}
              aria-current={state === 'now' ? 'step' : undefined}
              style={state === 'now' ? { '--mw-fill': fill + '%' } : undefined}
            >
              <span className="ExtensionManager-phaseMark" aria-hidden="true">
                {state === 'done' ? <i className="fas fa-check" /> : state === 'now' ? <i className="fas fa-circle-notch fa-spin" /> : i + 1}
              </span>
              <span className="ExtensionManager-phaseLabel">{app.translator.trans('flarum-extension-manager.admin.run.phases.' + p)}</span>
            </li>
          );
        })}
      </ol>
    );
  }

  bar(run: Run) {
    const total = (run.items || []).length;
    const item = total ? stepLabel(run.items[Math.min(run.index, total - 1)]) : null;

    return (
      <div className="ExtensionManager-progress">
        {/*
         * The item's own name, not "step 3 of 7". A package name tells you
         * what is being touched right now; a number tells you nothing you can
         * act on if it stops.
         */}
        <div className="ExtensionManager-progressText">
          {/*
           * "Asking Composer what this involves" is the PLAN phase's
           * sentence. Every phase starts with no items for a moment, and it
           * used to say that during the download, the swap and the finish too.
           */}
          {total
            ? app.translator.trans('flarum-extension-manager.admin.run.working_on', { item, index: Math.min(run.index + 1, total), total })
            : run.phase === 'plan'
              ? app.translator.trans('flarum-extension-manager.admin.run.working_out')
              : null}
        </div>
      </div>
    );
  }

  poll() {
    if (this.polling) return;
    this.polling = true;
    this.tick();
  }

  tick() {
    if (!this.polling) return;

    app
      .request<StepPayload>({ method: 'POST', url: apiUrl() + '/extension-manager/step' })
      .then((data) => {
        this.misses = 0;
        this.attrs.onprogress(data);

        if (data.idle) {
          this.polling = false;
          this.attrs.ondone(data.run);
          return;
        }

        m.redraw();
        setTimeout(() => this.tick(), 1500);
      })
      .catch((e: RequestError) => {
        /*
         * A failed poll is never a failed update, and this is the difference
         * between the two designs. The run's state is on disk; a 502 from a
         * host that cut the request means this one call did not land, and the
         * next one picks up where the last left off. Backing off keeps a
         * struggling host from being hammered while it recovers.
         */
        this.misses++;
        this.lastStatus = e?.status ?? null;
        this.outcome = pollOutcome(this.lastStatus, this.misses);

        /*
         * Driving the run and WATCHING it are two different jobs, and only
         * one of them is this panel's reason to exist.
         *
         * /extension-manager/step is a POST: it takes the lock and does an item. When
         * it fails — an expired session, a proxy that strips the write, a host
         * that 502s under a Composer install — the panel used to show nothing
         * at all, frozen on whatever it had when it mounted, while the queue
         * worker carried the update to completion behind it. Ernest watched
         * exactly that happen twice: "it stayed on the first step the whole
         * time", on a run that had in fact finished.
         *
         * /extension-manager/state is a GET and is documented as never advancing
         * anything. So when we cannot drive, we watch. The run still moves —
         * the worker is doing it — and the screen finally says so.
         */
        this.watch();
      });
  }

  /**
   * Read-only progress, for when we cannot drive.
   *
   * Keeps polling on the same backoff so a transient failure recovers into
   * driving again by itself.
   */
  private watch() {
    app
      .request<StatePayload>({ method: 'GET', url: apiUrl() + '/extension-manager/state' })
      .then((data) => {
        this.watching = true;

        if (data.run) {
          this.attrs.onprogress({ run: data.run, busy: true, stale: data.runIsStale });

          if (runIsOver(data.run)) {
            this.polling = false;
            this.attrs.ondone(data.run);
            m.redraw();

            return;
          }
        }

        m.redraw();
        setTimeout(() => this.tick(), this.outcome.delayMs);
      })
      .catch(() => {
        // Both endpoints unreachable. Now it really is a connectivity problem.
        m.redraw();
        setTimeout(() => this.tick(), this.outcome.delayMs);
      });
  }

  /** Undoing a run that worked is asked for, and says whether the database goes back too. */
  confirmRollback(run: Run) {
    const count = run.migrations?.length || 0;

    confirmAction({
      title: app.translator.trans('flarum-extension-manager.admin.run.roll_back'),
      message: count
        ? app.translator.trans('flarum-extension-manager.admin.run.roll_back_confirm_db', { count })
        : app.translator.trans('flarum-extension-manager.admin.run.roll_back_confirm'),
      confirmText: app.translator.trans('flarum-extension-manager.admin.run.roll_back'),
      danger: true,
      onconfirm: () => this.rollback(),
    });
  }

  rollback() {
    this.rollingBack = true;
    this.polling = false;
    m.redraw();

    app
      .request<RollbackPayload>({
        method: 'POST',
        url: apiUrl() + '/extension-manager/rollback',
        errorHandler: alertError('flarum-extension-manager.admin.run.rollback_failed'),
      })
      .then((data) => {
        this.rollingBack = false;
        this.attrs.onrollback(data);
        m.redraw();
      })
      .catch(() => {
        this.rollingBack = false;
        m.redraw();
      });
  }
}
