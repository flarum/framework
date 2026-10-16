import app from 'flarum/admin/app';
import t from '../t';
import apiUrl from '../apiUrl';
import { liveSearchPlan, submittedSearchPlan } from '../searchPolicy';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

declare const m: any;


interface Found {
  name: string;
  description: string;
  downloads: number;
  favers: number;
  repository: string;
  abandoned: boolean | string;
  installed: boolean;
}

interface Verdict {
  compatible: boolean | null;
  version: string | null;
  requires: string | null;
  stability: string | null;
  /** True when this name is a dead end another package took over. */
  replaced?: boolean;
  /** That package, when it could be named. */
  replacedBy?: string | null;
}

interface DiscoverAttrs {
  starting: boolean;
  oninstall: (packageName: string) => void;
}

/**
 * Finding something to install.
 *
 * 🚨 The one thing this does that the current tooling does not: it says whether
 * a result works with the Flarum you actually have, BEFORE you press anything.
 * The previous Extension Manager lists all of Packagist and lets you discover at install time
 * that an extension is Flarum 1 only — after the resolve, after the wait, and in
 * the form of a constraint error rather than a sentence.
 *
 * 🚨 Verdicts arrive in a second request, so results appear immediately and the
 * badges fill in. Waiting on a dozen round trips before showing anything would
 * be the slower, worse version of the same screen.
 */
export default class DiscoverTab extends Component<DiscoverAttrs> {
  /*
   * 🚨 The list loads on open, with no query. The previous Extension Manager shows everything
   * installable straight away, and that is the right behaviour: somebody opening
   * a tab called "Find extensions" is asking what exists, not to guess a search
   * term first. The first version required typing before anything appeared,
   * which made a catalogue behave like a command line.
   *
   * Packagist orders an untyped `type=flarum-extension` query by popularity, so
   * the default view is the extensions most forums actually run.
   */
  oninit(vnode: any) {
    super.oninit(vnode);
    this.search();
  }

  private query = '';
  /** '' is everything; the rest are the keys of Packagist::CATEGORIES. */
  private category = '';
  private searching = false;
  private searched = false;
  private page = 1;
  private more = false;
  private loadingMore = false;
  private results: Found[] = [];
  private verdicts: Record<string, Verdict> = {};
  private installedNames: string[] = [];
  private checking = false;
  private error: string | null = null;
  private core: string | null = null;
  private seq = 0;
  /** The query whose results are on screen, so a keystroke can tell if anything changed. */
  private lastSearched: string | null = null;
  /** The pending debounce, so a new keystroke replaces it rather than adding to it. */
  private typing: ReturnType<typeof setTimeout> | null = null;

