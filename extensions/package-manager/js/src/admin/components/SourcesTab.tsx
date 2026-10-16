import extractText from 'flarum/common/utils/extractText';
import app from 'flarum/admin/app';
import t from '../t';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import apiUrl from '../apiUrl';

declare const m: any;

const s = t;

/**
 * Where Composer looks, what it will accept, and how it authenticates.
 *
 * 🚨 These three settings are why the extension this replaces could not simply
 * be removed. The Extension Manager could install and update, but it could not tell
 * Composer where a private repository lives, that release candidates are
 * acceptable, or how to authenticate to a paid one — and a forum whose own
 * extensions live in private repositories cannot resolve a single one without
 * them.
 *
 * 🚨 A stored credential is never sent to the browser. This screen shows which
 * hosts have one; entering a value replaces it. That is a deliberate limit, not
 * an oversight — a settings page that displays your token back to you turns
 * every screenshot into a leak.
 *
 * The screen reads a source the way a person names it: "ernestdefoe/bespoke on
 * GitHub", not a URL in a monospace column. Every value it shows is still the
 * exact one Composer gets.
 */

interface Repo {
  type: string;
  url: string;
}

interface Stored {
  kind: string;
  host: string;
  detail: string;
}

/** What a repository URL points at, for the icon and the readable name. */
function describe(url: string, type: string): { icon: string; name: string; where: string; tone: string } {
  if (type === 'path') {
    return { icon: 'fas fa-folder-open', name: url.replace(/\/+$/, '').split('/').pop() || url, where: url, tone: 'path' };
  }

  const m = /^https?:\/\/([^/]+)\/(.+?)(?:\.git)?\/?$/i.exec(url.trim());
  if (!m) return { icon: 'fas fa-box', name: url, where: '', tone: 'other' };

  const host = m[1].toLowerCase();
  const name = type === 'composer' ? host : m[2];

  if (host.endsWith('github.com')) return { icon: 'fab fa-github', name, where: host, tone: 'github' };
  if (host.includes('gitlab')) return { icon: 'fab fa-gitlab', name, where: host, tone: 'gitlab' };
  if (host.includes('bitbucket')) return { icon: 'fab fa-bitbucket', name, where: host, tone: 'bitbucket' };

  return { icon: type === 'composer' ? 'fas fa-cubes' : 'fas fa-code-branch', name, where: host, tone: 'other' };
}

const TYPES: Record<string, { icon: string }> = {
  vcs: { icon: 'fas fa-code-branch' },
  composer: { icon: 'fas fa-cubes' },
  path: { icon: 'fas fa-folder-open' },
};

const KINDS: Record<string, { icon: string; host: string }> = {
  'github-oauth': { icon: 'fab fa-github', host: 'github.com' },
  'gitlab-token': { icon: 'fab fa-gitlab', host: 'gitlab.com' },
  bearer: { icon: 'fas fa-key', host: '' },
  'http-basic': { icon: 'fas fa-user-lock', host: '' },
};

const LADDER = ['stable', 'RC', 'beta', 'alpha', 'dev'];

export default class SourcesTab extends Component {
  private loading = true;
  private saving = false;
  private data: any = null;
  private error: string | null = null;

  // repositories
  private repoType = 'vcs';
  private repoUrl = '';
  private filter = '';
  private confirmRepo: string | null = null;

