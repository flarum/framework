import app from 'flarum/admin/app';
import t from '../t';
import apiUrl from '../apiUrl';
import Component from 'flarum/common/Component';

declare const m: any;


interface Row {
  package: string;
  installed: string;
  state: 'ready' | 'needs-update' | 'blocked' | 'unknown';
  requires: string | null;
  to: string | null;
}

interface CoreAttrs {
  starting: boolean;
  /** When the page's update check last ran; a new one means this panel asks again. */
  checkedAt: number | null;
  /*
   * 🚨 NOT `onupdate`. That is one of Mithril's component lifecycle hooks, so an
   * attr by that name is called by Mithril on every redraw — with a vnode, not a
   * package list. A core update would have started every time the page repainted.
   * `oninit`, `oncreate`, `onbeforeupdate`, `onupdate`, `onbeforeremove` and
   * `onremove` are all spoken for; a callback attr must avoid the whole set.
   */
  onbegin: (packages: string[]) => void;
  onnightly: () => void;
}

/** Asked in batches, because each one can cost an outbound call. */
const BATCH = 12;

/**
 * Flarum itself.
 *
 * 🚨 Updating core is the one operation whose blast radius is the whole forum,
 * so the question "what would this do to my extensions" is answered before
 * anything is pressed, per extension, by name. Composer's answer to the same
 * question is a refusal at the end of a long wait, written in package
 * constraints, from which somebody has to work out which of their thirty
 * extensions is the problem.
 *
 * 🚨 The pre-flight arrives in two parts and this is not an optimisation. The
 * cheap half — does the version you ALREADY have declare support — is in
 * composer.lock and is instant for any number of extensions. The rest costs one
 * HTTP call each: 58 of them took 28 seconds in a single request when this was
 * first built, which is the very failure the extension exists to prevent.
 */
