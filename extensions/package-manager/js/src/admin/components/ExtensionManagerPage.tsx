import app from 'flarum/admin/app';
import type RequestError from 'flarum/common/utils/RequestError';
import apiUrl from '../apiUrl';
import { alertError, errorPayload, serverMessage } from '../errors';
import { cardOffers, dismissalApplies, hidesPage, runIsLive, showingRun, sortForGrid } from '../runState';
import type {
  CheckPayload,
  Installed,
  HistoryEntry,
  HostReport,
  RollbackPayload,
  Run,
  RunMode,
  StartPayload,
  StatePayload,
  StepPayload,
  Updates,
} from '../types';
import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Link from 'flarum/common/components/Link';
import Button from 'flarum/common/components/Button';
import Alert from 'flarum/common/components/Alert';
import type Mithril from 'mithril';
import { confirmAction } from './ConfirmModal';
import HostPanel from './HostPanel';
import TrashPanel from './TrashPanel';
import RunPanel from './RunPanel';
import DiscoverTab from './DiscoverTab';
import CorePanel from './CorePanel';
import SourcesTab from './SourcesTab';
import HistoryTab from './HistoryTab';

/** This extension's own package: updating it means this page's code is out of date. */
const SELF_PACKAGE = 'flarum/extension-manager';

/** Flarum's id for an extension package, as core derives it (Extension::nameToId). */
function extensionId(name: string): string {
  const at = name.indexOf('/');
  const pkg = at < 0 ? name : name.slice(at + 1);
  return (at < 0 ? name : name.slice(0, at)) + '-' + pkg.replace(/flarum-ext-|flarum-/g, '');
}

type Tab = 'installed' | 'history' | 'discover' | 'sources' | 'host';
type CheckAge = 'fresh' | 'recent' | 'overdue';

export interface ExtensionManagerPageAttrs extends ExtensionPageAttrs {
  /** The dashboard's update banner links here with ?tab=installed. */
  tab?: string;
}

export default class ExtensionManagerPage extends ExtensionPage<ExtensionManagerPageAttrs> {
  /*
   * NOT `loading`. AdminPage — which this inherits from through
   * ExtensionPage — already declares `loading`, and uses it for the Save
   * button's spinner. Shadowing it means two unrelated pieces of state share
   * one flag, and whichever writes last wins.
   */
  firstLoad = true;

  /** Set when the first load fails or never arrives, so the page can say so. */
  loadError: string | null = null;
  /** The exception's own text, shown under the translated message when there is one. */
  loadErrorDetail: string | null = null;
  /** The 10-second safety net for the first load, cleared when the page goes. */
  protected loadTimeout: ReturnType<typeof setTimeout> | null = null;
  host: HostReport | null = null;
  installed: Installed[] = [];
  updates: Updates = { available: {}, checkedAt: null, stale: true, uncheckable: [], tracking: [] };
  checking = false;
  /*
   * Finding extensions is what most people open this page for (ClaudiusH,
   * 2026-10-09). The dashboard's update banner links here with ?tab=installed.
   */
  tab: Tab = 'discover';
  history: HistoryEntry[] = [];
  run: Run | null = null;
  driver: string | null = null;
  busy = false;
  stale = false;
  starting = false;
  rollbackNote: string | null = null;
  dismissed = false;

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
  protected static DISMISS_KEY = 'extension-manager.dismissedRun';

  protected storedDismissal(): string | null {
    try {
      return localStorage.getItem(ExtensionManagerPage.DISMISS_KEY);
    } catch {
      // Private browsing. The panel reappears, which is the safe way round.
      return null;
    }
  }

  protected rememberDismissed(id: string | null) {
    if (!id) return;

    try {
      localStorage.setItem(ExtensionManagerPage.DISMISS_KEY, id);
    } catch {
      // Private browsing: it reappears next load, which is the safe way round.
    }
  }

  oninit(vnode: Mithril.Vnode<ExtensionManagerPageAttrs, this>) {
    super.oninit(vnode);

    if (vnode.attrs.tab === 'installed') this.tab = 'installed';

    this.attemptLoad();
  }

