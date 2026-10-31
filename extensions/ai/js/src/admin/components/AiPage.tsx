import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Select from 'flarum/common/components/Select';
import Switch from 'flarum/common/components/Switch';
import type Mithril from 'mithril';
import EditConnectionModal from './EditConnectionModal';
import {
  aiPayload,
  CONNECTIONS_SETTING,
  type Connection,
  type Connections,
  DEFAULT_SETTING,
  driverLabel,
  type FeatureInfo,
  FEATURES_SETTING,
  type FeatureSettings,
  formatTokens,
  MEMBER_DAILY_SETTING,
  parseConnections,
  parseObject,
  serializeConnections,
  withoutKeys,
} from '../connections';

interface UsageRow {
  name: string | null;
  calls: number;
  tokens: number;
}

interface Usage {
  month: string;
  connections: UsageRow[];
  features: UsageRow[];
  members: { id: number; username: string | null; calls: number; tokens: number }[];
  days: { date: string; tokens: number }[];
}

export default class AiPage extends ExtensionPage {
  connections!: Connections;
  features!: Record<string, FeatureSettings>;
  usage: Usage | null = null;
  usageFailed = false;

  oninit(vnode: Mithril.Vnode<any, this>) {
    super.oninit(vnode);

    this.connections = parseConnections(this.setting(CONNECTIONS_SETTING)());
    this.features = parseObject<FeatureSettings>(this.setting(FEATURES_SETTING)());

    // Registered so the page counts it as changed.
    this.setting(MEMBER_DAILY_SETTING);

    this.loadUsage();
  }

  className() {
    return 'AiPage';
  }

