import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import humanTime from 'flarum/common/helpers/humanTime';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';

import type OAuthConsent from '../models/OAuthConsent';

/**
 * Lists the OAuth clients the current user has granted access to, with a
 * button to revoke each grant.
 */
export default class ConnectedApplications extends Component {
  consents: OAuthConsent[] | null = null;

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

    if (this.consents.length === 0) {
      return <p className="helpText">{app.translator.trans('flarum-oauth-provider.forum.connected_apps.empty')}</p>;
    }

    return <ul className="ConnectedApplications">{this.consents.map((consent) => this.item(consent))}</ul>;
  }

  item(consent: OAuthConsent): Mithril.Children {
    const host = consent.clientSourceHost();
    const lastUsed = consent.lastUsedAt();

    return (
      <li key={consent.id()} className="ConnectedApplications-item">
        {consent.clientLogoUrl() ? <img className="ConnectedApplications-logo" src={consent.clientLogoUrl()!} alt="" /> : null}
        <div className="ConnectedApplications-info">
          <strong className="ConnectedApplications-name">{consent.clientName()}</strong>
          {host ? (
            <span className="ConnectedApplications-host">
              {app.translator.trans('flarum-oauth-provider.forum.connected_apps.from_host', { host })}
            </span>
          ) : null}
          <div className="ConnectedApplications-scopes">
            {(consent.scopes() || []).map((scope) => (
              <code>{scope}</code>
            ))}
          </div>
          <div className="ConnectedApplications-meta helpText">
            {app.translator.trans('flarum-oauth-provider.forum.connected_apps.granted', { ago: humanTime(consent.createdAt()!) })}
            {' · '}
            {lastUsed
              ? app.translator.trans('flarum-oauth-provider.forum.connected_apps.last_used', { ago: humanTime(lastUsed) })
              : app.translator.trans('flarum-oauth-provider.forum.connected_apps.never_used')}
          </div>
        </div>
        <Button className="Button Button--danger" onclick={() => this.revoke(consent)}>
          {app.translator.trans('flarum-oauth-provider.forum.connected_apps.revoke_button')}
        </Button>
      </li>
    );
  }

  revoke(consent: OAuthConsent) {
    const message = extractText(app.translator.trans('flarum-oauth-provider.forum.connected_apps.revoke_confirm', { client: consent.clientName() }));

    if (!confirm(message)) return;

    consent.delete().then(() => {
      this.consents = (this.consents || []).filter((other) => other !== consent);
      m.redraw();
    });
  }
}
