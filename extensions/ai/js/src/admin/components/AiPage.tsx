import app from 'flarum/admin/app';
import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import SettingsSection from 'flarum/admin/components/SettingsSection';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Select from 'flarum/common/components/Select';
import Switch from 'flarum/common/components/Switch';
import type { TabItem } from 'flarum/common/components/Tabs';
import extractText from 'flarum/common/utils/extractText';
import type ItemList from 'flarum/common/utils/ItemList';
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
  GUEST_DAILY_SETTING,
  MEMBER_DAILY_SETTING,
  parseConnections,
  parseObject,
  parseTokens,
  serializeConnections,
  withoutKeys,
} from '../utils/settings';

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

/**
 * The tab each of the page's settings is on.
 */
const TAB_OF_SETTING: Record<string, string> = {
  [CONNECTIONS_SETTING]: 'connections',
  [DEFAULT_SETTING]: 'connections',
  [FEATURES_SETTING]: 'features',
  [MEMBER_DAILY_SETTING]: 'usage',
  [GUEST_DAILY_SETTING]: 'usage',
};

/**
 * The AI page: Connections, Features and Usage tabs, then core's Permissions
 * tab, which lists each feature's permission. One save covers every tab.
 */
export default class AiPage extends ExtensionPage {
  connections!: Connections;
  features!: Record<string, FeatureSettings>;
  /** Features whose daily limit field holds something that isn't a limit. */
  invalidFeatureLimits = new Set<string>();
  usage: Usage | null = null;
  usageState: 'idle' | 'loading' | 'loaded' | 'failed' = 'idle';

  oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>) {
    super.oninit(vnode);

    this.connections = parseConnections(this.setting(CONNECTIONS_SETTING)());
    this.features = parseObject<FeatureSettings>(this.setting(FEATURES_SETTING)());

    // Registered up front, so a change on one tab still counts once another is open.
    this.setting(DEFAULT_SETTING);
    this.setting(MEMBER_DAILY_SETTING);
    this.setting(GUEST_DAILY_SETTING);
  }

  className() {
    return super.className() + ' AiPage';
  }

  pageTabItems(): ItemList<TabItem> {
    const items = super.pageTabItems();
    const unsaved = this.unsavedTabs();
    const indicatorLabel = extractText(app.translator.trans('core.admin.extension.unsaved_changes'));

    const tab = (id: string, icon: string, priority: number) =>
      items.add(
        id,
        {
          id,
          label: app.translator.trans(`flarum-ai.admin.tabs.${id}`),
          icon,
          indicator: unsaved.has(id),
          indicatorLabel: unsaved.has(id) ? indicatorLabel : undefined,
        },
        priority
      );

    tab('connections', 'fas fa-plug', 100);
    tab('features', 'fas fa-wand-magic-sparkles', 90);
    tab('usage', 'fas fa-chart-bar', 80);

    return items;
  }

  /**
   * The page's own tabs with unsaved changes. Its settings aren't in the
   * settings registry, so core can't work this out from it.
   */
  unsavedTabs(): Set<string> {
    const tabs = super.unsavedTabs();

    Object.keys(this.dirty()).forEach((key) => TAB_OF_SETTING[key] && tabs.add(TAB_OF_SETTING[key]));

    return tabs;
  }

  pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children {
    switch (tab) {
      case 'connections':
        return this.form([this.connectionsSection(), this.defaultSection()]);
      case 'features':
        return this.form(this.featureSections());
      case 'usage':
        // Once, the first time the tab is shown, not on every redraw.
        if (this.usageState === 'idle') this.loadUsage();

        return this.form([this.limitsSection(), this.usageSection()]);
      default:
        return super.pageTabContent(tab, vnode);
    }
  }

  form(sections: Mithril.Children): Mithril.Children {
    return (
      <div className="ExtensionPage-settings">
        <Form className="AdminPage-settingsForm AdminPage-settingsForm--sectioned AiPage-form">
          {sections}
          <div className="Form-group Form-controls">{this.submitButton()}</div>
        </Form>
      </div>
    );
  }

  connectionsSection(): Mithril.Children {
    const locked = aiPayload().lockedConnections;
    const names = Object.keys(this.connections);
    const lockedNames = Object.keys(locked);

    return (
      <SettingsSection
        id="flarum-ai-connections"
        label={app.translator.trans('flarum-ai.admin.connections.heading')}
        description={app.translator.trans('flarum-ai.admin.connections.help')}
        icon="fas fa-plug"
      >
        {names.length || lockedNames.length ? (
          <ul className="AiPage-connections">
            {lockedNames.map((name) => this.connectionItem(name, locked[name], true))}
            {names.map((name) => this.connectionItem(name, this.connections[name], false))}
          </ul>
        ) : (
          <p className="AiPage-empty">{app.translator.trans('flarum-ai.admin.connections.empty_text')}</p>
        )}

        <div>
          <Button className="Button" icon="fas fa-plus" onclick={() => this.edit()}>
            {app.translator.trans('flarum-ai.admin.connections.add_button')}
          </Button>
        </div>
      </SettingsSection>
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

  defaultSection(): Mithril.Children {
    const { lockedDefault } = aiPayload();
    const value = this.setting(DEFAULT_SETTING)();

    const options: Record<string, Mithril.Children> = { '': app.translator.trans('flarum-ai.admin.connections.default_none_option') };
    this.connectionNames().forEach((name) => (options[name] = name));

    return (
      <SettingsSection
        id="flarum-ai-default"
        className="AiPage-default"
        label={app.translator.trans('flarum-ai.admin.connections.default_label')}
        description={app.translator.trans('flarum-ai.admin.connections.default_help')}
        icon="fas fa-star"
      >
        {lockedDefault ? (
          <p className="helpText">{app.translator.trans('flarum-ai.admin.connections.default_locked_text', { name: lockedDefault })}</p>
        ) : (
          <Select
            options={options}
            value={value in options ? value : ''}
            onchange={this.setting(DEFAULT_SETTING)}
            aria-label={extractText(app.translator.trans('flarum-ai.admin.connections.default_label'))}
          />
        )}
      </SettingsSection>
    );
  }

  featureSections(): Mithril.Children {
    const features = aiPayload().features;

    if (!features.length) {
      return (
        <SettingsSection label={app.translator.trans('flarum-ai.admin.features.heading')} icon="fas fa-wand-magic-sparkles">
          <p className="AiPage-empty">{app.translator.trans('flarum-ai.admin.features.empty_text')}</p>
        </SettingsSection>
      );
    }

    return features.map((feature) => this.featureSection(feature));
  }

  featureSection(feature: FeatureInfo): Mithril.Children {
    const settings = this.features[feature.key] || {};
    const label = app.translator.trans(feature.label);
    const idBase = 'flarum-ai-feature-' + feature.key.replace(/[^a-z0-9_-]/gi, '-');
    const extension = feature.extension ? app.data.extensions[feature.extension]?.extra['flarum-extension']?.title : null;
    const invalidLimit = this.invalidFeatureLimits.has(feature.key);

    const connections: Record<string, Mithril.Children> = { '': app.translator.trans('flarum-ai.admin.features.connection_default_option') };
    this.connectionNames().forEach((name) => (connections[name] = name));

    const connection = settings.connection && settings.connection in connections ? settings.connection : '';

    return (
      <SettingsSection
        key={feature.key}
        id={idBase}
        className="AiPage-feature"
        label={label}
        description={[
          feature.description ? <span>{app.translator.trans(feature.description)} </span> : null,
          extension ? <span>{app.translator.trans('flarum-ai.admin.features.extension_text', { extension })} </span> : null,
          app.translator.trans('flarum-ai.admin.features.help'),
        ]}
        icon="fas fa-wand-magic-sparkles"
      >
        <Switch state={!!settings.enabled} onchange={(enabled: boolean) => this.updateFeature(feature.key, { enabled })}>
          {app.translator.trans('flarum-ai.admin.features.enabled_label', { feature: label })}
        </Switch>

        <div className="AiPage-feature-options">
          <label for={idBase + '-connection'}>
            {app.translator.trans('flarum-ai.admin.features.connection_label')}
            <Select
              id={idBase + '-connection'}
              options={connections}
              value={connection}
              onchange={(value: string) => this.updateFeature(feature.key, { connection: value })}
            />
          </label>
          <label for={idBase + '-model'}>
            {app.translator.trans('flarum-ai.admin.features.model_label')}
            <input
              id={idBase + '-model'}
              className="FormControl"
              value={settings.model || ''}
              placeholder={app.translator.trans('flarum-ai.admin.features.model_placeholder')}
              maxlength={aiPayload().rules.modelMaxLength}
              spellcheck={false}
              oninput={(e: InputEvent) => this.updateFeature(feature.key, { model: (e.target as HTMLInputElement).value })}
            />
          </label>
          <label for={idBase + '-daily'}>
            {app.translator.trans('flarum-ai.admin.features.daily_tokens_label')}
            <input
              id={idBase + '-daily'}
              className="FormControl"
              type="number"
              min="1"
              step="1"
              inputmode="numeric"
              aria-invalid={invalidLimit ? 'true' : undefined}
              value={settings.daily_tokens || ''}
              oninput={(e: InputEvent) => this.updateFeatureLimit(feature.key, (e.target as HTMLInputElement).value)}
            />
            {invalidLimit && (
              <span className="helpText AiPage-invalid">{app.translator.trans('flarum-ai.admin.connections.tokens_invalid_message')}</span>
            )}
          </label>
        </div>
      </SettingsSection>
    );
  }

  limitsSection(): Mithril.Children {
    const field = (id: string, setting: string, label: string, help: string) => (
      <div className="Form-group">
        <label for={id}>{app.translator.trans(label)}</label>
        <div className="helpText">{app.translator.trans(help)}</div>
        <input id={id} className="FormControl AiPage-tokensInput" type="number" min="1" step="1" inputmode="numeric" bidi={this.setting(setting)} />
      </div>
    );

    return (
      <SettingsSection
        id="flarum-ai-limits"
        label={app.translator.trans('flarum-ai.admin.limits.heading')}
        description={app.translator.trans('flarum-ai.admin.limits.help')}
        icon="fas fa-gauge-high"
      >
        {field(
          'flarum-ai-member-daily',
          MEMBER_DAILY_SETTING,
          'flarum-ai.admin.limits.member_daily_label',
          'flarum-ai.admin.limits.member_daily_help'
        )}
        {field('flarum-ai-guest-daily', GUEST_DAILY_SETTING, 'flarum-ai.admin.limits.guest_daily_label', 'flarum-ai.admin.limits.guest_daily_help')}
      </SettingsSection>
    );
  }

  usageSection(): Mithril.Children {
    const usage = this.usage;
    const label = usage
      ? app.translator.trans('flarum-ai.admin.usage.heading', { month: dayjs(usage.month + '-01').format('MMMM YYYY') })
      : app.translator.trans('flarum-ai.admin.tabs.usage');

    let body: Mithril.Children;

    if (this.usageState === 'failed') {
      body = (
        <p className="AiPage-empty">
          {app.translator.trans('flarum-ai.admin.usage.load_error_text')}{' '}
          <Button className="Button Button--link" onclick={() => this.loadUsage()}>
            {app.translator.trans('flarum-ai.admin.usage.retry_button')}
          </Button>
        </p>
      );
    } else if (!usage) {
      body = <LoadingIndicator display="inline" />;
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
      <SettingsSection
        id="flarum-ai-usage"
        className="AiPage-usage"
        label={label}
        description={app.translator.trans('flarum-ai.admin.usage.help')}
        icon="fas fa-chart-bar"
      >
        {body}
      </SettingsSection>
    );
  }

  daysChart(days: Usage['days']): Mithril.Children {
    const max = Math.max(1, ...days.map((day) => day.tokens));
    const total = days.reduce((sum, day) => sum + day.tokens, 0);
    const peak = days.reduce((top, day) => (day.tokens > top.tokens ? day : top), days[0]);

    const summary = extractText(
      app.translator.trans('flarum-ai.admin.usage.chart_label', {
        total: formatTokens(total),
        peakDate: dayjs(peak.date).format('ll'),
        peak: formatTokens(peak.tokens),
      })
    );

    return (
      <div className="AiPage-days">
        <h4>{app.translator.trans('flarum-ai.admin.usage.days_heading')}</h4>
        <div className="AiPage-days-bars" role="img" aria-label={summary}>
          {days.map((day) => (
            <span
              key={day.date}
              className="AiPage-days-bar"
              aria-hidden="true"
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
            <th scope="col">{app.translator.trans('flarum-ai.admin.usage.name_column')}</th>
            <th scope="col">{app.translator.trans('flarum-ai.admin.usage.calls_column')}</th>
            <th scope="col">{app.translator.trans('flarum-ai.admin.usage.tokens_column')}</th>
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
    this.usageState = 'loading';

    return app
      .request<Usage>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/ai/usage',
        // The page says the totals couldn't load; no alert or modal on top of that.
        errorHandler: (error) => console.error(error),
      })
      .then((usage) => {
        this.usage = usage;
        this.usageState = 'loaded';
      })
      .catch(() => {
        this.usageState = 'failed';
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

  updateFeatureLimit(key: string, value: string) {
    const tokens = parseTokens(value);

    if (tokens === false) {
      this.invalidFeatureLimits.add(key);

      return;
    }

    this.invalidFeatureLimits.delete(key);
    this.updateFeature(key, { daily_tokens: tokens ?? undefined });
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

    const saved = serializeConnections(this.connections);

    app.data.settings[CONNECTIONS_SETTING] = saved;
    this.setting(CONNECTIONS_SETTING)(saved);
    aiPayload().connectionsWithKeys = Object.keys(this.connections).filter((name) => this.connections[name].hasKey);
  }
}