  content() {
    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          <Form>
            {this.connectionsField()}
            {this.defaultField()}
            {this.featuresField()}
            {this.limitsField()}
            <div className="Form-group Form-controls">{this.submitButton()}</div>
          </Form>
          {this.usageSection()}
        </div>
      </div>
    );
  }

  connectionsField(): Mithril.Children {
    const locked = aiPayload().lockedConnections;
    const names = Object.keys(this.connections);
    const lockedNames = Object.keys(locked);

    return (
      <div className="Form-group">
        <label>{app.translator.trans('flarum-ai.admin.connections.heading')}</label>
        <div className="helpText">{app.translator.trans('flarum-ai.admin.connections.help')}</div>

        {names.length || lockedNames.length ? (
          <ul className="AiPage-connections">
            {lockedNames.map((name) => this.connectionItem(name, locked[name], true))}
            {names.map((name) => this.connectionItem(name, this.connections[name], false))}
          </ul>
        ) : (
          <p className="AiPage-empty">{app.translator.trans('flarum-ai.admin.connections.empty_text')}</p>
        )}

        <Button className="Button" icon="fas fa-plus" onclick={() => this.edit()}>
          {app.translator.trans('flarum-ai.admin.connections.add_button')}
        </Button>
      </div>
    );
  }

  connectionItem(name: string, connection: Connection, locked: boolean): Mithril.Children {
    // A key typed on the page is only saved once the admin saves the page.
    const keyText =
      connection.key !== undefined && connection.key !== ''
        ? 'flarum-ai.admin.connections.key_new_text'
        : connection.key === undefined && connection.hasKey
          ? 'flarum-ai.admin.connections.key_saved_text'
          : 'flarum-ai.admin.connections.no_key_text';

    return (
      <li key={(locked ? 'locked:' : '') + name} className="AiPage-connection">
        <span className="AiPage-connection-name">{name}</span>
        <span className="AiPage-connection-driver">{driverLabel(connection.driver)}</span>
        {!!connection.model && <code className="AiPage-connection-model">{connection.model}</code>}
        {!!connection.url && <code className="AiPage-connection-url">{connection.url}</code>}
        <span className="AiPage-connection-key">{app.translator.trans(keyText)}</span>
        {!!connection.monthly_tokens && (
          <span className="AiPage-connection-limit">
            {app.translator.trans('flarum-ai.admin.connections.monthly_tokens_text', { tokens: formatTokens(connection.monthly_tokens) })}
          </span>
        )}
        <span className="AiPage-connection-actions">
          {locked ? (
            <span className="AiPage-connection-locked">{app.translator.trans('flarum-ai.admin.connections.locked_text')}</span>
          ) : (
            [
              <Button className="Button Button--link" icon="fas fa-pencil-alt" onclick={() => this.edit(name)}>
                {app.translator.trans('flarum-ai.admin.connections.edit_button')}
              </Button>,
              <Button className="Button Button--link" icon="fas fa-times" onclick={() => this.remove(name)}>
                {app.translator.trans('flarum-ai.admin.connections.remove_button')}
              </Button>,
            ]
          )}
        </span>
      </li>
    );
  }

  defaultField(): Mithril.Children {
    const { lockedDefault } = aiPayload();

    if (lockedDefault) {
      return (
        <div className="Form-group AiPage-default">
          <label>{app.translator.trans('flarum-ai.admin.connections.default_label')}</label>
          <div className="helpText">{app.translator.trans('flarum-ai.admin.connections.default_locked_text', { name: lockedDefault })}</div>
        </div>
      );
    }

    const options: Record<string, Mithril.Children> = { '': app.translator.trans('flarum-ai.admin.connections.default_none_option') };
    this.connectionNames().forEach((name) => (options[name] = name));

    const value = this.setting(DEFAULT_SETTING)();

    return (
      <div className="Form-group AiPage-default">
        <label>{app.translator.trans('flarum-ai.admin.connections.default_label')}</label>
        <div className="helpText">{app.translator.trans('flarum-ai.admin.connections.default_help')}</div>
        <Select options={options} value={value in options ? value : ''} onchange={this.setting(DEFAULT_SETTING)} />
      </div>
    );
  }

  featuresField(): Mithril.Children {
    const features = aiPayload().features;

    return (
      <div className="Form-group">
        <label>{app.translator.trans('flarum-ai.admin.features.heading')}</label>
        <div className="helpText">{app.translator.trans('flarum-ai.admin.features.help')}</div>

        {features.length ? (
          <ul className="AiPage-features">{features.map((feature) => this.featureItem(feature))}</ul>
        ) : (
          <p className="AiPage-empty">{app.translator.trans('flarum-ai.admin.features.empty_text')}</p>
        )}
      </div>
    );
  }

  featureItem(feature: FeatureInfo): Mithril.Children {
    const settings = this.features[feature.key] || {};
    const label = app.translator.trans(feature.label);

    const connections: Record<string, Mithril.Children> = { '': app.translator.trans('flarum-ai.admin.features.connection_default_option') };
    this.connectionNames().forEach((name) => (connections[name] = name));

    const connection = settings.connection && settings.connection in connections ? settings.connection : '';

    return (
      <li key={feature.key} className="AiPage-feature">
        <Switch state={!!settings.enabled} onchange={(enabled: boolean) => this.updateFeature(feature.key, { enabled })}>
          {app.translator.trans('flarum-ai.admin.features.enabled_label', { feature: label })}
        </Switch>
        {!!feature.description && <div className="helpText">{app.translator.trans(feature.description)}</div>}

        <div className="AiPage-feature-options">
          <label>
            {app.translator.trans('flarum-ai.admin.features.connection_label')}
            <Select options={connections} value={connection} onchange={(value: string) => this.updateFeature(feature.key, { connection: value })} />
          </label>
          <label>
            {app.translator.trans('flarum-ai.admin.features.model_label')}
            <input
              className="FormControl"
              value={settings.model || ''}
              placeholder={app.translator.trans('flarum-ai.admin.features.model_placeholder')}
              maxlength={100}
              spellcheck={false}
              oninput={(e: InputEvent) => this.updateFeature(feature.key, { model: (e.target as HTMLInputElement).value })}
            />
          </label>
          <label>
            {app.translator.trans('flarum-ai.admin.features.daily_tokens_label')}
            <input
              className="FormControl"
              type="number"
              min="1"
              step="1"
              value={settings.daily_tokens || ''}
              oninput={(e: InputEvent) =>
                this.updateFeature(feature.key, { daily_tokens: parseInt((e.target as HTMLInputElement).value, 10) || undefined })
              }
            />
          </label>
        </div>
      </li>
    );
  }

  limitsField(): Mithril.Children {
    return (
      <div className="Form-group">
        <label>{app.translator.trans('flarum-ai.admin.limits.heading')}</label>
        <label className="AiPage-subLabel">{app.translator.trans('flarum-ai.admin.limits.member_daily_label')}</label>
        <div className="helpText">{app.translator.trans('flarum-ai.admin.limits.member_daily_help')}</div>
        <input className="FormControl AiPage-tokensInput" type="number" min="1" step="1" bidi={this.setting(MEMBER_DAILY_SETTING)} />
      </div>
    );
  }

  usageSection(): Mithril.Children {
    const usage = this.usage;

    let body: Mithril.Children;

    if (this.usageFailed) {
      body = <p className="AiPage-empty">{app.translator.trans('flarum-ai.admin.usage.load_error_text')}</p>;
    } else if (!usage) {
      body = <LoadingIndicator />;
    } else if (!usage.connections.length) {
      body = <p className="AiPage-empty">{app.translator.trans('flarum-ai.admin.usage.empty_text')}</p>;
    } else {
      const featureLabels = Object.fromEntries(aiPayload().features.map((feature) => [feature.key, app.translator.trans(feature.label)]));

      body = [
        this.daysChart(usage.days),
        <div className="AiPage-usageTables">
          {this.usageTable(
            'flarum-ai.admin.usage.by_connection_heading',
            usage.connections.map((row) => [row.name, row.calls, row.tokens])
          )}
          {this.usageTable(
            'flarum-ai.admin.usage.by_feature_heading',
            usage.features.map((row) => [
              row.name === null ? app.translator.trans('flarum-ai.admin.usage.no_feature_text') : featureLabels[row.name] || row.name,
              row.calls,
              row.tokens,
            ])
          )}
          {!!usage.members.length &&
            this.usageTable(
              'flarum-ai.admin.usage.by_member_heading',
              usage.members.map((row) => [
                row.username ? <a href={app.forum.attribute('baseUrl') + '/u/' + row.username}>{row.username}</a> : '#' + row.id,
                row.calls,
                row.tokens,
              ])
            )}
        </div>,
      ];
    }

    return (
      <div className="AiPage-usage">
        <h3>{app.translator.trans('flarum-ai.admin.usage.heading', { month: usage ? dayjs(usage.month + '-01').format('MMMM YYYY') : '' })}</h3>
        <div className="helpText">{app.translator.trans('flarum-ai.admin.usage.help')}</div>
        {body}
      </div>
    );
  }

  daysChart(days: Usage['days']): Mithril.Children {
    const max = Math.max(1, ...days.map((day) => day.tokens));

    return (
      <div className="AiPage-days">
        <h4>{app.translator.trans('flarum-ai.admin.usage.days_heading')}</h4>
        <div className="AiPage-days-bars">
          {days.map((day) => (
            <span
              key={day.date}
              className="AiPage-days-bar"
              style={{ height: Math.max(2, Math.round((day.tokens / max) * 100)) + '%' }}
              title={`${dayjs(day.date).format('ll')}: ${formatTokens(day.tokens)}`}
            />
          ))}
        </div>
      </div>
    );
  }

  usageTable(heading: string, rows: [Mithril.Children, number, number][]): Mithril.Children {
    return (
      <table className="AiPage-table">
        <caption>{app.translator.trans(heading)}</caption>
        <thead>
          <tr>
            <th>{app.translator.trans('flarum-ai.admin.usage.name_column')}</th>
            <th>{app.translator.trans('flarum-ai.admin.usage.calls_column')}</th>
            <th>{app.translator.trans('flarum-ai.admin.usage.tokens_column')}</th>
          </tr>
        </thead>
        <tbody>
          {rows.map(([name, calls, tokens]) => (
            <tr>
              <td>{name}</td>
              <td>{formatTokens(calls)}</td>
              <td>{formatTokens(tokens)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    );
  }

  loadUsage() {
    app
      .request<Usage>({ method: 'GET', url: app.forum.attribute('apiUrl') + '/ai/usage' })
      .then((usage) => {
        this.usage = usage;
      })
      .catch(() => {
        this.usageFailed = true;
      })
      .finally(() => m.redraw());
  }

  connectionNames(): string[] {
    return [...Object.keys(aiPayload().lockedConnections), ...Object.keys(this.connections)];
  }

  edit(name?: string) {
    app.modal.show(EditConnectionModal, {
      name,
      connection: name ? this.connections[name] : undefined,
      takenNames: this.connectionNames().filter((other) => other !== name),
      onsave: (newName: string, connection: Connection) => {
        this.connections[newName] = connection;
        this.sync();
      },
    });
  }

  remove(name: string) {
    delete this.connections[name];

    if (this.setting(DEFAULT_SETTING)() === name) {
      this.setting(DEFAULT_SETTING)('');
    }

    // Features that used it fall back to the default connection.
    Object.keys(this.features).forEach((key) => {
      if (this.features[key].connection === name) this.updateFeature(key, { connection: '' });
    });

    this.sync();
  }

  updateFeature(key: string, changes: Partial<FeatureSettings>) {
    const settings: FeatureSettings = { ...this.features[key], ...changes };

    (Object.keys(settings) as (keyof FeatureSettings)[]).forEach((field) => {
      if (settings[field] === '' || settings[field] === undefined) delete settings[field];
    });

    this.features[key] = settings;

    // Every registered feature is sent, so each one's settings are saved.
    const features = Object.fromEntries(aiPayload().features.map((feature) => [feature.key, { enabled: false, ...this.features[feature.key] }]));

    this.setting(FEATURES_SETTING)(JSON.stringify(features));
  }

  sync() {
    this.setting(CONNECTIONS_SETTING)(serializeConnections(this.connections));
  }

  onsaved() {
    super.onsaved();

    // Saving copies what was sent, new keys included, into the page's settings.
    // Keep only whether each connection has a key, as the server would send.
    this.connections = withoutKeys(this.connections);

    const saved = JSON.stringify(this.connections);

    app.data.settings[CONNECTIONS_SETTING] = saved;
    this.setting(CONNECTIONS_SETTING)(saved);
  }
}