  view() {
    return (
      <div className="ExtensionManager-discover">
        <form
          className="ExtensionManager-search"
          onsubmit={(e: Event) => {
            e.preventDefault();

            /*
             * 🚨 Cancel the pending keystroke search first, or Return fires one
             * search immediately and a second one 350ms later for the same
             * query — two round trips and a list that redraws under the reader.
             */
            this.stopTyping();

            if (submittedSearchPlan().search) this.search();
          }}
        >
          <input
            className="FormControl"
            type="search"
            placeholder={t('discover_placeholder')}
            value={this.query}
            oninput={(e: any) => {
              this.query = e.target.value;
              this.queueSearch();
            }}
          />
          {/*
            * Kept, and not decorative: it forces a search the typing rule
            * declines — a one-character vendor prefix, or the same query again
            * after an error.
            */}
          <button className="Button Button--primary" type="submit" disabled={this.searching}>
            {this.searching ? t('searching') : t('search')}
          </button>
        </form>

        {/*
          * Under the search rather than as tabs: the tab row above is already the
          * page's own navigation, and a second row of tabs reads as a level of it.
          * A row of chips, so every category is visible without opening anything.
          */}
        <div className="ExtensionManager-chips" role="radiogroup" aria-label={t('category_label')}>
          {['', 'theme', 'language', 'widget'].map((c) => (
            <button
              type="button"
              role="radio"
              aria-checked={this.category === c ? 'true' : 'false'}
              className={'ExtensionManager-chip' + (this.category === c ? ' is-on' : '')}
              onclick={() => {
                if (this.category === c) return;
                this.category = c;
                this.stopTyping();
                this.search();
              }}
            >
              {t('category_' + (c || 'all'))}
            </button>
          ))}
        </div>

        <div className="ExtensionManager-searchNote">
          {this.core ? t('discover_against', { version: this.core }) : t('discover_hint')}
        </div>

        {this.error ? <div className="ExtensionManager-notice">{this.error}</div> : null}

        {this.searching ? <LoadingIndicator /> : null}

        {!this.searching && this.searched && this.results.length === 0 && !this.error ? (
          <div className="ExtensionManager-empty">
            {this.query ? t('discover_none', { query: this.query }) : t('discover_empty')}
          </div>
        ) : null}

        <div className="ExtensionManager-grid">{this.results.map((r) => this.card(r))}</div>

        {this.more && !this.searching ? (
          <div className="ExtensionManager-more">
            <button className="Button" disabled={this.loadingMore} onclick={() => this.showMore()}>
              {this.loadingMore ? t('loading_more') : t('show_more')}
            </button>
          </div>
        ) : null}
      </div>
    );
  }

  /**
   * A keystroke, considered.
   *
   * The rule lives in searchPolicy so it can be tested; this is only the timer.
   */
  queueSearch() {
    const plan = liveSearchPlan(this.query, this.lastSearched);

    this.stopTyping();

    if (! plan.search) return;

    this.typing = setTimeout(() => {
      this.typing = null;
      this.search();
    }, plan.delayMs);
  }

  stopTyping() {
    if (this.typing !== null) {
      clearTimeout(this.typing);
      this.typing = null;
    }
  }

  /*
   * 🚨 A timer that outlives its component fires into a dead tab: it redraws a
   * page that has moved on, and on a slow search it can overwrite the results
   * of whatever the reader opened next.
   */
  onremove() {
    this.stopTyping();
  }

  card(r: Found) {
    const v = this.verdicts[r.name];
    const instead = this.replacement(r, v);
    const [vendor, short] = r.name.split('/');

    return (
      <div className="ExtensionManager-card" key={r.name}>
        <div className="ExtensionManager-cardTop">
          <div className="ExtensionManager-icon" style={{ background: 'var(--control-bg)', color: 'var(--muted-color)' }}>
            {(short || vendor || '?').charAt(0).toUpperCase()}
          </div>
          <div>
            <a
              className="ExtensionManager-name"
              href={'https://packagist.org/packages/' + r.name}
              target="_blank"
              rel="noopener noreferrer"
              title={t('open_packagist', { name: r.name })}
            >
              {short}
            </a>
            <div className="ExtensionManager-pkg">{r.name}</div>
          </div>
        </div>

        {/* Clamped to two lines, so the whole of it on hover. */}
        <div className="ExtensionManager-desc" title={r.description}>{r.description}</div>

        <div className="ExtensionManager-meta">
          <span>
            <i className="fas fa-circle-down" aria-hidden="true" /> {t('downloads', { count: this.short(r.downloads) })}
          </span>
        </div>

        <div className="ExtensionManager-foot">
          <span className="ExtensionManager-tags">
            {/*
              * 🚨 Abandoned and "does it work" are separate facts and both are
              * shown. Putting abandoned in the single badge slot hid the
              * compatibility answer while still offering Install, so the tag
              * beside the button read like a verdict on the wrong question.
              */}
            {/*
              * "Installed as X" already says it was replaced, and by what, so
              * "replaced by X" beside it only said it twice (ClaudiusH, 2026-10-13).
              */}
            {instead && this.installedNames.includes(instead) ? null : instead ? (
              <span className="ExtensionManager-tag ExtensionManager-tag--warn" title={t('replaced_why')}>
                {t('replaced_by', { replacement: instead })}
              </span>
            ) : v?.replaced ? (
              <span className="ExtensionManager-tag ExtensionManager-tag--warn" title={t('replaced_why')}>
                {t('replaced')}
              </span>
            ) : r.abandoned ? (
              <span className="ExtensionManager-tag ExtensionManager-tag--warn" title={this.abandonedTitle(r)}>
                {t('abandoned')}
              </span>
            ) : null}
            {this.badge(v)}
          </span>
          {this.action(r, v)}
        </div>
      </div>
    );
  }

