import app from 'flarum/admin/app';
import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import Alert from 'flarum/common/components/Alert';
import Group from 'flarum/common/models/Group';
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
import ModelInput from './ModelInput';
import { type ModelIssue, ModelLists } from '../utils/models';
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
  parseTemperature,
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
 * A feature's settings typed in as numbers.
 */
type NumberField = 'daily_tokens' | 'temperature' | 'max_tokens';

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
  /** Feature fields holding something that isn't a valid value, as `key field`. */
  invalidFeatureFields = new Set<string>();
  usage: Usage | null = null;
  modelLists = new ModelLists();
  checkingModels = false;
  /** Connections the last check couldn't ask for their models. */
  uncheckedConnections: string[] = [];
  /** Connections whose providers don't list their models, so they can't be checked. */
  unlistedConnections: string[] = [];
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
        {!!connection.region && <code className="AiPage-connection-url">{connection.region}</code>}
        {!!connection.guardrail_id && (
          <span className="AiPage-connection-guardrail">
            {app.translator.trans('flarum-ai.admin.connections.guardrail_text', {
              guardrail: connection.guardrail_id,
              version: connection.guardrail_version || 'DRAFT',
            })}
          </span>
        )}
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

    // Every item keyed, as Mithril needs of a list where any is: the alert
    // only when it's shown.
    return [
      this.guestsUnlimitedAlert('guests-unlimited'),
      this.modelIssuesSection(),
      ...features.map((feature) => this.featureSection(feature)),
    ].filter(Boolean);
  }

  /**
   * The models features use that are deprecated or gone, and a way to check
   * them now rather than at the daily check.
   */
  modelIssuesSection(): Mithril.Children {
    const issues = aiPayload().modelIssues;

    return (
      <SettingsSection
        key="flarum-ai-models"
        id="flarum-ai-models"
        className="AiPage-models"
        label={app.translator.trans('flarum-ai.admin.models.heading')}
        description={app.translator.trans('flarum-ai.admin.models.help')}
        icon="fas fa-heart-pulse"
      >
        {this.unlistedConnections.map((connection) => (
          <Alert key={'unlisted:' + connection} type="info" dismissible={false}>
            {app.translator.trans('flarum-ai.admin.models.unlisted_text', { connection })}
          </Alert>
        ))}
        {this.uncheckedConnections.map((connection) => (
          <Alert key={'unchecked:' + connection} type="warning" dismissible={false}>
            {app.translator.trans('flarum-ai.admin.models.unchecked_text', { connection })}
          </Alert>
        ))}
        {issues.length ? (
          <ul className="AiPage-modelIssues">
            {issues.map((issue) => (
              <li key={issue.connection + '/' + issue.model}>{this.issueAlert(issue)}</li>
            ))}
          </ul>
        ) : (
          <p className="AiPage-empty">{app.translator.trans('flarum-ai.admin.models.no_issues_text')}</p>
        )}
        <div>
          <Button className="Button" icon="fas fa-stethoscope" loading={this.checkingModels} onclick={() => this.checkModels()}>
            {app.translator.trans('flarum-ai.admin.models.check_button')}
          </Button>
        </div>
      </SettingsSection>
    );
  }

  issueAlert(issue: ModelIssue): Mithril.Children {
    const labels = Object.fromEntries(aiPayload().features.map((feature) => [feature.key, extractText(app.translator.trans(feature.label))]));
    const features = issue.features.map((key) => labels[key] || key).join(', ');
    const params = {
      model: issue.model,
      connection: issue.connection,
      features: features || extractText(app.translator.trans('flarum-ai.admin.models.no_features_text')),
      since: dayjs(issue.since).format('ll'),
      date: issue.retiresAt ? dayjs(issue.retiresAt).format('ll') : '',
    };

    const text =
      issue.status === 'deprecated'
        ? issue.retiresAt
          ? 'flarum-ai.admin.models.issue_retiring_text'
          : 'flarum-ai.admin.models.issue_deprecated_text'
        : issue.status === 'missing'
          ? 'flarum-ai.admin.models.issue_missing_text'
          : 'flarum-ai.admin.models.issue_failed_text';

    return (
      <Alert type={issue.status === 'deprecated' ? 'warning' : 'error'} dismissible={false}>
        {app.translator.trans(text, params)}
      </Alert>
    );
  }

  /**
   * Whether the admin's temperature for a feature is left out of its calls,
   * as its model refuses one. Known once a call has been refused.
   */
  temperatureIgnored(key: string, settings: FeatureSettings): boolean {
    const connection = this.featureConnection(settings);
    const model = settings.model || (connection ? this.connectionModel(connection) || aiPayload().defaultModels?.[connection] : null);

    return (
      settings.temperature !== undefined &&
      !!connection &&
      !!model &&
      (aiPayload().temperatureIgnored || []).some((entry) => entry.feature === key && entry.connection === connection && entry.model === model)
    );
  }

  featureIssues(key: string): ModelIssue[] {
    return aiPayload().modelIssues.filter((issue) => issue.features.includes(key));
  }

  checkModels() {
    this.checkingModels = true;

    return app
      .request<{ issues: ModelIssue[]; unchecked: string[]; unlisted: string[] }>({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/ai/models/check',
      })
      .then(({ issues, unchecked, unlisted }) => {
        aiPayload().modelIssues = issues;
        this.uncheckedConnections = unchecked || [];
        this.unlistedConnections = unlisted || [];
      })
      .catch(() => {})
      .finally(() => {
        this.checkingModels = false;
        m.redraw();
      });
  }

  /**
   * The issues as they are once a save has changed which models are in use.
   */
  reloadModelIssues() {
    return app
      .request<{ issues: ModelIssue[] }>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/ai/models/issues',
        errorHandler: (error) => console.error(error),
      })
      .then(({ issues }) => {
        aiPayload().modelIssues = issues;
      })
      .catch(() => {})
      .finally(() => m.redraw());
  }

  /**
   * Whether guests can use a feature while they have no daily limit: any
   * guest could then spend the forum's tokens without end.
   */
  guestsUnlimited(): boolean {
    if (this.setting(GUEST_DAILY_SETTING)()) return false;

    return aiPayload().features.some((feature) => (app.data.permissions[feature.permission] || []).includes(Group.GUEST_ID));
  }

  /**
   * @param key Set where the alert's siblings are keyed, as Mithril needs all
   *            or none of a list's items to be.
   */
  guestsUnlimitedAlert(key?: string): Mithril.Children {
    if (!this.guestsUnlimited()) return null;

    return (
      <Alert key={key} className="AiPage-guestsUnlimited" type="warning" dismissible={false}>
        {app.translator.trans('flarum-ai.admin.limits.guests_unlimited_text')}
      </Alert>
    );
  }

  /**
   * The connections saved on the server, whose models can be listed. One
   * added or changed on the page is listed once it's saved.
   */
  savedConnection(name: string | null): string | null {
    if (!name) return null;

    const saved = parseObject<Connection>(app.data.settings[CONNECTIONS_SETTING]);
    const current = this.connections[name];

    if (name in aiPayload().lockedConnections) return name;

    return saved[name] &&
      current &&
      saved[name].driver === current.driver &&
      (saved[name].url || '') === (current.url || '') &&
      (saved[name].region || '') === (current.region || '')
      ? name
      : null;
  }

  /**
   * The connection a feature's calls go through: its own, or the default.
   */
  featureConnection(settings: FeatureSettings): string | null {
    const names = this.connectionNames();

    if (settings.connection && names.includes(settings.connection)) return settings.connection;

    const fallback = aiPayload().lockedDefault || this.setting(DEFAULT_SETTING)();

    return fallback && names.includes(fallback) ? fallback : null;
  }

  featureSection(feature: FeatureInfo): Mithril.Children {
    const settings = this.features[feature.key] || {};
    const label = app.translator.trans(feature.label);
    const idBase = 'flarum-ai-feature-' + feature.key.replace(/[^a-z0-9_-]/gi, '-');
    const extension = feature.extension ? app.data.extensions[feature.extension]?.extra['flarum-extension']?.title : null;

    const connections: Record<string, Mithril.Children> = { '': app.translator.trans('flarum-ai.admin.features.connection_default_option') };
    this.connectionNames().forEach((name) => (connections[name] = name));

    const connection = settings.connection && settings.connection in connections ? settings.connection : '';
    const effectiveConnection = this.featureConnection(settings);
    const issues = this.featureIssues(feature.key);

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
        {issues.map((issue) => this.issueAlert(issue))}

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
            <ModelInput
              id={idBase + '-model'}
              value={settings.model || ''}
              placeholder={extractText(app.translator.trans('flarum-ai.admin.features.model_placeholder'))}
              maxlength={aiPayload().rules.modelMaxLength}
              connection={this.savedConnection(effectiveConnection)}
              defaultModel={effectiveConnection ? this.connectionModel(effectiveConnection) : null}
              lists={this.modelLists}
              oninput={(model: string) => this.updateFeature(feature.key, { model })}
            />
          </label>
          {this.featureNumberField(
            feature.key,
            'daily_tokens',
            idBase + '-daily',
            'flarum-ai.admin.features.daily_tokens_label',
            settings.daily_tokens,
            {
              min: '1',
              step: '1',
              inputmode: 'numeric',
              message: 'flarum-ai.admin.connections.tokens_invalid_message',
            }
          )}
          {this.featureNumberField(
            feature.key,
            'temperature',
            idBase + '-temperature',
            'flarum-ai.admin.features.temperature_label',
            settings.temperature,
            {
              min: '0',
              max: '2',
              step: '0.1',
              inputmode: 'decimal',
              placeholder: 'flarum-ai.admin.features.agent_default_placeholder',
              message: 'flarum-ai.admin.features.temperature_invalid_message',
              note: this.temperatureIgnored(feature.key, settings) ? 'flarum-ai.admin.features.temperature_ignored_text' : undefined,
            }
          )}
          {this.featureNumberField(
            feature.key,
            'max_tokens',
            idBase + '-max-tokens',
            'flarum-ai.admin.features.max_tokens_label',
            settings.max_tokens,
            {
              min: '1',
              step: '1',
              inputmode: 'numeric',
              placeholder: 'flarum-ai.admin.features.agent_default_placeholder',
              message: 'flarum-ai.admin.features.max_tokens_invalid_message',
            }
          )}
        </div>
      </SettingsSection>
    );
  }

  featureNumberField(
    key: string,
    field: NumberField,
    id: string,
    label: string,
    value: number | undefined,
    options: { min: string; max?: string; step: string; inputmode: string; placeholder?: string; message: string; note?: string }
  ): Mithril.Children {
    const invalid = this.isInvalid(key, field);

    return (
      <label for={id}>
        {app.translator.trans(label)}
        <input
          id={id}
          className="FormControl"
          type="number"
          min={options.min}
          max={options.max}
          step={options.step}
          inputmode={options.inputmode}
          placeholder={options.placeholder ? extractText(app.translator.trans(options.placeholder)) : undefined}
          aria-invalid={invalid ? 'true' : undefined}
          value={value ?? ''}
          oninput={(e: InputEvent) => this.updateFeatureNumber(key, field, (e.target as HTMLInputElement).value)}
        />
        {invalid && <span className="helpText AiPage-invalid">{app.translator.trans(options.message)}</span>}
        {!invalid && !!options.note && <span className="helpText AiPage-note">{app.translator.trans(options.note)}</span>}
      </label>
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
        {this.guestsUnlimitedAlert()}
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

  /**
   * The model a connection uses when a feature names none, if the admin set one.
   */
  connectionModel(name: string): string | null {
    return (aiPayload().lockedConnections[name] || this.connections[name])?.model || null;
  }

  connectionNames(): string[] {
    return [...Object.keys(aiPayload().lockedConnections), ...Object.keys(this.connections)];
  }

  edit(name?: string) {
    app.modal.show(EditConnectionModal, {
      name,
      connection: name ? this.connections[name] : undefined,
      takenNames: this.connectionNames().filter((other) => other !== name),
      listedAs: this.savedConnection(name ?? null),
      lists: this.modelLists,
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

  isInvalid(key: string, field: NumberField): boolean {
    return this.invalidFeatureFields.has(`${key} ${field}`);
  }

  updateFeatureNumber(key: string, field: NumberField, value: string) {
    const parsed = field === 'temperature' ? parseTemperature(value) : parseTokens(value);

    if (parsed === false) {
      this.invalidFeatureFields.add(`${key} ${field}`);

      return;
    }

    this.invalidFeatureFields.delete(`${key} ${field}`);
    this.updateFeature(key, { [field]: parsed ?? undefined });
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

    // A connection's provider, address or key may have changed, and with
    // them its models and which of them are in use.
    this.modelLists = new ModelLists();
    this.reloadModelIssues();
  }
}
