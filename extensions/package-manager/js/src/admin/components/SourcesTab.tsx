import app from 'flarum/admin/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Alert from 'flarum/common/components/Alert';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import apiUrl from '../apiUrl';
import { alertError, serverMessage } from '../errors';
import type { ConfigPayload, Repository as Repo, StoredCredential as Stored } from '../types';

/**
 * Where Composer looks, what it will accept, and how it authenticates.
 *
 * These three settings are what a forum needs before Composer can install
 * anything beyond public Packagist: where a private repository lives, that
 * release candidates are acceptable, and how to authenticate to a paid one — a
 * forum whose own extensions live in private repositories cannot resolve a
 * single one without them.
 *
 * A stored credential is never sent to the browser. This screen shows which
 * hosts have one; entering a value replaces it. That is a deliberate limit, not
 * an oversight — a settings page that displays your token back to you turns
 * every screenshot into a leak.
 *
 * The screen reads a source the way a person names it: "ernestdefoe/bespoke on
 * GitHub", not a URL in a monospace column. Every value it shows is still the
 * exact one Composer gets.
 */

/** What a repository URL points at, for the icon and the readable name. */
function describe(url: string, type: string): { icon: string; name: string; where: string; tone: string } {
  if (type === 'path') {
    return { icon: 'fas fa-folder-open', name: url.replace(/\/+$/, '').split('/').pop() || url, where: url, tone: 'path' };
  }

  const match = /^https?:\/\/([^/]+)\/(.+?)(?:\.git)?\/?$/i.exec(url.trim());
  if (!match) return { icon: 'fas fa-box', name: url, where: '', tone: 'other' };

  const host = match[1].toLowerCase();
  const name = type === 'composer' ? host : match[2];

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
  private data: ConfigPayload | null = null;
  /** Why the settings could not be read at all, when they could not. */
  private loadError: string | null = null;

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

  oninit(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    super.oninit(vnode);
    this.load();
  }

  view() {
    if (this.loading) return <LoadingIndicator />;

    const data = this.data;

    if (!data) {
      return (
        <Alert type="error" dismissible={false} className="ExtensionManager-alert">
          {this.loadError || app.translator.trans('flarum-extension-manager.admin.composer.config_failed')}
        </Alert>
      );
    }

    return (
      <div className="ExtensionManager-sources ExtensionManager-src">
        {this.overview(data)}
        {this.repositories(data)}
        {this.stability(data)}
        {this.credentials(data)}
        <p className="ExtensionManager-src-note">
          <i className="fas fa-circle-info" aria-hidden="true" /> {data.note}
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
  overview(data: ConfigPayload) {
    const repos = data.repositories || [];
    const stored = data.auth?.stored || [];
    const level = data.stability?.minimumStability || 'stable';

    const tile = (icon: string, value: Mithril.Children, label: Mithril.Children, target: string) => (
      <a
        className="ExtensionManager-src-stat"
        href={'#' + target}
        onclick={(e: Event) => {
          e.preventDefault();
          document.getElementById(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }}
      >
        <span className="ExtensionManager-src-statIcon">
          <i className={icon} aria-hidden="true" />
        </span>
        <span className="ExtensionManager-src-statText">
          <strong>{value}</strong>
          <span>{label}</span>
        </span>
      </a>
    );

    return (
      <div className="ExtensionManager-src-overview">
        {tile(
          'fas fa-code-branch',
          repos.length,
          app.translator.trans('flarum-extension-manager.admin.composer.repositories.stat', { count: repos.length }),
          'mw-src-repos'
        )}
        {tile(
          'fas fa-flask',
          app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.options.' + level.toLowerCase()),
          app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.stat'),
          'mw-src-stability'
        )}
        {tile(
          'fas fa-key',
          stored.length,
          app.translator.trans('flarum-extension-manager.admin.auth_config.stat', { count: stored.length }),
          'mw-src-auth'
        )}
      </div>
    );
  }

  // ── repositories ────────────────────────────────────────────────────────

  repositories(data: ConfigPayload) {
    const repos = data.repositories || [];
    const q = this.filter.trim().toLowerCase();
    const shown = q ? repos.filter((r) => r.url.toLowerCase().includes(q)) : repos;

    return (
      <section className="ExtensionManager-src-card" id="mw-src-repos">
        <header className="ExtensionManager-src-head">
          <div className="ExtensionManager-src-headIcon">
            <i className="fas fa-code-branch" aria-hidden="true" />
          </div>
          <div className="ExtensionManager-src-headText">
            <h3>
              {app.translator.trans('flarum-extension-manager.admin.composer.repositories.label')}{' '}
              <span className="ExtensionManager-src-count">{repos.length}</span>
            </h3>
            <p>{app.translator.trans('flarum-extension-manager.admin.composer.repositories.help')}</p>
          </div>
          {repos.length > 6 ? (
            <label className="ExtensionManager-src-filter">
              <i className="fas fa-magnifying-glass" aria-hidden="true" />
              <input
                type="search"
                placeholder={app.translator.trans('flarum-extension-manager.admin.composer.repositories.filter', {}, true)}
                aria-label={app.translator.trans('flarum-extension-manager.admin.composer.repositories.filter', {}, true)}
                value={this.filter}
                oninput={(e: InputEvent) => (this.filter = (e.target as HTMLInputElement).value)}
              />
            </label>
          ) : null}
        </header>

        {repos.length === 0 ? (
          <div className="ExtensionManager-src-empty">
            <i className="fas fa-box-open" aria-hidden="true" />
            <span>{app.translator.trans('flarum-extension-manager.admin.composer.repositories.none')}</span>
          </div>
        ) : shown.length === 0 ? (
          <div className="ExtensionManager-src-empty">
            <span>{app.translator.trans('flarum-extension-manager.admin.composer.repositories.no_match', { query: this.filter })}</span>
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

        {this.addRepo(data, repos)}
      </section>
    );
  }

  /**
   * Grouped by owner and host, so "ernestdefoe · github.com" is said once
   * instead of twenty-two times. Most forums keep their private extensions
   * under one account, and the repeated half of every row was the clutter.
   */
  groups(repos: Repo[]) {
    const out: { key: string; owner: string; where: string; icon: string; tone: string; repos: Repo[] }[] = [];

    for (const r of repos) {
      const d = describe(r.url, r.type);
      const slash = r.type === 'vcs' ? d.name.indexOf('/') : -1;
      const owner = slash > 0 ? d.name.slice(0, slash) : this.typeLabel(r.type);
      const key = owner + '@' + (slash > 0 ? d.where : r.type);
      let g = out.find((x) => x.key === key);

      if (!g) {
        g = {
          key,
          owner,
          where: slash > 0 ? d.where : '',
          icon: slash > 0 ? d.icon : (TYPES[r.type] || TYPES.vcs).icon,
          tone: slash > 0 ? d.tone : 'other',
          repos: [],
        };
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
            <span>{app.translator.trans('flarum-extension-manager.admin.composer.repositories.remove_named', { name: short })}</span>
            <span>
              <Button className="Button Button--sm" onclick={() => (this.confirmRepo = null)}>
                {app.translator.trans('flarum-extension-manager.admin.actions.cancel')}
              </Button>
              <Button
                className="Button Button--sm Button--danger"
                disabled={this.saving}
                onclick={() => this.send({ action: 'remove-repository', url: r.url }, () => (this.confirmRepo = null))}
              >
                {app.translator.trans('flarum-extension-manager.admin.actions.remove')}
              </Button>
            </span>
          </div>
        ) : (
          [
            <span className="ExtensionManager-src-tileName">{short}</span>,
            r.type !== 'vcs' ? <span className="ExtensionManager-src-badge">{this.typeLabel(r.type)}</span> : null,
            <span className="ExtensionManager-src-tileActions">
              {linkable ? (
                <a
                  className="ExtensionManager-src-icon ExtensionManager-src-icon--sm"
                  href={r.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  title={app.translator.trans('flarum-extension-manager.admin.composer.repositories.open', {}, true)}
                  aria-label={app.translator.trans('flarum-extension-manager.admin.composer.repositories.open', {}, true)}
                >
                  <i className="fas fa-arrow-up-right-from-square" aria-hidden="true" />
                </a>
              ) : null}
              <button
                className="ExtensionManager-src-icon ExtensionManager-src-icon--sm ExtensionManager-src-icon--danger"
                disabled={this.saving}
                title={app.translator.trans('flarum-extension-manager.admin.actions.remove', {}, true)}
                aria-label={app.translator.trans('flarum-extension-manager.admin.composer.remove_repository_label', { name: d.name }, true)}
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

  /** A repository type's name: "Git repository", "Folder on this server"… */
  typeLabel(type: string): string {
    return app.translator.trans('flarum-extension-manager.admin.composer.repositories.types.' + type, {}, true);
  }

  addRepo(data: ConfigPayload, repos: Repo[]) {
    const types = data.types || ['vcs', 'composer', 'path'];
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
        <div className="ExtensionManager-src-addTitle">{app.translator.trans('flarum-extension-manager.admin.composer.add_repository_label')}</div>

        <div
          className="ExtensionManager-src-choices"
          role="radiogroup"
          aria-label={app.translator.trans('flarum-extension-manager.admin.composer.add_repository_label', {}, true)}
        >
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
                <strong>{this.typeLabel(ty)}</strong>
                <small>{app.translator.trans('flarum-extension-manager.admin.composer.repositories.type_help.' + ty)}</small>
              </span>
            </button>
          ))}
        </div>

        <div className="ExtensionManager-src-field">
          <span className={'ExtensionManager-src-fieldIcon' + (d ? ' ExtensionManager-src-logo--' + d.tone : '')}>
            <i className={d ? d.icon : (TYPES[this.repoType] || TYPES.vcs).icon} aria-hidden="true" />
          </span>
          <input
            placeholder={app.translator.trans('flarum-extension-manager.admin.composer.repositories.placeholders.' + this.repoType, {}, true)}
            aria-label={app.translator.trans('flarum-extension-manager.admin.composer.repositories.placeholders.' + this.repoType, {}, true)}
            value={this.repoUrl}
            oninput={(e: InputEvent) => (this.repoUrl = (e.target as HTMLInputElement).value)}
          />
          <Button className="Button Button--primary" type="submit" icon="fas fa-plus" disabled={this.saving || !url || duplicate}>
            {app.translator.trans('flarum-extension-manager.admin.actions.add')}
          </Button>
        </div>

        {/*
         * Says what will be added before it is, in the same words the list
         * uses — or why it won't be.
         */}
        {duplicate ? (
          <div className="ExtensionManager-src-hint is-warn">
            {app.translator.trans('flarum-extension-manager.admin.composer.repositories.duplicate')}
          </div>
        ) : d && d.where ? (
          <div className="ExtensionManager-src-hint">
            {app.translator.trans('flarum-extension-manager.admin.composer.repositories.will_add', { name: d.name, where: d.where })}
          </div>
        ) : null}
      </form>
    );
  }

  // ── stability ───────────────────────────────────────────────────────────

  stability(data: ConfigPayload) {
    const st = data.stability || { minimumStability: 'stable', preferStable: false };
    const current = st.minimumStability || 'stable';
    const levels = st.levels || LADDER;
    const means = (l: string) => app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.means.' + l.toLowerCase(), {}, true);

    return (
      <section className="ExtensionManager-src-card" id="mw-src-stability">
        <header className="ExtensionManager-src-head">
          <div className="ExtensionManager-src-headIcon">
            <i className="fas fa-flask" aria-hidden="true" />
          </div>
          <div className="ExtensionManager-src-headText">
            <h3>{app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.label')}</h3>
            <p>{app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.help')}</p>
          </div>
        </header>

        <div className="ExtensionManager-src-body">
          {/*
           * A ladder, most finished first, because that is what the setting
           * is: everything at and above the chosen step is accepted. A select
           * hid the other four choices and the order between them.
           */}
          <div
            className="ExtensionManager-src-ladder"
            role="radiogroup"
            aria-label={app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.label', {}, true)}
          >
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
                  <strong>{app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.options.' + l.toLowerCase())}</strong>
                  {l === 'beta' ? (
                    <small className="ExtensionManager-src-reco">
                      {app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.recommended')}
                    </small>
                  ) : (
                    <small>{app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.short.' + l.toLowerCase())}</small>
                  )}
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
              onchange={(e: Event) =>
                this.send({ action: 'set-stability', minimumStability: current, preferStable: (e.target as HTMLInputElement).checked })
              }
            />
            <span className="ExtensionManager-src-track" aria-hidden="true">
              <span />
            </span>
            <span className="ExtensionManager-src-switchText">
              <strong>{app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.prefer_stable')}</strong>
              <small>{app.translator.trans('flarum-extension-manager.admin.composer.minimum_stability.prefer_stable_help')}</small>
            </span>
          </label>
        </div>
      </section>
    );
  }

  // ── credentials ─────────────────────────────────────────────────────────

  credentials(data: ConfigPayload) {
    const a = data.auth || { stored: [] };
    const stored = a.stored || [];

    return (
      <section className="ExtensionManager-src-card" id="mw-src-auth">
        <header className="ExtensionManager-src-head">
          <div className="ExtensionManager-src-headIcon">
            <i className="fas fa-key" aria-hidden="true" />
          </div>
          <div className="ExtensionManager-src-headText">
            <h3>
              {app.translator.trans('flarum-extension-manager.admin.auth_config.title')}{' '}
              <span className="ExtensionManager-src-count">{stored.length}</span>
            </h3>
            <p>{app.translator.trans('flarum-extension-manager.admin.auth_config.help')}</p>
          </div>
        </header>

        {stored.length === 0 ? (
          <div className="ExtensionManager-src-empty">
            <i className="fas fa-lock-open" aria-hidden="true" />
            <span>{app.translator.trans('flarum-extension-manager.admin.auth_config.no_auth_methods_configured')}</span>
          </div>
        ) : (
          <ul className="ExtensionManager-src-list">{stored.map((c) => this.authRow(c))}</ul>
        )}

        {this.addAuth(a.kinds || Object.keys(KINDS))}

        <div className="ExtensionManager-src-callout ExtensionManager-src-callout--lock">
          <i className="fas fa-shield-halved" aria-hidden="true" />
          <span>{app.translator.trans('flarum-extension-manager.admin.auth_config.write_only')}</span>
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
            <span className="ExtensionManager-src-badge">{app.translator.trans('flarum-extension-manager.admin.auth_config.types.' + c.kind)}</span>
            <span className="ExtensionManager-src-held">
              <i className="fas fa-lock" aria-hidden="true" />{' '}
              {c.detail === 'token set' ? app.translator.trans('flarum-extension-manager.admin.auth_config.token_stored') : c.detail}
            </span>
          </span>
        </span>

        {confirming ? (
          <span className="ExtensionManager-src-confirm">
            <span>{app.translator.trans('flarum-extension-manager.admin.auth_config.remove_q')}</span>
            <Button className="Button Button--sm" onclick={() => (this.confirmAuth = null)}>
              {app.translator.trans('flarum-extension-manager.admin.actions.cancel')}
            </Button>
            <Button
              className="Button Button--sm Button--danger"
              disabled={this.saving}
              onclick={() => this.send({ action: 'remove-auth', kind: c.kind, host: c.host }, () => (this.confirmAuth = null))}
            >
              {app.translator.trans('flarum-extension-manager.admin.actions.remove')}
            </Button>
          </span>
        ) : (
          <span className="ExtensionManager-src-actions">
            <Button
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
              {app.translator.trans('flarum-extension-manager.admin.auth_config.edit_label')}
            </Button>
            <button
              className="ExtensionManager-src-icon ExtensionManager-src-icon--danger"
              disabled={this.saving}
              title={app.translator.trans('flarum-extension-manager.admin.actions.remove', {}, true)}
              aria-label={app.translator.trans('flarum-extension-manager.admin.auth_config.remove_host_label', { name: c.host }, true)}
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
   * Every credential kind links to the page that makes one. A field that asks
   * for a token without saying where tokens come from sends somebody to search
   * GitHub's settings for the right screen and the right scope.
   */
  tokenLink(): { href: string; label: string } | null {
    const host = this.authHost.trim() || KINDS[this.authKind]?.host || '';

    if (this.authKind === 'github-oauth') {
      // The note GitHub keeps beside the token, so its owner can tell later what it is for.
      const note = app.translator.trans(
        'flarum-extension-manager.admin.auth_config.github_token_note',
        { forum: app.forum.attribute<string>('title') },
        true
      );

      return {
        href: 'https://github.com/settings/tokens/new?scopes=repo&description=' + encodeURIComponent(note),
        label: app.translator.trans('flarum-extension-manager.admin.auth_config.create_github_token', {}, true),
      };
    }

    if (this.authKind === 'gitlab-token' && /^[a-z0-9.-]+$/i.test(host)) {
      return {
        href: 'https://' + host + '/-/user_settings/personal_access_tokens',
        label: app.translator.trans('flarum-extension-manager.admin.auth_config.create_gitlab_token', {}, true),
      };
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
          this.send({ action: 'set-auth', kind: this.authKind, host: this.authHost, username: this.authUser, secret: this.authSecret }, () => {
            // Cleared from memory as soon as it has been sent.
            this.authSecret = '';
            this.authUser = '';
            this.reveal = false;
            this.authHost = KINDS[this.authKind]?.host || '';
          });
        }}
      >
        <div className="ExtensionManager-src-addTitle">{app.translator.trans('flarum-extension-manager.admin.auth_config.add_label')}</div>

        <div
          className="ExtensionManager-src-choices ExtensionManager-src-choices--four"
          role="radiogroup"
          aria-label={app.translator.trans('flarum-extension-manager.admin.auth_config.add_label', {}, true)}
        >
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
                <strong>{app.translator.trans('flarum-extension-manager.admin.auth_config.types.' + k)}</strong>
                <small>{app.translator.trans('flarum-extension-manager.admin.auth_config.type_help.' + k)}</small>
              </span>
            </button>
          ))}
        </div>

        <div className={'ExtensionManager-src-grid' + (basic ? ' ExtensionManager-src-grid--three' : '')}>
          <label className="ExtensionManager-src-labelled">
            <span>{app.translator.trans('flarum-extension-manager.admin.auth_config.fields.host')}</span>
            <input
              className="FormControl"
              placeholder={app.translator.trans('flarum-extension-manager.admin.auth_config.add_modal.host_placeholder', {}, true)}
              value={this.authHost}
              oninput={(e: InputEvent) => (this.authHost = (e.target as HTMLInputElement).value)}
            />
          </label>

          {basic ? (
            <label className="ExtensionManager-src-labelled">
              <span>{app.translator.trans('flarum-extension-manager.admin.auth_config.fields.username')}</span>
              <input
                className="FormControl"
                autocomplete="off"
                placeholder={app.translator.trans('flarum-extension-manager.admin.auth_config.add_modal.username_placeholder', {}, true)}
                value={this.authUser}
                oninput={(e: InputEvent) => (this.authUser = (e.target as HTMLInputElement).value)}
              />
            </label>
          ) : null}

          <label className="ExtensionManager-src-labelled">
            <span>
              {basic
                ? app.translator.trans('flarum-extension-manager.admin.auth_config.fields.password')
                : app.translator.trans('flarum-extension-manager.admin.auth_config.fields.token')}
            </span>
            <span className="ExtensionManager-src-secret">
              <input
                id="mw-src-secret"
                className="FormControl"
                type={this.reveal ? 'text' : 'password'}
                autocomplete="new-password"
                spellcheck={false}
                placeholder={app.translator.trans('flarum-extension-manager.admin.auth_config.add_modal.secret_placeholder', {}, true)}
                value={this.authSecret}
                oninput={(e: InputEvent) => (this.authSecret = (e.target as HTMLInputElement).value)}
              />
              <button
                type="button"
                className="ExtensionManager-src-icon"
                aria-label={this.revealLabel()}
                title={this.revealLabel()}
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
          <Button
            className="Button Button--primary"
            type="submit"
            icon="fas fa-lock"
            disabled={this.saving || !this.authHost.trim() || !this.authSecret || (basic && !this.authUser.trim())}
          >
            {app.translator.trans('flarum-extension-manager.admin.auth_config.submit_button')}
          </Button>
        </div>
      </form>
    );
  }

  revealLabel(): string {
    return this.reveal
      ? app.translator.trans('flarum-extension-manager.admin.auth_config.hide', {}, true)
      : app.translator.trans('flarum-extension-manager.admin.auth_config.show', {}, true);
  }

  // ── plumbing ────────────────────────────────────────────────────────────

  load() {
    app
      .request<ConfigPayload>({ method: 'GET', url: apiUrl() + '/extension-manager/config', errorHandler: () => {} })
      .then((data) => {
        this.data = data;
        this.loading = false;
        m.redraw();
      })
      .catch((e) => {
        this.loading = false;
        this.loadError = serverMessage(e);
        m.redraw();
      });
  }

  send(body: Record<string, unknown>, after?: () => void) {
    this.saving = true;
    m.redraw();

    app
      .request<ConfigPayload>({
        method: 'POST',
        url: apiUrl() + '/extension-manager/config',
        body,
        /*
         * The server's own words. Every refusal from the Config classes is
         * written for the person who typed the value — "that is not a hostname"
         * tells them what to change; "could not save" does not.
         */
        errorHandler: alertError('flarum-extension-manager.admin.composer.config_failed'),
      })
      .then((data) => {
        this.data = data;
        this.saving = false;
        if (after) after();
        m.redraw();
      })
      .catch(() => {
        this.saving = false;
        m.redraw();
      });
  }
}
