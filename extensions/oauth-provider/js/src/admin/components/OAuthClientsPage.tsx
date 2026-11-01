import app from 'flarum/admin/app';
import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import Alert from 'flarum/common/components/Alert';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type { TabItem } from 'flarum/common/components/Tabs';
import humanTime from 'flarum/common/helpers/humanTime';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

import OAuthClient from '../models/OAuthClient';

const loadClientFormModal = () => import('./ClientFormModal');
const loadNewClientSecretModal = () => import('./NewClientSecretModal');

/**
 * Applications an admin added, and the ones that registered themselves
 * (dynamic registration or a metadata document), such as MCP clients.
 */
type ClientKind = 'applications' | 'self_registered';

const KIND_SETTINGS: Record<ClientKind, string> = {
  applications: 'flarum-oauth-provider.allow_admin_clients',
  self_registered: 'flarum-oauth-provider.allow_self_registered_clients',
};

export default class OAuthClientsPage extends ExtensionPage {
  clients: OAuthClient[] = [];
  loading = true;

  oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>) {
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

  /**
   * The Applications and Self-registered tabs first, then the registered
   * Settings tab, then core's Permissions tab.
   */
  pageTabItems(): ItemList<TabItem> {
    const items = super.pageTabItems();

    items.add(
      'applications',
      { id: 'applications', label: app.translator.trans('flarum-oauth-provider.admin.tabs.applications'), icon: 'fas fa-plug' },
      100
    );
    items.add(
      'self_registered',
      { id: 'self_registered', label: app.translator.trans('flarum-oauth-provider.admin.tabs.self_registered'), icon: 'fas fa-robot' },
      90
    );

    return items;
  }

  pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children {
    if (tab === 'applications' || tab === 'self_registered') return this.kindContent(tab);

    return super.pageTabContent(tab, vnode);
  }

  /**
   * Whether clients of a kind may be used, as last saved. Both default on.
   */
  allows(kind: ClientKind): boolean {
    const value = app.data.settings[KIND_SETTINGS[kind]] as unknown as string | boolean | null | undefined;

    return value === undefined || value === null || value === true || value === '1';
  }

  clientsOf(kind: ClientKind): OAuthClient[] {
    return this.clients.filter((client) => (client.source() === 'admin') === (kind === 'applications'));
  }

  /**
   * One kind of client, and for applications the button to add one.
   */
  kindContent(kind: ClientKind) {
    const allowed = this.allows(kind);
    const clients = this.clientsOf(kind);

    return (
      <div className="OAuthClientsPage-clients">
        <div className="Form-group">
          <h3>{app.translator.trans(`flarum-oauth-provider.admin.clients.${kind}.heading`)}</h3>
          <p className="helpText">{app.translator.trans(`flarum-oauth-provider.admin.clients.${kind}.help`)}</p>

          {!allowed && (
            <Alert type="warning" dismissible={false} className="OAuthClientsPage-off">
              {app.translator.trans(`flarum-oauth-provider.admin.clients.${kind}.off`)}
            </Alert>
          )}

          {kind === 'applications' && allowed && (
            <Button
              className="Button Button--primary OAuthClientsPage-add"
              icon="fas fa-plus"
              onclick={() => {
                app.modal.show(loadClientFormModal, {
                  onSaved: (client: OAuthClient, plainSecret: string | null) => this.onCreated(client, plainSecret),
                });
              }}
            >
              {app.translator.trans('flarum-oauth-provider.admin.clients.new_button')}
            </Button>
          )}
        </div>

        {this.loading ? (
          <LoadingIndicator />
        ) : (
          <div className="Form-group">{clients.length === 0 ? this.emptyState(kind) : this.clientsTable(clients)}</div>
        )}
      </div>
    );
  }

  emptyState(kind: ClientKind): Mithril.Children {
    return <p>{app.translator.trans(`flarum-oauth-provider.admin.clients.${kind}.empty`)}</p>;
  }

  clientsTable(clients: OAuthClient[]): Mithril.Children {
    return (
      <ul className="OAuthClientsList">
        {clients.map((client) => (
          <li key={client.id()} className={'OAuthClientsList-item' + (client.revoked() ? ' OAuthClientsList-item--revoked' : '')}>
            <div className="OAuthClientsList-main">
              <div className="OAuthClientsList-name">
                {client.name()}
                {client.source() && client.source() !== 'admin' ? (
                  <span className="OAuthClientsList-badge">
                    {app.translator.trans(`flarum-oauth-provider.admin.settings.source_${client.source()}`)}
                  </span>
                ) : null}
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
              {this.usage(client)}
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

  /**
   * How many members connected the client, linking to them in the users
   * list, and when it was last used.
   */
  usage(client: OAuthClient): Mithril.Children {
    const count = client.connectionCount() || 0;
    const lastUsedAt = client.lastUsedAt();

    return (
      <div className="OAuthClientsList-usage">
        <span className="OAuthClientsList-connections">
          {count > 0 ? (
            <Link href={app.route('users', { q: 'connected:' + client.id() })}>
              {app.translator.trans('flarum-oauth-provider.admin.clients.connections', { count })}
            </Link>
          ) : (
            app.translator.trans('flarum-oauth-provider.admin.clients.connections_none')
          )}
        </span>
        <span className="OAuthClientsList-lastUsed">
          {lastUsedAt
            ? app.translator.trans('flarum-oauth-provider.admin.clients.last_used', { ago: humanTime(lastUsedAt) })
            : app.translator.trans('flarum-oauth-provider.admin.clients.never_used')}
        </span>
      </div>
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
