import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import IPAddress from 'flarum/common/components/IPAddress';
import LabelValue from 'flarum/common/components/LabelValue';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import humanTime from 'flarum/common/helpers/humanTime';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';

import type OAuthConsent from '../models/OAuthConsent';

/**
 * Lists the OAuth clients the current user has granted access to, as their
 * browser sessions are listed, with a button to revoke each grant.
 */
export default class ConnectedApplications extends Component {
  consents: OAuthConsent[] | null = null;
  revoking: Record<string, boolean> = {};
  revokingAll = false;

  oninit(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    super.oninit(vnode);

    app.store
      .find<OAuthConsent[]>('oauth-consents')
      .then((consents) => (this.consents = consents))
      .catch(() => (this.consents = []))
      .finally(() => m.redraw());
  }

  view() {
    if (this.consents === null) return <LoadingIndicator display="block" />;

    return [
      <div className="AccessTokensList ConnectedApplications">
        {this.consents.length ? (
          this.consents.map((consent) => this.item(consent))
        ) : (
          <div className="AccessTokensList--empty">{app.translator.trans('flarum-oauth-provider.forum.connected_apps.empty')}</div>
        )}
      </div>,
      this.consents.length > 1 && (
        <div className="UserSecurityPage-controls">
          <Button className="Button ConnectedApplications-revokeAll" loading={this.revokingAll} onclick={() => this.revokeAll()}>
            {app.translator.trans('flarum-oauth-provider.forum.connected_apps.revoke_all_button')}
          </Button>
        </div>
      ),
    ];
  }

  item(consent: OAuthConsent): Mithril.Children {
    const host = consent.clientSourceHost();
    const logo = consent.clientLogoUrl();
    const lastUsed = consent.lastUsedAt();

    return (
      <div className="AccessTokensList-item ConnectedApplications-item" key={consent.id()}>
        <div className="AccessTokensList-item-icon">
          {logo ? <img className="ConnectedApplications-logo" src={logo} alt="" /> : <Icon name="fas fa-plug" />}
        </div>
        <div className="AccessTokensList-item-info">
          <div className="AccessTokensList-item-title">
            <span className="AccessTokensList-item-title-main">{consent.clientName()}</span>
            {host
              ? [
                  ' — ',
                  <span className="AccessTokensList-item-title-sub">
                    {app.translator.trans('flarum-oauth-provider.forum.connected_apps.from_host', { host })}
                  </span>,
                ]
              : null}
          </div>
          <div className="AccessTokensList-item-createdAt">
            <LabelValue
              label={app.translator.trans('flarum-oauth-provider.forum.connected_apps.connected')}
              value={humanTime(consent.createdAt()!)}
            />
          </div>
          <div className="AccessTokensList-item-lastActivityAt">
            <LabelValue
              label={app.translator.trans('flarum-oauth-provider.forum.connected_apps.last_used')}
              value={
                lastUsed ? (
                  <>
                    {humanTime(lastUsed)}
                    {consent.lastIpAddress() && (
                      <span>
                        {' '}
                        — <IPAddress ip={consent.lastIpAddress()} />
                      </span>
                    )}
                  </>
                ) : (
                  app.translator.trans('flarum-oauth-provider.forum.connected_apps.never_used')
                )
              }
            />
          </div>
          <div className="ConnectedApplications-scopes">
            {(consent.scopes() || []).map((scope) => (
              <code>{scope}</code>
            ))}
          </div>
        </div>
        <div className="AccessTokensList-item-actions">
          <Button className="Button Button--danger" loading={!!this.revoking[consent.id()!]} onclick={() => this.revoke(consent)}>
            {app.translator.trans('flarum-oauth-provider.forum.connected_apps.revoke_button')}
          </Button>
        </div>
      </div>
    );
  }

  async revoke(consent: OAuthConsent) {
    const message = extractText(app.translator.trans('flarum-oauth-provider.forum.connected_apps.revoke_confirm', { client: consent.clientName() }));

    if (!confirm(message)) return;

    this.revoking[consent.id()!] = true;
    m.redraw();

    try {
      await consent.delete();

      this.consents = (this.consents || []).filter((other) => other !== consent);
      app.alerts.show(
        { type: 'success' },
        app.translator.trans('flarum-oauth-provider.forum.connected_apps.revoked', { client: consent.clientName() })
      );
    } finally {
      this.revoking[consent.id()!] = false;
      m.redraw();
    }
  }

  async revokeAll() {
    if (!confirm(extractText(app.translator.trans('flarum-oauth-provider.forum.connected_apps.revoke_all_confirm')))) return;

    this.revokingAll = true;
    m.redraw();

    try {
      await app.request({ method: 'DELETE', url: app.forum.attribute('apiUrl') + '/oauth-consents' });

      (this.consents || []).forEach((consent) => app.store.remove(consent));
      this.consents = [];
      app.alerts.show({ type: 'success' }, app.translator.trans('flarum-oauth-provider.forum.connected_apps.revoked_all'));
    } finally {
      this.revokingAll = false;
      m.redraw();
    }
  }
}