  /**
   * 🚨 Four states, and "we don't know yet" is one of them. Collapsing unknown
   * into "no" hides working extensions; collapsing it into "yes" offers ones
   * that cannot install. Either way somebody stops believing the badge.
   */
  badge(v?: Verdict) {
    if (!v) {
      return <span className="ExtensionManager-tag ExtensionManager-tag--muted">{this.checking ? t('checking') : '—'}</span>;
    }

    if (v.compatible === null) {
      return <span className="ExtensionManager-tag ExtensionManager-tag--muted">{t('compat_unknown')}</span>;
    }

    if (!v.compatible) {
      return (
        <span className="ExtensionManager-tag ExtensionManager-tag--muted" title={t('compat_no_why', { requires: v.requires || '?' })}>
          {t('compat_no')}
        </span>
      );
    }

    // 🚨 A pre-release is labelled as one. Most forums are on stable minimum
    // stability and cannot install it at all, and somebody who can should still
    // be told that is what they are getting.
    return v.stability && v.stability !== 'stable' ? (
      <span className="ExtensionManager-tag ExtensionManager-tag--warn">{t('compat_prerelease', { version: v.version })}</span>
    ) : (
      <span className="ExtensionManager-tag ExtensionManager-tag--ok">{v.version}</span>
    );
  }

  /**
   * Packagist records a replacement package when the author names one, and that
   * is the single most useful thing to say about an abandoned extension.
   */
  abandonedTitle(r: Found): string {
    return typeof r.abandoned === 'string' && r.abandoned
      ? t('abandoned_for', { replacement: r.abandoned })
      : t('abandoned_why');
  }

  /**
   * The package to use instead of this one, if any: the one Packagist names for
   * an abandoned package, or the new name of a repository that moved.
   */
  replacement(r: Found, v?: Verdict): string | null {
    if (v?.replacedBy) return v.replacedBy;
    return typeof r.abandoned === 'string' && r.abandoned ? r.abandoned : null;
  }

  action(r: Found, v?: Verdict) {
    if (r.installed) {
      return <span className="ExtensionManager-tag ExtensionManager-tag--ok">{t('already_installed')}</span>;
    }

    /*
     * 🚨 A replaced package is never offered under its old name. Installing
     * v17development/flarum-seo gave the previous Extension Manager users FoF SEO under the
     * wrong id, which then refused to enable (discuss.flarum.org d/40012). The
     * button installs what replaced it, and the server refuses the old name
     * whatever the screen offered.
     */
    const instead = this.replacement(r, v);

    if (instead) {
      if (this.installedNames.includes(instead)) {
        return <span className="ExtensionManager-tag ExtensionManager-tag--ok">{t('installed_as', { replacement: instead })}</span>;
      }

      return this.verdicts[instead]?.compatible !== true ? null : (
        <button className="Button Button--primary Button--sm" disabled={this.attrs.starting} onclick={() => this.attrs.oninstall(instead)}>
          {t('install_replacement', { replacement: instead })}
        </button>
      );
    }

    if (v?.replaced) return null;

    // Not offered when it cannot work. The badge beside it says why.
    if (!v || v.compatible !== true) return null;

    return (
      <button
        className="Button Button--primary Button--sm"
        disabled={this.attrs.starting}
        onclick={() => this.attrs.oninstall(r.name)}
      >
        {t('install')}
      </button>
    );
  }

