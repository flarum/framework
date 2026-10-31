import app from 'flarum/admin/app';
import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import Alert from 'flarum/common/components/Alert';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Link from 'flarum/common/components/Link';
import type Mithril from 'mithril';

export interface McpCapability {
  id: string;
  attributes: {
    kind: 'tool' | 'resource' | 'template' | 'prompt';
    name: string;
    title: string | null;
    description: string | null;
    extension: string;
    extensionTitle: string;
    readOnly: boolean | null;
    destructive: boolean | null;
  };
}

/**
 * How to connect MCP clients to the forum, and what the server offers them.
 * There are no settings: who may connect is the `mcp.use` permission.
 */
export default class McpPage extends ExtensionPage {
  capabilities: McpCapability[] | null = null;
  loadFailed = false;

  oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>) {
    super.oninit(vnode);

    this.loadCapabilities();
  }

  loadCapabilities() {
    app
      .request<{ data: McpCapability[] }>({
        method: 'GET',
        url: app.forum.attribute<string>('apiUrl') + '/mcp-capabilities',
      })
      .then((response) => {
        this.capabilities = response.data;
      })
      .catch(() => {
        this.loadFailed = true;
      })
      .finally(() => m.redraw());
  }

  baseUrl(): string {
    return app.forum.attribute<string>('baseUrl');
  }

  serverUrl(): string {
    return this.baseUrl() + '/mcp';
  }

  metadataUrl(): string {
    return this.baseUrl() + '/.well-known/oauth-protected-resource/mcp';
  }

  content() {
    return (
      <div className="ExtensionPage-settings McpPage">
        <div className="container">
          {this.serverSection()}
          {this.connectSection()}
          {this.capabilitiesSection()}
        </div>
      </div>
    );
  }

  serverSection(): Mithril.Children {
    const basePath = app.forum.attribute<string>('basePath');

    return (
      <div className="Form-group">
        <h3>{app.translator.trans('flarum-mcp.admin.page.server_heading')}</h3>
        <p className="helpText">{app.translator.trans('flarum-mcp.admin.page.server_help')}</p>

        {this.urlField(app.translator.trans('flarum-mcp.admin.page.server_url_label'), this.serverUrl())}
        {this.urlField(app.translator.trans('flarum-mcp.admin.page.metadata_url_label'), this.metadataUrl())}

        {basePath ? this.subPathWarning(basePath) : null}
      </div>
    );
  }

  urlField(label: Mithril.Children, url: string): Mithril.Children {
    return (
      <div className="McpPage-url">
        <label>{label}</label>
        <div className="McpPage-urlRow">
          <code>{url}</code>
          <Button className="Button" icon="fas fa-copy" onclick={() => this.copy(url)}>
            {app.translator.trans('flarum-mcp.admin.page.copy_button')}
          </Button>
        </div>
      </div>
    );
  }

  /**
   * MCP clients look for the metadata documents at the root of the host,
   * where a forum installed under a path cannot answer.
   */
  subPathWarning(basePath: string): Mithril.Children {
    const origin = app.forum.attribute<string>('baseOrigin');
    const escaped = basePath.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    const nginx = [
      `location = /.well-known/oauth-protected-resource${basePath}/mcp {`,
      `    rewrite ^ ${basePath}/.well-known/oauth-protected-resource/mcp last;`,
      `}`,
      `location = /.well-known/oauth-authorization-server${basePath} {`,
      `    rewrite ^ ${basePath}/.well-known/oauth-authorization-server last;`,
      `}`,
    ].join('\n');

    const apache = [
      'RewriteEngine On',
      `RewriteRule ^\\.well-known/oauth-protected-resource${escaped}/mcp$ ${basePath}/.well-known/oauth-protected-resource/mcp [L,PT]`,
      `RewriteRule ^\\.well-known/oauth-authorization-server${escaped}$ ${basePath}/.well-known/oauth-authorization-server [L,PT]`,
    ].join('\n');

    return (
      <Alert type="warning" dismissible={false} className="McpPage-subPath">
        <p>{app.translator.trans('flarum-mcp.admin.page.sub_path_warning', { path: <code>{basePath}</code>, origin: <code>{origin}</code> })}</p>
        <p>{app.translator.trans('flarum-mcp.admin.page.sub_path_nginx')}</p>
        <pre>{nginx}</pre>
        <p>{app.translator.trans('flarum-mcp.admin.page.sub_path_apache')}</p>
        <pre>{apache}</pre>
      </Alert>
    );
  }

  connectSection(): Mithril.Children {
    const user = app.session.user;
    const securityUrl = user ? `${this.baseUrl()}/u/${user.slug()}/security` : this.baseUrl();

    const vscode = JSON.stringify({ servers: { [this.serverName()]: { type: 'http', url: this.serverUrl() } } }, null, 2);
    const token = JSON.stringify(
      { servers: { [this.serverName()]: { type: 'http', url: this.serverUrl(), headers: { Authorization: 'Bearer <developer token>' } } } },
      null,
      2
    );

    return (
      <div className="Form-group">
        <h3>{app.translator.trans('flarum-mcp.admin.page.connect_heading')}</h3>
        <p className="helpText">{app.translator.trans('flarum-mcp.admin.page.connect_help')}</p>

        <dl className="McpPage-clients">
          <dt>{app.translator.trans('flarum-mcp.admin.page.claude_title')}</dt>
          <dd>{app.translator.trans('flarum-mcp.admin.page.claude_steps')}</dd>

          <dt>{app.translator.trans('flarum-mcp.admin.page.chatgpt_title')}</dt>
          <dd>{app.translator.trans('flarum-mcp.admin.page.chatgpt_steps')}</dd>

          <dt>{app.translator.trans('flarum-mcp.admin.page.vscode_title')}</dt>
          <dd>
            {app.translator.trans('flarum-mcp.admin.page.vscode_steps', { file: <code>.vscode/mcp.json</code> })}
            <pre>{vscode}</pre>
          </dd>

          <dt>{app.translator.trans('flarum-mcp.admin.page.token_title')}</dt>
          <dd>
            {app.translator.trans('flarum-mcp.admin.page.token_steps', {
              a: <Link href={securityUrl} external={true} target="_blank" />,
              header: <code>Authorization: Bearer</code>,
            })}
            <pre>{token}</pre>
          </dd>
        </dl>
      </div>
    );
  }

  /**
   * A key for client config files: the forum's host, which is stable and
   * readable where the title may not be.
   */
  serverName(): string {
    try {
      return new URL(this.baseUrl()).hostname;
    } catch {
      return 'flarum';
    }
  }

  capabilitiesSection(): Mithril.Children {
    let body: Mithril.Children;

    if (this.loadFailed) {
      body = <p className="helpText">{app.translator.trans('flarum-mcp.admin.page.capabilities_failed')}</p>;
    } else if (!this.capabilities) {
      body = <LoadingIndicator />;
    } else {
      body = this.capabilitiesTable(this.capabilities);
    }

    return (
      <div className="Form-group">
        <h3>{app.translator.trans('flarum-mcp.admin.page.capabilities_heading')}</h3>
        <p className="helpText">{app.translator.trans('flarum-mcp.admin.page.capabilities_help')}</p>
        {body}
      </div>
    );
  }

  capabilitiesTable(capabilities: McpCapability[]): Mithril.Children {
    return (
      <table className="McpCapabilities">
        <thead>
          <tr>
            <th>{app.translator.trans('flarum-mcp.admin.page.column_name')}</th>
            <th>{app.translator.trans('flarum-mcp.admin.page.column_kind')}</th>
            <th>{app.translator.trans('flarum-mcp.admin.page.column_access')}</th>
            <th>{app.translator.trans('flarum-mcp.admin.page.column_source')}</th>
          </tr>
        </thead>
        <tbody>
          {capabilities.map(({ id, attributes }) => (
            <tr key={id}>
              <td>
                <code>{attributes.name}</code>
                {attributes.title ? <div className="McpCapabilities-title">{attributes.title}</div> : null}
              </td>
              <td>{app.translator.trans(`flarum-mcp.admin.page.kind_${attributes.kind}`)}</td>
              <td>{this.accessLabel(attributes)}</td>
              <td>{attributes.extensionTitle}</td>
            </tr>
          ))}
        </tbody>
      </table>
    );
  }

  accessLabel(attributes: McpCapability['attributes']): Mithril.Children {
    // Resources, templates and prompts only ever read.
    if (attributes.kind !== 'tool' || attributes.readOnly) {
      return app.translator.trans('flarum-mcp.admin.page.access_read');
    }

    return (
      <span className="McpCapabilities-write">
        {app.translator.trans(attributes.destructive ? 'flarum-mcp.admin.page.access_destructive' : 'flarum-mcp.admin.page.access_write')}
      </span>
    );
  }

  copy(text: string) {
    navigator.clipboard.writeText(text).then(
      () => app.alerts.show({ type: 'success' }, app.translator.trans('flarum-mcp.admin.page.copied')),
      () => app.alerts.show({ type: 'error' }, app.translator.trans('flarum-mcp.admin.page.copy_failed'))
    );
  }
}