  /** The first load, and each "Try again": guarded by the timeout, and never allowed to throw. */
  attemptLoad() {
    this.firstLoad = true;

    /*
     * The safety net is armed BEFORE the thing it protects against, and the
     * first version of it was not. It sat after `this.load()`, so a load that
     * threw SYNCHRONOUSLY — which is what a bad `app.forum.attribute()` does —
     * skipped past it, escaped oninit, and left the spinner on screen forever.
     * A guard that runs only when the guarded code succeeds is not a guard.
     */
    this.armLoadTimeout();

    /*
     * And the call itself cannot be allowed to escape. An exception inside a
     * Mithril lifecycle hook aborts the component's initialisation; nothing in
     * the UI changes, so the failure is invisible in exactly the place this
     * extension promises never to be.
     */
    try {
      this.load();
    } catch (e) {
      this.loadError = app.translator.trans('flarum-extension-manager.admin.page.load_failed', {}, true);
      this.loadErrorDetail = e instanceof Error && e.message ? e.message : null;
      this.firstLoad = false;
    }
  }

  /**
   * Gives the first load ten seconds before saying it never arrived. Armed
   * again by "Try again", so a retry that hangs says so too instead of
   * spinning forever.
   */
  armLoadTimeout() {
    if (this.loadTimeout !== null) clearTimeout(this.loadTimeout);

    this.loadTimeout = setTimeout(() => {
      this.loadTimeout = null;

      if (this.firstLoad) {
        this.loadError = app.translator.trans('flarum-extension-manager.admin.page.load_timeout', {}, true);
        this.firstLoad = false;
        m.redraw();
      }
    }, 10000);
  }

  /** A timer that outlives its page fires into whatever the admin opened next. */
  onremove(vnode: Mithril.VnodeDOM<ExtensionManagerPageAttrs, this>) {
    super.onremove(vnode);

    if (this.loadTimeout !== null) {
      clearTimeout(this.loadTimeout);
      this.loadTimeout = null;
    }
  }

  load() {
    this.loadError = null;
    this.loadErrorDetail = null;

    app
      .request<StatePayload>({ method: 'GET', url: apiUrl() + '/extension-manager/state' })
      .then((data) => {
        this.host = data.host;
        this.installed = data.installed || [];
        this.history = data.history || [];
        // The second half of an undo runs on this request; say if it could not.
        if (data.undoError) app.alerts.show({ type: 'error' }, data.undoError);
        this.updates = data.updates || this.updates;
        /*
         * An unfinished run found on load is picked straight back up. Closing
         * the tab is not abandoning the update — the state is on disk and any
         * driver can carry it on — so reopening the page should show it running,
         * not an empty screen that invites somebody to start a second one.
         */
        this.run = data.run || null;
        this.stale = !!data.runIsStale;

        /*
         * Restore the dismissal, but never for a run that is still going.
         * A live run's panel is the page; dismissing one must not be a way to
         * hide an update that is halfway through applying itself.
         */
        this.dismissed = dismissalApplies(this.run, this.storedDismissal());

        this.firstLoad = false;
        m.redraw();
      })
      .catch((e) => {
        /*
         * The reason, on screen. Swallowing it and rendering an empty page
         * is how somebody ends up staring at a screen that looks broken with
         * nowhere to look next.
         */
        this.loadError = serverMessage(e) || app.translator.trans('flarum-extension-manager.admin.page.load_failed', {}, true);
        this.firstLoad = false;
        m.redraw();
      });
  }

