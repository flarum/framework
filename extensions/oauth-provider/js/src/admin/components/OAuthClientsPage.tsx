import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';

import OAuthClient from '../models/OAuthClient';

const loadClientFormModal = () => import('./ClientFormModal');
const loadNewClientSecretModal = () => import('./NewClientSecretModal');

export default class OAuthClientsPage extends ExtensionPage {
  clients: OAuthClient[] = [];
  loading = true;

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);
    this.loadClients();
  }

  loadClients(): Promise<void> {
    this.loading = true;
    m.redraw();

    return app.store
      .find<OAuthClient[]>('oauth-provider-clients')
      .then((clients) => {
        this.clients = clients;
      })
      .catch(() => {
        this.clients = [];
      })
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }

  content() {
    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          <div className="Form-group">
            <h3>{app.translator.trans('flarum-oauth-provider.admin.clients.heading')}</h3>
            <p className="helpText">{app.translator.trans('flarum-oauth-provider.admin.clients.help')}</p>

            <Button
              className="Button Button--primary"
              icon="fas fa-plus"
              onclick={() => {
                app.modal.show(loadClientFormModal, {
                  onSaved: (client: OAuthClient, plainSecret: string | null) => this.onCreated(client, plainSecret),
                });
              }}
            >
              {app.translator.trans('flarum-oauth-provider.admin.clients.new_button')}
            </Button>
          </div>

          {this.loading ? (
            <LoadingIndicator />
          ) : (
            <div className="Form-group">{this.clients.length === 0 ? this.emptyState() : this.clientsTable()}</div>
          )}
        </div>
      </div>
    );
  }

  emptyState(): Mithril.Children {
    return <p>{app.translator.trans('flarum-oauth-provider.admin.clients.empty')}</p>;
  }

  clientsTable(): Mithril.Children {
    return (
      <ul className="OAuthClientsList">
        {this.clients.map((client) => (
          <li key={client.id()} className={'OAuthClientsList-item' + (client.revoked() ? ' OAuthClientsList-item--revoked' : '')}>
            <div className="OAuthClientsList-main">
              <div className="OAuthClientsList-name">
                {client.name()}
                {client.revoked() ? (
                  <span className="OAuthClientsList-badge OAuthClientsList-badge--revoked">
                    {app.translator.trans('flarum-oauth-provider.admin.clients.status_revoked')}
                  </span>
                ) : null}
              </div>
              <div className="OAuthClientsList-meta">
                <span className="OAuthClientsList-metaLabel">{app.translator.trans('flarum-oauth-provider.admin.clients.id_column')}</span>
                <code className="OAuthClientsList-id" title={client.id()}>
                  {client.id()}
                </code>
              </div>
              <div className="OAuthClientsList-meta">
                <span className="OAuthClientsList-metaLabel">{app.translator.trans('flarum-oauth-provider.admin.clients.redirects_column')}</span>
                <span>{(client.redirectUris() || []).join(', ')}</span>
              </div>
            </div>
            <div className="OAuthClientsList-actions">
              <Button
                className="Button Button--icon"
                icon="fas fa-pencil-alt"
                aria-label={app.translator.trans('flarum-oauth-provider.admin.clients.edit_button')}
                onclick={() => {
                  app.modal.show(loadClientFormModal, {
                    client,
                    onSaved: () => {
                      this.loadClients();
                    },
                  });
                }}
              />
              {client.confidential() ? (
                <Button
                  className="Button Button--icon"
                  icon="fas fa-sync-alt"
                  aria-label={app.translator.trans('flarum-oauth-provider.admin.clients.rotate_button')}
                  onclick={() => this.rotateSecret(client)}
                />
              ) : null}
              <Button
                className="Button Button--icon Button--danger"
                icon="fas fa-trash"
                aria-label={app.translator.trans('flarum-oauth-provider.admin.clients.delete_button')}
                onclick={() => this.deleteClient(client)}
              />
            </div>
          </li>
        ))}
      </ul>
    );
  }

  onCreated(client: OAuthClient, plainSecret: string | null) {
    const clientId = client.id();

    if (clientId && plainSecret) {
      setTimeout(() => {
        app.modal.show(loadNewClientSecretModal, { clientId, clientSecret: plainSecret });
      }, 0);
    }

    this.loadClients();
  }

  deleteClient(client: OAuthClient) {
    if (!confirm(app.translator.trans('flarum-oauth-provider.admin.clients.delete_confirm') as unknown as string)) {
      return;
    }

    client.delete().then(() => this.loadClients());
  }

  rotateSecret(client: OAuthClient) {
    if (!confirm(app.translator.trans('flarum-oauth-provider.admin.clients.rotate_confirm') as unknown as string)) {
      return;
    }

    const clientId = client.id();

    app
      .request<{ data: { attributes: { plainSecret: string | null } } }>({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/oauth-provider-clients/${clientId}/rotate-secret`,
      })
      .then((response) => {
        const plainSecret = response?.data?.attributes?.plainSecret ?? null;

        if (clientId && plainSecret) {
          setTimeout(() => {
            app.modal.show(loadNewClientSecretModal, { clientId, clientSecret: plainSecret, rotated: true });
          }, 0);
        }

        return this.loadClients();
      });
  }
}