  // credentials
  private authKind = 'github-oauth';
  private authHost = 'github.com';
  private authUser = '';
  private authSecret = '';
  private reveal = false;
  private confirmAuth: string | null = null;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.load();
  }

  view() {
    if (this.loading) return <LoadingIndicator />;
    if (!this.data) return <div className="ExtensionManager-notice">{this.error || t('config_failed')}</div>;

    return (
      <div className="ExtensionManager-sources ExtensionManager-src">
        {this.overview()}
        {this.error ? (
          <div className="ExtensionManager-src-error" role="alert">
            <i className="fas fa-circle-exclamation" aria-hidden="true" />
            <span>{this.error}</span>
          </div>
        ) : null}
        {this.repositories()}
        {this.stability()}
        {this.credentials()}
        <p className="ExtensionManager-src-note">
          <i className="fas fa-circle-info" aria-hidden="true" /> {this.data.note}
        </p>
      </div>
    );
  }

  // ── at a glance ─────────────────────────────────────────────────────────

  /**
   * Three answers before any detail: where Composer looks, how finished a
   * release must be, and how many hosts it can sign in to. Each tile jumps to
   * the card that changes it.
   */
  overview() {
    const repos: Repo[] = this.data.repositories || [];
    const stored: Stored[] = this.data.auth?.stored || [];
    const level = this.data.stability?.minimumStability || 'stable';

    const tile = (icon: string, value: any, label: any, target: string) => (
      <a
        className="ExtensionManager-src-stat"
        href={'#' + target}
        onclick={(e: Event) => {
          e.preventDefault();
          document.getElementById(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }}
      >
        <span className="ExtensionManager-src-statIcon"><i className={icon} aria-hidden="true" /></span>
        <span className="ExtensionManager-src-statText">
          <strong>{value}</strong>
          <span>{label}</span>
        </span>
      </a>
    );

    return (
      <div className="ExtensionManager-src-overview">
        {tile('fas fa-code-branch', repos.length, t('src_stat_repos', { count: repos.length }), 'mw-src-repos')}
        {tile('fas fa-flask', t('level_' + level.toLowerCase()), t('src_stat_level'), 'mw-src-stability')}
        {tile('fas fa-key', stored.length, t('src_stat_auth', { count: stored.length }), 'mw-src-auth')}
      </div>
    );
  }

  // ── repositories ────────────────────────────────────────────────────────

  repositories() {
    const repos: Repo[] = this.data.repositories || [];
    const q = this.filter.trim().toLowerCase();
    const shown = q ? repos.filter((r) => r.url.toLowerCase().includes(q)) : repos;

    return (
      <section className="ExtensionManager-src-card" id="mw-src-repos">
        <header className="ExtensionManager-src-head">
          <div className="ExtensionManager-src-headIcon"><i className="fas fa-code-branch" aria-hidden="true" /></div>
          <div className="ExtensionManager-src-headText">
            <h3>
              {t('repos_title')} <span className="ExtensionManager-src-count">{repos.length}</span>
            </h3>
            <p>{t('repos_help')}</p>
          </div>
          {repos.length > 6 ? (
            <label className="ExtensionManager-src-filter">
              <i className="fas fa-magnifying-glass" aria-hidden="true" />
              <input
                type="search"
                placeholder={s('src_filter')}
                aria-label={s('src_filter')}
                value={this.filter}
                oninput={(e: any) => (this.filter = e.target.value)}
              />
            </label>
          ) : null}
        </header>

        {repos.length === 0 ? (
          <div className="ExtensionManager-src-empty">
            <i className="fas fa-box-open" aria-hidden="true" />
            <span>{t('repos_none')}</span>
          </div>
        ) : shown.length === 0 ? (
          <div className="ExtensionManager-src-empty">
            <span>{t('src_no_match', { query: this.filter })}</span>
          </div>
        ) : (
          this.groups(shown).map((g) => (
            <div className="ExtensionManager-src-group" key={g.key}>
              <div className="ExtensionManager-src-groupHead">
                <span className={'ExtensionManager-src-logo ExtensionManager-src-logo--sm ExtensionManager-src-logo--' + g.tone}>
                  <i className={g.icon} aria-hidden="true" />
                </span>
                <strong>{g.owner}</strong>
                {g.where ? <span>{g.where}</span> : null}
                <span className="ExtensionManager-src-count">{g.repos.length}</span>
              </div>
              <ul className="ExtensionManager-src-tiles">{g.repos.map((r) => this.repoTile(r, g.owner))}</ul>
            </div>
          ))
        )}

        {this.addRepo(repos)}
      </section>
    );
  }

  /**
   * 🚨 Grouped by owner and host, so "ernestdefoe · github.com" is said once
   * instead of twenty-two times. Most forums keep their private extensions
   * under one account, and the repeated half of every row was the clutter.
   */
  groups(repos: Repo[]) {
    const out: { key: string; owner: string; where: string; icon: string; tone: string; repos: Repo[] }[] = [];

    for (const r of repos) {
      const d = describe(r.url, r.type);
      const slash = r.type === 'vcs' ? d.name.indexOf('/') : -1;
      const owner = slash > 0 ? d.name.slice(0, slash) : s('type_' + r.type);
      const key = owner + '@' + (slash > 0 ? d.where : r.type);
      let g = out.find((x) => x.key === key);

      if (!g) {
        g = { key, owner, where: slash > 0 ? d.where : '', icon: slash > 0 ? d.icon : (TYPES[r.type] || TYPES.vcs).icon, tone: slash > 0 ? d.tone : 'other', repos: [] };
        out.push(g);
      }

      g.repos.push(r);
    }

    return out;
  }

  repoTile(r: Repo, owner: string) {
    const d = describe(r.url, r.type);
    const confirming = this.confirmRepo === r.url;
    const linkable = /^https?:\/\//i.test(r.url);
    const short = d.name.startsWith(owner + '/') ? d.name.slice(owner.length + 1) : d.name;

    return (
      <li className={'ExtensionManager-src-tile' + (confirming ? ' is-confirming' : '')} key={r.url} title={r.url}>
        {confirming ? (
          <div className="ExtensionManager-src-tileConfirm">
            <span>{t('src_remove_named', { name: short })}</span>
            <span>
              <button className="Button Button--sm" onclick={() => (this.confirmRepo = null)}>{t('cancel')}</button>
              <button
                className="Button Button--sm Button--danger"
                disabled={this.saving}
                onclick={() => this.send({ action: 'remove-repository', url: r.url }, () => (this.confirmRepo = null))}
              >
                {t('remove')}
              </button>
            </span>
          </div>
        ) : (
          [
            <span className="ExtensionManager-src-tileName">{short}</span>,
            r.type !== 'vcs' ? <span className="ExtensionManager-src-badge">{t('type_' + r.type)}</span> : null,
            <span className="ExtensionManager-src-tileActions">
              {linkable ? (
                <a className="ExtensionManager-src-icon ExtensionManager-src-icon--sm" href={r.url} target="_blank" rel="noopener noreferrer" title={s('src_open')} aria-label={s('src_open')}>
                  <i className="fas fa-arrow-up-right-from-square" aria-hidden="true" />
                </a>
              ) : null}
              <button
                className="ExtensionManager-src-icon ExtensionManager-src-icon--sm ExtensionManager-src-icon--danger"
                disabled={this.saving}
                title={s('remove')}
                aria-label={s('src_remove_label', { name: d.name })}
                onclick={() => (this.confirmRepo = r.url)}
              >
                <i className="fas fa-trash-can" aria-hidden="true" />
              </button>
            </span>,
          ]
        )}
      </li>
    );
  }

  addRepo(repos: Repo[]) {
    const types: string[] = this.data.types || ['vcs', 'composer', 'path'];
    const url = this.repoUrl.trim();
    const d = url ? describe(url, this.repoType) : null;
    const duplicate = url !== '' && repos.some((r) => r.url.replace(/\/+$/, '') === url.replace(/\/+$/, ''));

    return (
      <form
        className="ExtensionManager-src-add"
        onsubmit={(e: Event) => {
          e.preventDefault();
          if (!url || duplicate) return;
          this.send({ action: 'add-repository', type: this.repoType, url }, () => (this.repoUrl = ''));
        }}
      >
        <div className="ExtensionManager-src-addTitle">{t('src_add_repo')}</div>

        <div className="ExtensionManager-src-choices" role="radiogroup" aria-label={s('src_add_repo')}>
          {types.map((ty) => (
            <button
              type="button"
              role="radio"
              aria-checked={this.repoType === ty ? 'true' : 'false'}
              className={'ExtensionManager-src-choice' + (this.repoType === ty ? ' is-on' : '')}
              onclick={() => (this.repoType = ty)}
            >
              <i className={(TYPES[ty] || TYPES.vcs).icon} aria-hidden="true" />
              <span>
                <strong>{t('type_' + ty)}</strong>
                <small>{t('type_' + ty + '_help')}</small>
              </span>
            </button>
          ))}
        </div>

        <div className="ExtensionManager-src-field">
          <span className={'ExtensionManager-src-fieldIcon' + (d ? ' ExtensionManager-src-logo--' + d.tone : '')}>
            <i className={d ? d.icon : (TYPES[this.repoType] || TYPES.vcs).icon} aria-hidden="true" />
          </span>
          <input
            placeholder={s('type_' + this.repoType + '_placeholder')}
            aria-label={s('type_' + this.repoType + '_placeholder')}
            value={this.repoUrl}
            oninput={(e: any) => (this.repoUrl = e.target.value)}
          />
          <button className="Button Button--primary" type="submit" disabled={this.saving || !url || duplicate}>
            <i className="fas fa-plus" aria-hidden="true" /> {t('add')}
          </button>
        </div>

        {/*
          * Says what will be added before it is, in the same words the list
          * uses — or why it won't be.
          */}
        {duplicate ? (
          <div className="ExtensionManager-src-hint is-warn">{t('src_duplicate')}</div>
        ) : d && d.where ? (
          <div className="ExtensionManager-src-hint">{t('src_will_add', { name: d.name, where: d.where })}</div>
        ) : null}
      </form>
    );
  }

  // ── stability ───────────────────────────────────────────────────────────

  stability() {
    const st = this.data.stability || {};
    const current: string = st.minimumStability || 'stable';
    const levels: string[] = st.levels || LADDER;
    const means = (l: string) => extractText(t('level_' + l.toLowerCase() + '_means'));

    return (
      <section className="ExtensionManager-src-card" id="mw-src-stability">
        <header className="ExtensionManager-src-head">
          <div className="ExtensionManager-src-headIcon"><i className="fas fa-flask" aria-hidden="true" /></div>
          <div className="ExtensionManager-src-headText">
            <h3>{t('stability_title')}</h3>
            <p>{t('stability_help')}</p>
          </div>
        </header>

        <div className="ExtensionManager-src-body">
          {/*
            * 🚨 A ladder, most finished first, because that is what the setting
            * is: everything at and above the chosen step is accepted. A select
            * hid the other four choices and the order between them.
            */}
          <div className="ExtensionManager-src-ladder" role="radiogroup" aria-label={s('stability_title')}>
            {levels.map((l, i) => {
              const on = l === current;
              const accepted = i <= levels.indexOf(current);

              return (
                <button
                  type="button"
                  role="radio"
                  aria-checked={on ? 'true' : 'false'}
                  title={means(l)}
                  disabled={this.saving}
                  className={'ExtensionManager-src-step' + (on ? ' is-on' : accepted ? ' is-accepted' : '')}
                  onclick={() => !on && this.send({ action: 'set-stability', minimumStability: l, preferStable: st.preferStable })}
                >
                  <strong>{t('level_' + l.toLowerCase())}</strong>
                  {l === 'beta' ? <small className="ExtensionManager-src-reco">{t('src_recommended')}</small> : <small>{t('level_' + l.toLowerCase() + '_short')}</small>}
                </button>
              );
            })}
          </div>

          <div className="ExtensionManager-src-callout">
            <i className="fas fa-lightbulb" aria-hidden="true" />
            <span>{means(current)}</span>
          </div>

          <label className="ExtensionManager-src-switch">
            <input
              type="checkbox"
              role="switch"
              checked={!!st.preferStable}
              disabled={this.saving}
              onchange={(e: any) => this.send({ action: 'set-stability', minimumStability: current, preferStable: e.target.checked })}
            />
            <span className="ExtensionManager-src-track" aria-hidden="true"><span /></span>
            <span className="ExtensionManager-src-switchText">
              <strong>{t('prefer_stable')}</strong>
              <small>{t('src_prefer_stable_help')}</small>
            </span>
          </label>
        </div>
      </section>
    );
  }

  // ── credentials ─────────────────────────────────────────────────────────

  credentials() {
    const a = this.data.auth || {};
    const stored: Stored[] = a.stored || [];

    return (
      <section className="ExtensionManager-src-card" id="mw-src-auth">
        <header className="ExtensionManager-src-head">
          <div className="ExtensionManager-src-headIcon"><i className="fas fa-key" aria-hidden="true" /></div>
          <div className="ExtensionManager-src-headText">
            <h3>
              {t('auth_title')} <span className="ExtensionManager-src-count">{stored.length}</span>
            </h3>
            <p>{t('auth_help')}</p>
          </div>
        </header>

        {stored.length === 0 ? (
          <div className="ExtensionManager-src-empty">
            <i className="fas fa-lock-open" aria-hidden="true" />
            <span>{t('auth_none')}</span>
          </div>
        ) : (
          <ul className="ExtensionManager-src-list">{stored.map((c) => this.authRow(c))}</ul>
        )}

        {this.addAuth(a.kinds || Object.keys(KINDS))}

        <div className="ExtensionManager-src-callout ExtensionManager-src-callout--lock">
          <i className="fas fa-shield-halved" aria-hidden="true" />
          <span>{t('auth_writeonly')}</span>
        </div>
      </section>
    );
  }

  authRow(c: Stored) {
    const key = c.kind + '|' + c.host;
    const confirming = this.confirmAuth === key;
    const kind = KINDS[c.kind] || KINDS.bearer;

    return (
      <li className={'ExtensionManager-src-item' + (confirming ? ' is-confirming' : '')} key={key}>
        <span className={'ExtensionManager-src-logo ExtensionManager-src-logo--' + (c.kind.split('-')[0] || 'other')}>
          <i className={kind.icon} aria-hidden="true" />
        </span>
        <span className="ExtensionManager-src-itemText">
          <strong>{c.host}</strong>
          <span>
            <span className="ExtensionManager-src-badge">{t('kind_' + c.kind)}</span>
            <span className="ExtensionManager-src-held">
              <i className="fas fa-lock" aria-hidden="true" /> {c.detail === 'token set' ? t('src_token_stored') : c.detail}
            </span>
          </span>
        </span>

        {confirming ? (
          <span className="ExtensionManager-src-confirm">
            <span>{t('src_remove_q')}</span>
            <button className="Button Button--sm" onclick={() => (this.confirmAuth = null)}>{t('cancel')}</button>
            <button
              className="Button Button--sm Button--danger"
              disabled={this.saving}
              onclick={() => this.send({ action: 'remove-auth', kind: c.kind, host: c.host }, () => (this.confirmAuth = null))}
            >
              {t('remove')}
            </button>
          </span>
        ) : (
          <span className="ExtensionManager-src-actions">
            <button
              className="Button Button--sm"
              disabled={this.saving}
              onclick={() => {
                // Replacing is entering a new value for the same host: the form
                // is filled in and the secret field is where the cursor lands.
                this.authKind = c.kind;
                this.authHost = c.host;
                this.authSecret = '';
                setTimeout(() => (document.getElementById('mw-src-secret') as HTMLInputElement | null)?.focus(), 0);
              }}
            >
              {t('src_replace')}
            </button>
            <button
              className="ExtensionManager-src-icon ExtensionManager-src-icon--danger"
              disabled={this.saving}
              title={s('remove')}
              aria-label={s('src_remove_label', { name: c.host })}
              onclick={() => (this.confirmAuth = key)}
            >
              <i className="fas fa-trash-can" aria-hidden="true" />
            </button>
          </span>
        )}
      </li>
    );
  }

  /**
   * 🚨 Every credential kind links to the page that makes one. A field that asks
   * for a token without saying where tokens come from sends somebody to search
   * GitHub's settings for the right screen and the right scope.
   */
  tokenLink(): { href: string; label: string } | null {
    const host = this.authHost.trim() || KINDS[this.authKind]?.host || '';

    if (this.authKind === 'github-oauth') {
      return {
        href: 'https://github.com/settings/tokens/new?scopes=repo&description=the Extension Manager',
        label: s('src_token_github'),
      };
    }

    if (this.authKind === 'gitlab-token' && /^[a-z0-9.-]+$/i.test(host)) {
      return { href: 'https://' + host + '/-/user_settings/personal_access_tokens', label: s('src_token_gitlab') };
    }

    return null;
  }

  addAuth(kinds: string[]) {
    const basic = this.authKind === 'http-basic';
    const link = this.tokenLink();

    return (
      <form
        className="ExtensionManager-src-add"
        onsubmit={(e: Event) => {
          e.preventDefault();
          this.send(
            { action: 'set-auth', kind: this.authKind, host: this.authHost, username: this.authUser, secret: this.authSecret },
            () => {
              // 🚨 Cleared from memory as soon as it has been sent.
              this.authSecret = '';
              this.authUser = '';
              this.reveal = false;
              this.authHost = KINDS[this.authKind]?.host || '';
            }
          );
        }}
      >
        <div className="ExtensionManager-src-addTitle">{t('src_add_auth')}</div>

        <div className="ExtensionManager-src-choices ExtensionManager-src-choices--four" role="radiogroup" aria-label={s('src_add_auth')}>
          {kinds.map((k) => (
            <button
              type="button"
              role="radio"
              aria-checked={this.authKind === k ? 'true' : 'false'}
              className={'ExtensionManager-src-choice' + (this.authKind === k ? ' is-on' : '')}
              onclick={() => {
                const was = KINDS[this.authKind]?.host || '';
                this.authKind = k;
                // Fill the obvious host, but never overwrite one somebody typed.
                if (!this.authHost.trim() || this.authHost === was) this.authHost = KINDS[k]?.host || '';
              }}
            >
              <i className={(KINDS[k] || KINDS.bearer).icon} aria-hidden="true" />
              <span>
                <strong>{t('kind_' + k)}</strong>
                <small>{t('kind_' + k + '_help')}</small>
              </span>
            </button>
          ))}
        </div>

        <div className={'ExtensionManager-src-grid' + (basic ? ' ExtensionManager-src-grid--three' : '')}>
          <label className="ExtensionManager-src-labelled">
            <span>{t('src_host')}</span>
            <input
              className="FormControl"
              placeholder={s('auth_host_placeholder')}
              value={this.authHost}
              oninput={(e: any) => (this.authHost = e.target.value)}
            />
          </label>

          {basic ? (
            <label className="ExtensionManager-src-labelled">
              <span>{t('src_username')}</span>
              <input
                className="FormControl"
                autocomplete="off"
                placeholder={s('auth_user_placeholder')}
                value={this.authUser}
                oninput={(e: any) => (this.authUser = e.target.value)}
              />
            </label>
          ) : null}

          <label className="ExtensionManager-src-labelled">
            <span>{basic ? t('src_password') : t('src_token')}</span>
            <span className="ExtensionManager-src-secret">
              <input
                id="mw-src-secret"
                className="FormControl"
                type={this.reveal ? 'text' : 'password'}
                autocomplete="new-password"
                spellcheck={false}
                placeholder={s('auth_secret_placeholder')}
                value={this.authSecret}
                oninput={(e: any) => (this.authSecret = e.target.value)}
              />
              <button
                type="button"
                className="ExtensionManager-src-icon"
                aria-label={s(this.reveal ? 'src_hide' : 'src_show')}
                title={s(this.reveal ? 'src_hide' : 'src_show')}
                onclick={() => (this.reveal = !this.reveal)}
              >
                <i className={this.reveal ? 'fas fa-eye-slash' : 'fas fa-eye'} aria-hidden="true" />
              </button>
            </span>
          </label>
        </div>

        <div className="ExtensionManager-src-addFoot">
          {link ? (
            <a className="ExtensionManager-src-link" href={link.href} target="_blank" rel="noopener noreferrer">
              <i className="fas fa-arrow-up-right-from-square" aria-hidden="true" /> {link.label}
            </a>
          ) : (
            <span />
          )}
          <button
            className="Button Button--primary"
            type="submit"
            disabled={this.saving || !this.authHost.trim() || !this.authSecret || (basic && !this.authUser.trim())}
          >
            <i className="fas fa-lock" aria-hidden="true" /> {t('src_save_auth')}
          </button>
        </div>
      </form>
    );
  }

  // ── plumbing ────────────────────────────────────────────────────────────

  load() {
    app
      .request({ method: 'GET', url: apiUrl() + '/extension-manager/config' })
      .then((data: any) => {
        this.data = data;
        this.loading = false;
        m.redraw();
      })
      .catch((e: any) => {
        this.loading = false;
        this.error = e?.response?.error || s('config_failed');
        m.redraw();
      });
  }

  send(body: any, after?: () => void) {
    this.saving = true;
    this.error = null;
    m.redraw();

    app
      .request({ method: 'POST', url: apiUrl() + '/extension-manager/config', body })
      .then((data: any) => {
        this.data = data;
        this.saving = false;
        if (after) after();
        m.redraw();
      })
      .catch((e: any) => {
        /*
         * 🚨 The server's own words. Every refusal from the Config classes is
         * written for the person who typed the value — "that is not a hostname"
         * tells them what to change; "could not save" does not.
         */
        this.saving = false;
        this.error = e?.response?.error || s('config_failed');
        m.redraw();
      });
  }
}