  content() {
    if (this.firstLoad) {
      return (
        <div className="ExtensionPage-settings">
          <div className="container">
            <LoadingIndicator />
          </div>
        </div>
      );
    }

    if (this.loadError && !this.host) {
      // Nothing loaded at all. Say what happened and offer the one useful action.
      return (
        <div className="ExtensionPage-settings">
          <div className="container">
            <Alert type="error" dismissible={false} className="ExtensionManager-alert">
              {this.loadError}
              {this.loadErrorDetail ? <code className="ExtensionManager-alertDetail">{this.loadErrorDetail}</code> : null}
            </Alert>
            <Button className="Button" onclick={() => this.attemptLoad()}>
              {app.translator.trans('flarum-extension-manager.admin.page.try_again')}
            </Button>
          </div>
        </div>
      );
    }

    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          {/*
           * While a run is live the panel is the ONLY thing on screen, and
           * the tabs go away. A grid of Update buttons beside a running update
           * invites somebody to start a second one, and the honest answer to
           * the second press is a refusal — better not to offer it.
           */}
          {this.showingRun() ? this.runPanel() : null}

          {this.hidesPage() ? null : (
            <div className="Tabs ExtensionManager-tabs">
              <div className="Tabs-nav" role="tablist">
                {this.tabItems().map((tab) => (
                  <Button
                    key={tab.id}
                    className="Button Button--link"
                    role="tab"
                    active={this.tab === tab.id}
                    aria-selected={this.tab === tab.id ? 'true' : 'false'}
                    onclick={() => (this.tab = tab.id)}
                  >
                    {tab.label}
                    {tab.badge > 0 ? <span className="ExtensionManager-count">{tab.badge}</span> : null}
                  </Button>
                ))}
              </div>
              <hr className="Tabs-divider" />
            </div>
          )}

          {this.hidesPage() ? null : this.tab === 'host' ? (
            <div>
              <HostPanel host={this.host} onchange={() => this.load()} />
              <TrashPanel />
            </div>
          ) : this.tab === 'sources' ? (
            <SourcesTab />
          ) : this.tab === 'history' ? (
            <HistoryTab history={this.history} installed={this.installed} />
          ) : this.tab === 'discover' ? (
            <DiscoverTab starting={this.starting} oninstall={(name: string) => this.start([name], 'install')} />
          ) : (
            this.installedTab()
          )}
        </div>
      </div>
    );
  }

  /** The page's tabs, in order, with the number of updates waiting on Installed. */
  tabItems(): { id: Tab; label: Mithril.Children; badge: number }[] {
    return [
      { id: 'discover', label: app.translator.trans('flarum-extension-manager.admin.sections.discover.title'), badge: 0 },
      {
        id: 'installed',
        label: app.translator.trans('flarum-extension-manager.admin.sections.installed.title', { count: this.installed.length }),
        badge: this.updateCount(),
      },
      { id: 'history', label: app.translator.trans('flarum-extension-manager.admin.sections.queue.title'), badge: 0 },
      { id: 'sources', label: app.translator.trans('flarum-extension-manager.admin.composer.title'), badge: 0 },
      { id: 'host', label: app.translator.trans('flarum-extension-manager.admin.host.title'), badge: 0 },
    ];
  }

  /**
   * A FINISHED run keeps its panel until somebody dismisses it. The screen
   * returning to normal on its own would throw away the log at the exact moment
   * it becomes useful — what changed, in what order, and what to check.
   */
  showingRun(): boolean {
    return showingRun(this.run, this.dismissed);
  }

  /**
   * Whether a run is still going.
   *
   * Only a LIVE run may take the page over. Hiding the grid while an update
   * is applying is right — a row of Update buttons beside a running update
   * invites a second one, and the honest answer to that is a refusal. Hiding it
   * because a run finished yesterday is not: the log is worth keeping on screen,
   * the rest of the page is worth having as well.
   */
  runIsLive(): boolean {
    return runIsLive(this.run);
  }

  /** The page's own content is suppressed only while something is happening. */
  protected hidesPage(): boolean {
    return hidesPage(this.run, this.dismissed);
  }

  /**
   * The Extension Manager updating ITSELF leaves this page running the old version's
   * code: the header still said v1.15.0 under a finished v1.16.1 update
   * (ClaudiusH, 2026-10-07). So the page reloads, once per run and outcome.
   * The finished panel comes straight back, because the run is on disk.
   */
  protected reloadIfSelf(run: Run | null) {
    // Flarum core too: the page's own JavaScript and version are core's.
    const reloads = (run?.packages || []).some((p) => p === SELF_PACKAGE || p === 'flarum/core');
    /*
     * And after an install. A new extension is missing from the admin
     * sidebar until the page reloads, so it could not be opened and switched
     * on, which is the obvious next step (ClaudiusH, 2026-10-09). So the page
     * reloads onto the new extension's own page.
     */
    const installed = run?.state === 'done' && run.mode === 'install' && (run.packages || []).length === 1 ? run.packages[0] : null;
    if (!run || !['done', 'rolled-back'].includes(run.state) || (!reloads && !installed)) return;

    const key = 'extension-manager.reloaded.' + run.id + '.' + run.state;

    try {
      if (sessionStorage.getItem(key)) return;
      sessionStorage.setItem(key, '1');
    } catch (e) {
      return; // No way to remember it: better a stale page than a reload loop.
    }

    if (installed) {
      // The run is dismissed first, or its panel would greet them on the way back.
      this.dismissed = true;
      this.rememberDismissed(run.id);
      // app.route() is a route path ("/extension/x"), not a URL; the admin
      // routes on the hash. Set synchronously, so the reload lands on it.
      window.location.hash = app.route('extension', {
        id: this.installed.find((e) => e.package === installed)?.id ?? extensionId(installed),
      });
    }

    window.location.reload();
  }

  runPanel() {
    return (
      <RunPanel
        run={this.run}
        driver={this.driver}
        busy={this.busy}
        stale={this.stale}
        rollbackNote={this.rollbackNote}
        ondismiss={() => {
          this.dismissed = true;
          this.rememberDismissed(this.run?.id ?? null);
          this.rollbackNote = null;
        }}
        onprogress={(data: StepPayload) => {
          this.run = data.run;
          this.busy = !!data.busy;
          this.stale = !!data.stale;
        }}
        ondone={(run: Run | null) => {
          this.run = run;
          // Versions on the cards are stale the moment an update lands.
          this.load();
          this.reloadIfSelf(run);
        }}
        onrollback={(data: RollbackPayload) => {
          this.run = data.run;
          this.rollbackNote = data.next || null;
          this.load();
          this.reloadIfSelf(data.run);
        }}
      />
    );
  }

  /**
   * Every package with a newer version, in one press.
   *
   * Resolved TOGETHER rather than as a queue of separate updates. Two
   * extensions can each have a newer version and still be uninstallable side by
   * side; asking Composer about all of them at once is the only way to find that
   * out before anything moves, rather than halfway through the second one.
   */
  updateAll() {
    const updatable = this.installed.filter((e) => cardOffers(e).update);
    const names = updatable.map((e) => e.package);

    if (names.length === 0) return null;

    /*
     * If any of them is pinned, "update everything" has to ask about the
     * requirements too — otherwise it quietly skips exactly the packages the
     * admin was most deliberate about, and reports success.
     */
    const pinned = updatable.filter((e) => cardOffers(e).repin);

    return (
      <div className="ExtensionManager-updateAll">
        <Button
          className="Button Button--primary"
          disabled={this.starting}
          onclick={() => (pinned.length ? this.confirmRepin(pinned, names) : this.start(names))}
        >
          {/* "Update all 1" read oddly (ClaudiusH): one update is named. */}
          {this.starting
            ? app.translator.trans('flarum-extension-manager.admin.updater.starting')
            : names.length === 1
              ? app.translator.trans('flarum-extension-manager.admin.extensions.update_one', { name: updatable[0].name })
              : app.translator.trans('flarum-extension-manager.admin.updater.update_all', { count: names.length })}
        </Button>
      </div>
    );
  }

  /**
   * Raising a pin edits composer.json, so it is asked for in those words.
   *
   * The new version is NOT sent to the server. The request carries a yes,
   * and the server takes the target from its own update check — so the only
   * version a requirement can move to is the one on the card the admin just
   * read. A version travelling from the browser into composer.json would be an
   * arbitrary string from a client.
   */
  confirmRepin(entry: Installed | Installed[], packages?: string[]) {
    const list = Array.isArray(entry) ? entry : [entry];
    const names = packages ?? list.map((e) => e.package);

    confirmAction({
      title: app.translator.trans('flarum-extension-manager.admin.extensions.update'),
      message: app.translator.trans('flarum-extension-manager.admin.extensions.repin_confirm', { count: list.length }),
      details: (
        <ul className="ExtensionManager-confirm-list">
          {list.map((e) => (
            <li key={e.package}>
              <code>{e.package}</code> {e.constraint} → {e.update?.to ?? '?'}
            </li>
          ))}
        </ul>
      ),
      confirmText: app.translator.trans('flarum-extension-manager.admin.extensions.update'),
      onconfirm: () => this.start(names, 'update', true),
    });
  }

  /**
   * Confirmed, because it is the one action here that takes something away.
   * Everything else can be undone by rolling back and is described as such; a
   * removal can too, but somebody should still mean it.
   */
  confirmRemove(e: Installed) {
    confirmAction({
      title: app.translator.trans('flarum-extension-manager.admin.extensions.remove_files'),
      message: app.translator.trans('flarum-extension-manager.admin.extensions.remove_confirm', { name: e.name }),
      confirmText: app.translator.trans('flarum-extension-manager.admin.extensions.remove'),
      danger: true,
      onconfirm: () => this.start([e.package], 'remove'),
    });
  }

  /**
   * @param mode 'install' adds a package that is not here yet. It is not
   *        cosmetic: `composer update` on an uninstalled package does nothing
   *        and exits 0, so a run in the wrong mode would pass every phase,
   *        change nothing, and report success.
   */
  start(packages: string[], mode: RunMode = 'update', repin = false, nightly = false) {
    this.starting = true;
    this.rollbackNote = null;
    m.redraw();

    app
      .request<StartPayload>({
        method: 'POST',
        url: apiUrl() + '/extension-manager/update',
        body: { packages, mode, repin, nightly },
        /*
         * The server's own words. Every refusal it sends names the situation
         * — a host too small, a run already going and how long since it moved —
         * and paraphrasing that into "could not start" throws away the only
         * part anybody can act on.
         */
        errorHandler: alertError('flarum-extension-manager.admin.run.start_failed'),
      })
      .then((data) => {
        this.starting = false;
        /*
         * Un-dismissed only once the NEW run exists. Doing it before the
         * request re-mounted the panel on the OLD, finished run: it polled,
         * was told "idle", and stopped — in the same second the new run began.
         */
        this.dismissed = false;
        this.run = data.run;
        this.driver = data.driver || null;
        m.redraw();
      })
      .catch((e) => {
        this.starting = false;
        // Refused because a run is already going (409): load that run, so it can be watched.
        if ((e as RequestError | null)?.status === 409) {
          this.dismissed = false;
          this.load();
        }
        m.redraw();
      });
  }

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
  installedTab() {
    return [
      this.checkLine(),
      /*
       * Above the extensions, because it is the thing whose blast radius is
       * the whole forum. It is also the one panel that disables its own button —
       * a core update with blocked extensions would be refused by Composer at
       * the end of a long wait, and knowing that in advance is the point.
       */
      <CorePanel
        starting={this.starting}
        checkedAt={this.updates?.checkedAt ?? null}
        onbegin={(pkgs: string[]) => this.start(pkgs)}
        onnightly={() => this.start(['flarum/core'], 'update', false, true)}
      />,
      this.updateAll(),
      this.grid(),
    ].filter(Boolean);
  }

  /**
   * One count everywhere: the extensions "Update all" would update. The tab
   * badge and the line above counted every newer package, Flarum itself
   * included, so they read 17 beside "Update all 16" and stayed one ahead
   * after each update (ClaudiusH, 2026-10-09). Flarum has its own panel, and
   * the line names it separately.
   */
  updateCount(): number {
    return this.installed.filter((e) => cardOffers(e).update).length;
  }

  coreHasUpdate(): boolean {
    return !!this.updates.available?.['flarum/core'];
  }

  /**
   * The count is always shown WITH its age and its blind spots. "1 newer
   * version" is a claim; "1 newer version, checked 2 hours ago, 9 packages could
   * not be checked" is the truth, and only the second lets somebody decide
   * whether to believe it.
   */
  checkLine() {
    const n = this.updateCount();
    const uncheckable = (this.updates.uncheckable || []).length;
    const tracking = (this.updates.tracking || []).length;
    const age = this.checkAge();

    // Never checked: there is no result to state, only the prompt (ClaudiusH, 2026-10-09:
    // "up to date" beside "check for updates" contradicted itself).
    const checkedAt = this.updates.checkedAt;

    if (!checkedAt) {
      return (
        <div className="ExtensionManager-checkline ExtensionManager-checkline--overdue">
          <p className="ExtensionManager-checkPrompt">{app.translator.trans('flarum-extension-manager.admin.updater.check_overdue')}</p>
          {this.checkButton(age)}
        </div>
      );
    }

    return (
      <div className={'ExtensionManager-checkline ExtensionManager-checkline--' + age}>
        {age === 'overdue' ? (
          <p className="ExtensionManager-checkPrompt">{app.translator.trans('flarum-extension-manager.admin.updater.check_overdue')}</p>
        ) : null}
        <span>
          {app.translator.trans('flarum-extension-manager.admin.updater.check_line', {
            summary: this.checkSummary(n),
            when: this.ago(checkedAt),
            uncheckable,
            tracking,
          })}
        </span>
        {this.checkButton(age)}
      </div>
    );
  }

  /** The check line's first sentence: what the last check found. */
  checkSummary(n: number): string {
    const core = this.coreHasUpdate();
    const key = n === 0 ? (core ? 'core_newer' : 'up_to_date') : core ? 'some_newer_and_core' : 'some_newer';

    return app.translator.trans('flarum-extension-manager.admin.updater.' + key, { count: n }, true);
  }

  /**
   * How hard to ask, by how old the last check is (ClaudiusH, 2026-10-09):
   * checked today, a quiet link to check again; within the week, an ordinary
   * button; older than that, or never, a prompt that says why it matters.
   */
  checkAge(): CheckAge {
    const at = this.updates.checkedAt;
    if (!at) return 'overdue';

    const days = (Date.now() / 1000 - at) / 86400;

    return days < 1 ? 'fresh' : days <= 7 ? 'recent' : 'overdue';
  }

  checkButton(age: CheckAge) {
    const className = age === 'fresh' ? 'Button Button--link' : age === 'recent' ? 'Button' : 'Button Button--primary';

    return (
      <Button
        className={className + ' ExtensionManager-checkNow'}
        disabled={this.checking}
        // A spinner as well as the words: "Checking…" alone reads as a label.
        icon={this.checking ? 'fas fa-circle-notch fa-spin' : undefined}
        onclick={() => this.checkNow()}
      >
        {this.checking
          ? app.translator.trans('flarum-extension-manager.admin.updater.checking')
          : age === 'fresh'
            ? app.translator.trans('flarum-extension-manager.admin.updater.check_now')
            : app.translator.trans('flarum-extension-manager.admin.updater.check_for_updates')}
      </Button>
    );
  }

  checkNow() {
    this.checking = true;
    m.redraw();

    app
      .request<CheckPayload>({ method: 'POST', url: apiUrl() + '/extension-manager/check' })
      .then((data) => {
        this.updates = data.updates || this.updates;
        this.checking = false;
        // The cards carry their own copy of each update, and the check
        // returns only the list: a new update was counted in the line above
        // and missing from every card until a reload (ClaudiusH). Reload the
        // state the cards are built from.
        this.load();
      })
      .catch(() => {
        this.checking = false;
        m.redraw();
      });
  }

  ago(unix: number): string {
    const mins = Math.max(1, Math.round(Date.now() / 1000 - unix) / 60);
    if (mins < 60) return app.translator.trans('flarum-extension-manager.admin.updater.ago.minutes', { count: Math.round(mins) }, true);
    const hours = Math.round(mins / 60);
    return hours < 48
      ? app.translator.trans('flarum-extension-manager.admin.updater.ago.hours', { count: hours }, true)
      : app.translator.trans('flarum-extension-manager.admin.updater.ago.days', { count: Math.round(hours / 24) }, true);
  }

  /** Anything with a newer version first — that is what somebody came to see. */
  sorted(): Installed[] {
    return sortForGrid(this.installed);
  }

  cardTop(e: Installed) {
    return [
      <div
        className="ExtensionManager-icon"
        style={{ background: e.icon?.backgroundColor || 'var(--primary-color)', color: e.icon?.color || 'var(--button-primary-color)' }}
      >
        {e.library ? <i className="fas fa-box" /> : e.icon?.name ? <i className={e.icon.name} /> : e.name.charAt(0)}
      </div>,
      <div className="ExtensionManager-cardId">
        <div className="ExtensionManager-name">{e.name}</div>
        <div className="ExtensionManager-pkg">
          {e.library ? app.translator.trans('flarum-extension-manager.admin.extensions.library') : e.package}
        </div>
      </div>,
    ];
  }

  grid() {
    return (
      <div className="ExtensionManager-grid">
        {this.sorted().map((e) => (
          <div className={'ExtensionManager-card' + (cardOffers(e).highlight ? ' ExtensionManager-card--update' : '')} key={e.id}>
            {/* The extension's own settings page, the way the Extensions list opens it. A library has none. */}
            {e.library ? (
              <div
                className="ExtensionManager-cardTop"
                title={app.translator.trans('flarum-extension-manager.admin.extensions.library_why', {}, true)}
              >
                {this.cardTop(e)}
              </div>
            ) : (
              <Link
                className="ExtensionManager-cardTop"
                href={app.route('extension', { id: e.id })}
                title={app.translator.trans('flarum-extension-manager.admin.extensions.open_settings', { name: e.name }, true)}
              >
                {this.cardTop(e)}
              </Link>
            )}

            {/*
             * One place for the version: the pair when there is a newer one,
             * else the version installed. The border and the Update button
             * already say an update is waiting — a badge saying it a third
             * time only crowded the package name out (ClaudiusH, 2026-10-13).
             */}
            <div className="ExtensionManager-meta">
              <span>
                {e.update
                  ? `${e.update.from} → ${e.update.to}`
                  : e.version || app.translator.trans('flarum-extension-manager.admin.extensions.version_unknown')}
              </span>
            </div>

            <div className="ExtensionManager-foot">
              {/*
               * Always two groups: what is TRUE about this extension on the
               * left, what you can DO with it on the right. The foot is
               * space-between, so loose chips get pushed to opposite ends of
               * the card and read as unrelated — which is what "enabled" and
               * "local checkout" did.
               */}
              <span className="ExtensionManager-tags">
                {e.library ? (
                  <span
                    className="ExtensionManager-tag ExtensionManager-tag--muted"
                    title={app.translator.trans('flarum-extension-manager.admin.extensions.library_why', {}, true)}
                  >
                    {app.translator.trans('flarum-extension-manager.admin.extensions.library')}
                  </span>
                ) : (
                  <span className={'ExtensionManager-tag' + (e.enabled ? ' ExtensionManager-tag--ok' : '')}>
                    {e.enabled
                      ? app.translator.trans('flarum-extension-manager.admin.extensions.enabled')
                      : app.translator.trans('flarum-extension-manager.admin.extensions.disabled')}
                  </span>
                )}
                {e.pathInstall ? (
                  <span
                    className="ExtensionManager-tag ExtensionManager-tag--muted"
                    title={app.translator.trans('flarum-extension-manager.admin.extensions.path_install_why', {}, true)}
                  >
                    {app.translator.trans('flarum-extension-manager.admin.extensions.path_install')}
                  </span>
                ) : null}
              </span>

              <span className="ExtensionManager-actions">
                {/*
                 * What the update gives you, from the release on GitHub: not
                 * every author posts changelogs anywhere else.
                 */}
                {e.update?.notes ? (
                  <Button
                    className="Button Button--link Button--sm ExtensionManager-notes"
                    onclick={() =>
                      app.modal.show(() => import('./ReleaseNotesModal'), {
                        name: e.name,
                        package: e.package,
                        from: e.update!.from,
                        to: e.update!.to,
                      })
                    }
                  >
                    {app.translator.trans('flarum-extension-manager.admin.extensions.learn_more')}
                  </Button>
                ) : null}
                {cardOffers(e).update ? (
                  <Button
                    className="Button Button--primary Button--sm"
                    disabled={this.starting}
                    onclick={() => (cardOffers(e).repin ? this.confirmRepin(e) : this.start([e.package]))}
                  >
                    {app.translator.trans('flarum-extension-manager.admin.extensions.update')}
                  </Button>
                ) : null}
                {cardOffers(e).remove ? (
                  <Button className="Button Button--sm" disabled={this.starting} onclick={() => this.confirmRemove(e)}>
                    {app.translator.trans('flarum-extension-manager.admin.extensions.remove')}
                  </Button>
                ) : null}
              </span>
            </div>
          </div>
        ))}
      </div>
    );
  }
}