  short(n: number): string {
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return Math.round(n / 1000) + 'k';
    return String(n);
  }

  search() {
    const q = this.query.trim();

    /*
     * 🚨 Every search carries a number, and a reply is dropped unless it is the
     * newest. Typing fast means several searches in flight, and without this the
     * slowest one wins — the results end up belonging to a query the person
     * already moved on from.
     */
    const mine = ++this.seq;

    /*
     * Recorded when the search STARTS, not when it returns: it is what the
     * typing rule compares against, and between here and the reply every
     * keystroke that types the same string back would otherwise queue another
     * identical request.
     */
    this.lastSearched = q;

    this.page = 1;
    this.searching = true;
    this.searched = true;
    this.error = null;
    m.redraw();

    app
      .request({ method: 'GET', url: apiUrl() + '/extension-manager/discover?q=' + encodeURIComponent(q) + '&category=' + this.category })
      .then((data: any) => {
        if (mine !== this.seq) return;

        this.searching = false;
        this.results = data.results || [];
        this.installedNames = data.installedNames || this.installedNames;
        this.more = !!data.more;
        this.error = data.error || null;
        m.redraw();

        if (this.results.length) this.checkCompat(this.results.map((r) => r.name), mine);
      })
      .catch(() => {
        if (mine !== this.seq) return;
        this.searching = false;
        /*
         * 🚨 Forget the query on failure, so the same one can be tried again.
         * Otherwise a network blip leaves the box holding a query the typing
         * rule now considers already-shown, and nothing the reader types —
         * short of changing it — will retry.
         */
        this.lastSearched = null;
        this.error = t('discover_failed');
        m.redraw();
      });
  }

  /**
   * The next page, appended.
   *
   * 🚨 Appended rather than replacing, because browsing is a scroll and losing
   * what you already looked at to see more of it is the wrong trade. Each page
   * costs its own compatibility pass, which is why they are fetched a page at a
   * time rather than all 2306 at once.
   */
  showMore() {
    const mine = this.seq;
    this.loadingMore = true;
    m.redraw();

    app
      .request({
        method: 'GET',
        url: apiUrl() + '/extension-manager/discover?page=' + (this.page + 1) + '&q=' + encodeURIComponent(this.query.trim()) + '&category=' + this.category,
      })
      .then((data: any) => {
        if (mine !== this.seq) return;

        this.page += 1;
        this.loadingMore = false;
        this.more = !!data.more;

        const fresh: Found[] = data.results || [];
        this.results = this.results.concat(fresh);
        m.redraw();

        if (fresh.length) this.checkCompat(fresh.map((r) => r.name), mine);
      })
      .catch(() => {
        if (mine !== this.seq) return;
        this.loadingMore = false;
        this.more = false;
        m.redraw();
      });
  }

  checkCompat(names: string[], mine: number) {
    this.checking = true;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: apiUrl() + '/extension-manager/discover/compat',
        body: { packages: names },
      })
      .then((data: any) => {
        if (mine !== this.seq) return;
        this.checking = false;
        this.verdicts = { ...this.verdicts, ...(data.verdicts || {}) };
        this.core = data.core || this.core;

        // A replacement is offered in place of the package it replaced, so it
        // is held to the same rule: no button until we know it works here.
        const owed = this.results
          .map((r) => this.replacement(r, this.verdicts[r.name]))
          .filter((n): n is string => !!n && !this.verdicts[n] && !this.installedNames.includes(n));
        if (owed.length) this.checkCompat([...new Set(owed)], mine);

        m.redraw();
      })
      .catch(() => {
        if (mine !== this.seq) return;
        // 🚨 The results stay. Failing to work out compatibility is not a
        // reason to throw away a search that worked — the badges just stay
        // unknown, which is what they honestly are.
        this.checking = false;
        m.redraw();
      });
  }
}
