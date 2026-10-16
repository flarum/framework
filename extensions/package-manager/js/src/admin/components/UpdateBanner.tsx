import app from 'flarum/admin/app';
import t from '../t';
import apiUrl from '../apiUrl';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LinkButton from 'flarum/common/components/LinkButton';

declare const m: any;


interface Pending {
  package: string;
  name: string;
  from: string | null;
  to: string | null;
}

/**
 * Tells an admin, on the page they already land on, that something is out of
 * date — without overstating what that means.
 *
 * 🚨 The wording matters more than the banner. "3 updates available" is a claim
 * this cannot back: the cheap Packagist check knows a newer version EXISTS, not
 * that it will install alongside everything else on the forum. So it says a
 * newer version exists and offers to go and find out properly, which is a
 * sentence that stays true.
 *
 * 🚨 It also carries its own age. A badge with no timestamp is indistinguishable
 * from a stale one, and a stale badge is how people learn to ignore badges.
 */
export default class UpdateBanner extends Component {
  loading = true;
  count = 0;
  /**
   * 🚨 WHICH packages, not just how many.
   *
   * The banner used to say "1 package has a newer version" and stop there, so
   * the only way to find out which one was to open the page and read a grid of
   * every extension on the forum looking for a tag. A notification that does
   * not name its subject makes the reader do the work it exists to save.
   */
  pending: Pending[] = [];
  checkedAt: number | null = null;
  stale = false;
  uncheckable = 0;
  dismissed = false;

  oninit(vnode: any) {
    super.oninit(vnode);

    app
      .request({ method: 'GET', url: apiUrl() + '/extension-manager/state' })
      .then((data: any) => {
        const updates = data.updates || {};
        const available = updates.available || {};

        /*
         * The display name lives on `installed`, which this response already
         * carries — the update map is keyed by composer package, and
         * "huseyinfiliz/discussion-ban" is not what the extension is called on
         * the Extensions page. Fall back to the package when the two cannot be
         * matched, which is better than saying nothing.
         */
        const installed: any[] = data.installed || [];

        this.pending = Object.keys(available).map((pkg) => ({
          package: pkg,
          name: installed.find((i) => i.package === pkg)?.name || pkg,
          from: available[pkg]?.from ?? null,
          to: available[pkg]?.to ?? null,
        }));

        this.count = this.pending.length;
        this.checkedAt = updates.checkedAt ?? null;
        this.stale = !!updates.stale;
        this.uncheckable = (updates.uncheckable || []).length;
        this.dismissed = this.wasDismissed(Object.keys(updates.available || {}));
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        // A dashboard is not the place to report that a background check could
        // not run. The Extension Manager page says so properly.
        this.loading = false;
        m.redraw();
      });
  }

  view() {
    if (this.loading || this.count === 0 || this.dismissed) return null;

    return (
      <div className="ExtensionManager-banner">
        <div>
          <div className="ExtensionManager-banner-title">{t('banner_title', { count: this.count })}</div>

          {this.names()}

          <div className="ExtensionManager-banner-body">
            {t('banner_body')}
            {this.checkedAt ? ' ' + t('banner_checked', { when: this.ago(this.checkedAt) }) : ''}
            {this.uncheckable > 0 ? ' ' + t('banner_uncheckable', { count: this.uncheckable }) : ''}
          </div>
        </div>
        <div className="ExtensionManager-banner-actions">
          {LinkButton.component(
            { className: 'Button Button--primary', href: app.route('extension', { id: 'flarum-extension-manager', tab: 'installed' }) },
            t('banner_open')
          )}
          {Button.component({ className: 'Button Button--link', onclick: () => this.dismiss() }, t('banner_dismiss'))}
        </div>
      </div>
    );
  }

  /**
   * The extensions themselves, with the versions involved.
   *
   * 🚨 Capped, and honest about the cap. A forum with thirty out-of-date
   * packages would otherwise turn a banner into a wall, and the reader who has
   * thirty does not need them enumerated — they need to open the page.
   */
  names() {
    const shown = this.pending.slice(0, 4);
    const rest = this.pending.length - shown.length;

    return (
      <ul className="ExtensionManager-banner-list">
        {shown.map((p) => (
          <li key={p.package}>
            <span className="ExtensionManager-banner-name">{p.name}</span>
            {p.from && p.to ? (
              <span className="ExtensionManager-banner-vers">
                {p.from} → {p.to}
              </span>
            ) : null}
          </li>
        ))}
        {rest > 0 ? (
          <li className="ExtensionManager-banner-more">{t('banner_more', { count: rest })}</li>
        ) : null}
      </ul>
    );
  }

  /**
   * 🚨 Dismissal is remembered against WHICH packages were out of date, not as a
   * flag. Dismissing "3 updates" and then never hearing about a fourth is how a
   * notification quietly stops working.
   */
  private key(packages: string[]): string {
    return 'extension-manager.dismissed.' + packages.sort().join(',');
  }

  private wasDismissed(packages: string[]): boolean {
    try {
      return localStorage.getItem(this.key(packages)) === '1';
    } catch {
      return false;
    }
  }

  private dismiss() {
    this.dismissed = true;

    app
      .request({ method: 'GET', url: apiUrl() + '/extension-manager/state' })
      .then((data: any) => {
        try {
          localStorage.setItem(this.key(Object.keys(data.updates?.available || {})), '1');
        } catch {
          // Private browsing. It reappears next load, which is the safe way round.
        }
      })
      .catch(() => {});

    m.redraw();
  }

  private ago(unix: number): string {
    const mins = Math.max(1, Math.round((Date.now() / 1000 - unix) / 60));

    if (mins < 60) return t('ago_minutes', { count: mins });

    const hours = Math.round(mins / 60);

    return hours < 48
      ? t('ago_hours', { count: hours })
      : t('ago_days', { count: Math.round(hours / 24) });
  }
}