export default class CorePanel extends Component<CoreAttrs> {
  private loading = true;
  private current: string | null = null;
  private newest: { from: string; to: string } | null = null;
  private preflight: any = null;
  private checking = false;
  private error: string | null = null;
  private seenCheck: number | null = null;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.seenCheck = this.attrs.checkedAt;
    this.load();
  }

  /*
   * 🚨 Asks again after every check. This panel loads once, so "Check now" found
   * Flarum 2.0.1 in the line above while this box still said no newer Flarum had
   * been published, until a reload (ClaudiusH, 2026-10-14).
   */
  onupdate() {
    if (this.attrs.checkedAt === this.seenCheck) return;
    this.seenCheck = this.attrs.checkedAt;
    this.load();
  }

  view() {
    if (this.loading) return <div className="ExtensionManager-core">{t('core_loading')}</div>;

    return (
      <div className="ExtensionManager-core">
        <div className="ExtensionManager-coreHead">
          <div>
            <div className="ExtensionManager-coreTitle">{t('core_title')}</div>
            <div className="ExtensionManager-corenow">{t('core_running', { version: this.current })}</div>
          </div>
          {this.newest ? (
            <button
              className="Button Button--primary"
              disabled={this.attrs.starting || this.blocked() > 0}
              onclick={() => this.attrs.onbegin(this.packagesToUpdate())}
            >
              {t('core_update_to', { version: this.newest.to })}
            </button>
          ) : null}
        </div>

        {this.error ? <div className="ExtensionManager-notice">{this.error}</div> : null}

        {!this.newest ? (
          <div className="ExtensionManager-coreNote">{t('core_current')}</div>
        ) : (
          /*
           * 🚨 .filter(Boolean), because a `null` child counts as an unkeyed
           * vnode and Mithril refuses a children list that mixes keyed and
           * unkeyed. Both of these return null in ordinary situations —
           * summary() before the pre-flight arrives, rows() when nothing is
           * blocked — so the unfiltered array threw during view exactly when a
           * newer Flarum existed, which is the only time anyone opens this
           * panel. Nothing here is keyed now, which is the durable half of the
           * fix.
           */
          [this.summary(), this.rows()].filter(Boolean)
        )}

        {/*
          * The nightly build: Flarum and its bundled extensions moved to the
          * development branch together, for testing what the next release
          * will be. The server works out which packages and which branch;
          * undo returns the forum to the release it was on.
          */}
        <div className="ExtensionManager-coreNightly">
          <div>
            <b>{t('core_nightly_title')}</b> {t('core_nightly_help')}
          </div>
          <button
            className="Button"
            disabled={this.attrs.starting}
            onclick={() => confirm(t('core_nightly_confirm')) && this.attrs.onnightly()}
          >
            {t('core_nightly_button')}
          </button>
        </div>
      </div>
    );
  }

  summary() {
    const p = this.preflight;
    if (!p) return null;

    const blocked = this.blocked();
    const updating = p.updating || 0;
    const pending = (p.pending || []).length;

    return (
      <div className="ExtensionManager-coreSummary">
        {/*
          * 🚨 The blocking case is stated as a consequence, not as a count. "3
          * extensions" is a number; "these three have no release that works with
          * Flarum 2.1, so the upgrade would be refused" is something somebody can
          * act on — and it is why the button above is disabled.
          */}
        {blocked > 0 ? (
          <div className="ExtensionManager-coreBlocked">{t('core_blocked', { count: blocked, version: p.target })}</div>
        ) : pending === 0 ? (
          <div className="ExtensionManager-coreClear">{t('core_clear', { version: p.target })}</div>
        ) : null}

        {updating > 0 ? <div>{t('core_updating_too', { count: updating })}</div> : null}
        {pending > 0 ? <div className="ExtensionManager-coreChecking">{t('core_checking', { count: pending })}</div> : null}
      </div>
    );
  }

  rows() {
    const rows: Row[] = this.preflight?.verdicts || [];

    // Only the ones that are not simply fine. A wall of "ready" is not an answer
    // to "what stops me", and there are fifty-eight of them on this forum.
    const interesting = rows.filter((r) => r.state !== 'ready');

    if (interesting.length === 0) return null;

    return (
      <ul className="ExtensionManager-coreRows">
        {interesting.map((r) => (
          <li key={r.package} className={'ExtensionManager-coreRow is-' + r.state}>
            <span className="ExtensionManager-corePkg">{r.package}</span>
            <span className="ExtensionManager-coreState">
              {r.state === 'blocked'
                ? t('core_row_blocked', { requires: r.requires || '?' })
                : r.state === 'needs-update'
                  ? t('core_row_updating', { version: r.to })
                  : t('core_row_unknown')}
            </span>
          </li>
        ))}
      </ul>
    );
  }

  blocked(): number {
    return this.preflight?.blocked || 0;
  }

  /**
   * 🚨 Core AND every extension that needs to move, resolved together in one
   * request. Updating core alone would leave Composer refusing, because the
   * extensions still on disk pin the old version — and updating them one at a
   * time afterwards means a forum that is broken in between.
   */
  packagesToUpdate(): string[] {
    const rows: Row[] = this.preflight?.verdicts || [];

    return ['flarum/core', ...rows.filter((r) => r.state === 'needs-update').map((r) => r.package)];
  }

  load() {
    app
      .request({ method: 'GET', url: apiUrl() + '/extension-manager/core' })
      .then((data: any) => {
        this.current = data.current;
        this.newest = data.newest || null;
        // A pre-flight belongs to the version it was run against.
        if (!this.newest || this.newest.to !== this.preflight?.target) this.preflight = null;
        this.loading = false;
        m.redraw();

        // Only worth a pre-flight when there is something to fly to.
        if (this.newest) this.runPreflight(this.newest.to);
      })
      .catch(() => {
        this.loading = false;
        this.error = t('core_failed');
        m.redraw();
      });
  }

  runPreflight(target: string) {
    app
      .request({ method: 'GET', url: apiUrl() + '/extension-manager/core?target=' + encodeURIComponent(target) })
      .then((data: any) => {
        this.preflight = data.preflight;
        m.redraw();

        const pending: string[] = this.preflight?.pending || [];
        if (pending.length) this.resolve(pending, target);
      })
      .catch(() => {
        this.error = t('core_failed');
        m.redraw();
      });
  }

  /** One batch at a time, so no single request can cause a burst of outbound calls. */
  resolve(pending: string[], target: string) {
    if (pending.length === 0) {
      this.checking = false;
      m.redraw();
      return;
    }

    this.checking = true;
    const batch = pending.slice(0, BATCH);
    const rest = pending.slice(BATCH);

    app
      .request({
        method: 'POST',
        url: apiUrl() + '/extension-manager/discover/compat',
        body: { packages: batch, core: target },
      })
      .then((data: any) => {
        this.applyVerdicts(data.verdicts || {});
        this.preflight.pending = rest;
        m.redraw();
        this.resolve(rest, target);
      })
      .catch(() => {
        /*
         * 🚨 The rows already answered stay. Failing to reach Packagist for one
         * batch is not a reason to throw away the fifty extensions the lock
         * already cleared — those answers cost nothing and are still true.
         */
        this.checking = false;
        this.preflight.pending = [];
        m.redraw();
      });
  }

  applyVerdicts(verdicts: Record<string, any>) {
    const rows: Row[] = this.preflight?.verdicts || [];

    rows.forEach((r) => {
      const v = verdicts[r.package];
      if (!v) return;

      if (v.compatible === true) {
        r.state = 'needs-update';
        r.to = v.version;
      } else if (v.compatible === false) {
        r.state = 'blocked';
        r.requires = v.requires || r.requires;
      }
      // null stays unknown — not on Packagist is not the same as incompatible.
    });

    const order: Record<string, number> = { blocked: 0, unknown: 1, 'needs-update': 2, ready: 3 };
    rows.sort((a, b) => order[a.state] - order[b.state] || a.package.localeCompare(b.package));

    this.preflight.blocked = rows.filter((r) => r.state === 'blocked').length;
    this.preflight.updating = rows.filter((r) => r.state === 'needs-update').length;
  }
}
