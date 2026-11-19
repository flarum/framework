import app from 'flarum/admin/app';
import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import Select from 'flarum/common/components/Select';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import { aiPayload, type Connection, driverLabel, KEY_URLS, nameIsValid, parseTokens } from '../utils/settings';
import type { ModelLists } from '../utils/models';
import ConnectionTest, { type CandidateConnection, ConnectionTestState } from './ConnectionTest';
import ModelInput from './ModelInput';

export interface EditConnectionModalAttrs extends IFormModalAttrs {
  /** Set when editing; a new connection has none yet. */
  name?: string;
  connection?: Connection;
  /** The provider a new connection starts with. */
  driver?: string;
  takenNames: string[];
  /** The saved connection whose models can be listed, if this is one. */
  listedAs?: string | null;
  lists?: ModelLists;
  onsave: (name: string, connection: Connection) => void;
}

type Field = 'name' | 'driver' | 'url' | 'region' | 'key' | 'model' | 'monthlyTokens' | 'guardrail';

const id = (field: Field) => `flarum-ai-connection-${field}`;

const normaliseUrl = (url: string | undefined) => (url || '').trim().toLowerCase().replace(/\/+$/, '');

/**
 * Adds or edits a connection on the page. Nothing is stored until the admin
 * saves the page.
 */
export default class EditConnectionModal<CustomAttrs extends EditConnectionModalAttrs = EditConnectionModalAttrs> extends FormModal<CustomAttrs> {
  name!: Stream<string>;
  driver!: Stream<string>;
  url!: Stream<string>;
  region!: Stream<string>;
  guardrailId!: Stream<string>;
  guardrailVersion!: Stream<string>;
  model!: Stream<string>;
  monthlyTokens!: Stream<string>;
  key!: Stream<string>;
  removeKey = false;
  invalid: Field | null = null;
  test = new ConnectionTestState();

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    const connection = this.attrs.connection;

    this.name = Stream(this.attrs.name || '');
    this.driver = Stream(connection?.driver || this.attrs.driver || aiPayload().drivers[0]);
    this.url = Stream(connection?.url || '');
    this.region = Stream(connection?.region || '');
    this.guardrailId = Stream(connection?.guardrail_id || '');
    this.guardrailVersion = Stream(connection?.guardrail_version || '');
    this.model = Stream(connection?.model || '');
    this.monthlyTokens = Stream(connection?.monthly_tokens ? String(connection.monthly_tokens) : '');
    this.key = Stream(connection?.key || '');
  }

  className() {
    return 'EditConnectionModal Modal--small';
  }

  title() {
    return this.attrs.name
      ? app.translator.trans('flarum-ai.admin.edit_connection.edit_title', { name: this.attrs.name })
      : app.translator.trans('flarum-ai.admin.edit_connection.add_title');
  }

  content() {
    return (
      <div className="Modal-body">
        <Form>
          {this.fields().toArray()}
          <ConnectionTest state={this.test} />
          <div className="Form-group Form-controls">
            <Button className="Button Button--primary" type="submit">
              {app.translator.trans('flarum-ai.admin.edit_connection.submit_button')}
            </Button>
            <Button className="Button" type="button" icon="fas fa-vial" loading={this.test.status === 'running'} onclick={() => this.runTest()}>
              {app.translator.trans('flarum-ai.admin.edit_connection.test_button')}
            </Button>
          </div>
        </Form>
      </div>
    );
  }

  fields(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const { keyOptional, urlRequired, rules, awsSdk } = aiPayload();
    const driver = this.driver();
    const byRegion = this.byRegion();

    items.add(
      'name',
      this.field(
        'name',
        'flarum-ai.admin.edit_connection.name_label',
        'flarum-ai.admin.edit_connection.name_help',
        // A saved key belongs to its connection's name, so a connection keeps the name it was added with.
        <input className="FormControl" bidi={this.name} disabled={!!this.attrs.name} maxlength={50} spellcheck={false} {...this.fieldAttrs('name')} />
      ),
      60
    );

    const options = this.driverOptions();

    items.add(
      'driver',
      this.field(
        'driver',
        'flarum-ai.admin.edit_connection.driver_label',
        null,
        <Select options={options} value={driver} onchange={this.driver} {...this.fieldAttrs('driver')} />
      ),
      50
    );

    if (!awsSdk) {
      items.add(
        'awsSdk',
        <p className="helpText EditConnectionModal-awsSdk">{app.translator.trans('flarum-ai.admin.edit_connection.aws_sdk_text')}</p>,
        45
      );
    }

    if (byRegion) {
      items.add(
        'region',
        this.field(
          'region',
          'flarum-ai.admin.edit_connection.region_label',
          'flarum-ai.admin.edit_connection.region_help',
          <input className="FormControl" bidi={this.region} placeholder="eu-central-1" spellcheck={false} {...this.fieldAttrs('region')} />
        ),
        40
      );
    } else {
      items.add(
        'url',
        this.field(
          'url',
          'flarum-ai.admin.edit_connection.url_label',
          urlRequired.includes(driver) ? 'flarum-ai.admin.edit_connection.url_required_help' : 'flarum-ai.admin.edit_connection.url_optional_help',
          <input
            className="FormControl"
            type="url"
            bidi={this.url}
            placeholder={aiPayload().defaultUrls?.[driver] || ''}
            spellcheck={false}
            {...this.fieldAttrs('url')}
          />
        ),
        40
      );
    }

    items.add('key', this.keyField(keyOptional.includes(driver)), 30);

    items.add(
      'model',
      this.field(
        'model',
        'flarum-ai.admin.edit_connection.model_label',
        'flarum-ai.admin.edit_connection.model_help',
        this.attrs.lists && this.listsSavedModels() ? (
          <ModelInput
            id={id('model')}
            value={this.model()}
            maxlength={rules.modelMaxLength}
            connection={this.attrs.listedAs ?? null}
            lists={this.attrs.lists}
            oninput={this.model}
          />
        ) : this.attrs.lists && this.canAsk() ? (
          // Until it's saved, its models are listed from what's typed in.
          <ModelInput
            id={id('model')}
            value={this.model()}
            maxlength={rules.modelMaxLength}
            connection={'candidate:' + JSON.stringify(this.candidateFor(true))}
            candidate={{ name: this.name().trim(), connection: this.candidateFor(true) }}
            lists={this.attrs.lists}
            oninput={this.model}
          />
        ) : (
          <input className="FormControl" bidi={this.model} maxlength={rules.modelMaxLength} spellcheck={false} {...this.fieldAttrs('model')} />
        )
      ),
      20
    );

    items.add(
      'monthlyTokens',
      this.field(
        'monthlyTokens',
        'flarum-ai.admin.edit_connection.monthly_tokens_label',
        'flarum-ai.admin.edit_connection.monthly_tokens_help',
        <input
          className="FormControl"
          type="number"
          min="1"
          step="1"
          inputmode="numeric"
          bidi={this.monthlyTokens}
          {...this.fieldAttrs('monthlyTokens')}
        />
      ),
      10
    );

    if (driver === 'bedrock') {
      items.add(
        'guardrail',
        this.field(
          'guardrail',
          'flarum-ai.admin.edit_connection.guardrail_label',
          'flarum-ai.admin.edit_connection.guardrail_help',
          <div className="EditConnectionModal-guardrail">
            <input
              className="FormControl"
              bidi={this.guardrailId}
              placeholder={app.translator.trans('flarum-ai.admin.edit_connection.guardrail_id_placeholder')}
              spellcheck={false}
              {...this.fieldAttrs('guardrail')}
            />
            <input
              className="FormControl"
              bidi={this.guardrailVersion}
              placeholder="DRAFT"
              spellcheck={false}
              aria-label={app.translator.trans('flarum-ai.admin.edit_connection.guardrail_version_label')}
            />
          </div>
        ),
        5
      );
    }

    return items;
  }

  /**
   * The providers to choose from: those the page offers, and the one the
   * connection was saved with, so a Bedrock connection can still be changed
   * or removed after the AWS SDK it needs is taken away.
   */
  driverOptions(): Record<string, string> {
    const options: Record<string, string> = {};
    const saved = this.attrs.connection?.driver;

    aiPayload().drivers.forEach((name) => (options[name] = driverLabel(name)));
    if (saved && !(saved in options)) options[saved] = driverLabel(saved);

    return options;
  }

  /**
   * Whether the provider is reached by region, not address.
   */
  byRegion(): boolean {
    return aiPayload().regionRequired.includes(this.driver());
  }

  field(field: Field, label: string, help: string | null, control: Mithril.Children, extra?: Mithril.Children): Mithril.Children {
    return (
      <div className="Form-group">
        <label for={id(field)}>{app.translator.trans(label)}</label>
        {!!help && (
          <div className="helpText" id={id(field) + '-help'}>
            {app.translator.trans(help)}
          </div>
        )}
        {control}
        {extra}
      </div>
    );
  }

  /**
   * Whether the models listed for the saved connection are this one's: it
   * still has the provider, address and key it was saved with.
   */
  listsSavedModels(): boolean {
    const saved = this.attrs.connection;

    return (
      !!this.attrs.listedAs &&
      !!saved &&
      this.driver() === saved.driver &&
      // Only the field the provider uses counts, whatever was typed in the other.
      (this.byRegion() ? this.region().trim() === (saved.region || '') : normaliseUrl(this.url()) === normaliseUrl(saved.url)) &&
      !this.key().trim() &&
      !this.removeKey
    );
  }

  fieldAttrs(field: Field): Record<string, unknown> {
    return {
      id: id(field),
      'aria-invalid': this.invalid === field ? 'true' : undefined,
    };
  }

  keyField(optional: boolean): Mithril.Children {
    const savedKey = this.savedKeyApplies();
    const dropped = !!this.attrs.connection?.hasKey && this.attrs.connection.key === undefined && !savedKey;

    return this.field(
      'key',
      'flarum-ai.admin.edit_connection.key_label',
      dropped
        ? 'flarum-ai.admin.edit_connection.key_dropped_text'
        : this.driver() === 'bedrock'
          ? 'flarum-ai.admin.edit_connection.key_bedrock_help'
          : optional
            ? 'flarum-ai.admin.edit_connection.key_optional_help'
            : null,
      [
        !!KEY_URLS[this.driver()] && (
          <a className="EditConnectionModal-keyLink helpText" href={KEY_URLS[this.driver()]} target="_blank" rel="noopener noreferrer">
            {app.translator.trans('flarum-ai.admin.edit_connection.key_link', { provider: driverLabel(this.driver()) })}
          </a>
        ),
        <input
          className="FormControl"
          type="password"
          // Keeps the browser from filling in the admin's own password.
          autocomplete="new-password"
          bidi={this.key}
          placeholder={savedKey && !this.removeKey ? app.translator.trans('flarum-ai.admin.edit_connection.key_saved_placeholder') : ''}
          spellcheck={false}
          {...this.fieldAttrs('key')}
        />,
      ],
      savedKey &&
        (this.removeKey ? (
          <div className="helpText">{app.translator.trans('flarum-ai.admin.edit_connection.key_removed_text')}</div>
        ) : (
          <Button className="Button Button--link" onclick={() => (this.removeKey = true)}>
            {app.translator.trans('flarum-ai.admin.edit_connection.remove_key_button')}
          </Button>
        ))
    );
  }

  /**
   * Whether the saved key will still be used: only with the provider and API
   * URL it was saved for, as the server checks.
   */
  savedKeyApplies(): boolean {
    const connection = this.attrs.connection;

    return (
      !!connection?.hasKey &&
      connection.key === undefined &&
      connection.driver === this.driver() &&
      // A provider reached by region has no address, whatever was typed before switching to it.
      normaliseUrl(this.byRegion() ? '' : connection.url) === normaliseUrl(this.byRegion() ? '' : this.url()) &&
      (this.byRegion() ? this.region().trim() : '') === (connection.region || '')
    );
  }

  /**
   * Whether enough has been typed in to ask the provider anything: its
   * address or region where it needs one, and a key where it needs one.
   */
  canAsk(): boolean {
    const { keyOptional, urlRequired, rules } = aiPayload();
    const driver = this.driver();

    if (!nameIsValid(this.name().trim())) return false;
    if (urlRequired.includes(driver) && !this.url().trim()) return false;
    if (this.byRegion() && !new RegExp(rules.regionPattern).test(this.region().trim())) return false;

    return keyOptional.includes(driver) || !!this.key().trim() || (!this.removeKey && this.savedKeyApplies());
  }

  /**
   * The connection as typed in, to test or list the models of before it's
   * saved. Left without a key, the server uses the saved one, if it applies.
   *
   * @param forModels Without the model and guardrail, which don't change the list.
   */
  candidateFor(forModels = false): CandidateConnection {
    const candidate: CandidateConnection = { driver: this.driver() };

    if (this.byRegion()) candidate.region = this.region().trim();
    else if (this.url().trim()) candidate.url = this.url().trim();
    if (this.key().trim()) candidate.key = this.key().trim();
    if (this.removeKey) candidate.removeKey = true;

    if (!forModels) {
      if (this.model().trim()) candidate.model = this.model().trim();
      if (this.driver() === 'bedrock' && this.guardrailId().trim()) {
        candidate.guardrail_id = this.guardrailId().trim();
        if (this.guardrailVersion().trim()) candidate.guardrail_version = this.guardrailVersion().trim();
      }
    }

    return candidate;
  }

  /**
   * Tests the connection as it's typed in, before it's saved.
   */
  runTest() {
    if (!this.check()) return;

    this.test.start(this.name().trim(), this.candidateFor());
  }

  /**
   * Marks the first problem with the form, if any.
   *
   * @return Whether there's none.
   */
  check(): boolean {
    const problem = this.validate();

    this.invalid = problem?.field ?? null;

    if (problem) {
      this.alertAttrs = { type: 'error', content: app.translator.trans(problem.message, { name: this.name().trim() }) };

      // Once the alert and the field's state have been drawn.
      setTimeout(() => document.getElementById(id(problem.field))?.focus());

      return false;
    }

    this.alertAttrs = null;

    return true;
  }

  /**
   * The first problem with the form, checked as the server would, so the
   * admin can fix it here, next to the field.
   */
  validate(): { field: Field; message: string } | null {
    const name = this.name().trim();
    const driver = this.driver();
    const { keyOptional, urlRequired, rules } = aiPayload();
    const region = this.region().trim();
    const guardrailId = this.guardrailId().trim();

    if (!nameIsValid(name)) return { field: 'name', message: 'flarum-ai.admin.connections.name_invalid_message' };
    if (!this.attrs.name && this.attrs.takenNames.includes(name))
      return { field: 'name', message: 'flarum-ai.admin.edit_connection.name_taken_message' };
    if (!this.url().trim() && urlRequired.includes(driver)) return { field: 'url', message: 'flarum-ai.admin.connections.url_required_message' };
    if (this.byRegion() && !new RegExp(rules.regionPattern).test(region)) {
      return {
        field: 'region',
        message: region ? 'flarum-ai.admin.connections.region_invalid_message' : 'flarum-ai.admin.connections.region_required_message',
      };
    }
    if (
      driver === 'bedrock' &&
      guardrailId &&
      (!new RegExp(rules.guardrailPattern).test(guardrailId) ||
        !new RegExp(rules.guardrailVersionPattern).test(this.guardrailVersion().trim() || 'DRAFT'))
    ) {
      return { field: 'guardrail', message: 'flarum-ai.admin.connections.guardrail_invalid_message' };
    }
    if (!this.key().trim() && (this.removeKey || !this.savedKeyApplies()) && !keyOptional.includes(driver)) {
      return { field: 'key', message: 'flarum-ai.admin.connections.key_required_message' };
    }
    if (parseTokens(this.monthlyTokens()) === false) return { field: 'monthlyTokens', message: 'flarum-ai.admin.connections.tokens_invalid_message' };

    return null;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    const name = this.name().trim();

    if (!this.check()) return;

    const connection: Connection = { driver: this.driver() };
    const url = this.byRegion() ? '' : this.url().trim();
    const region = this.byRegion() ? this.region().trim() : '';
    const guardrailId = this.driver() === 'bedrock' ? this.guardrailId().trim() : '';
    const guardrailVersion = this.guardrailVersion().trim();
    const model = this.model().trim();
    const monthlyTokens = parseTokens(this.monthlyTokens());
    const key = this.key().trim();

    if (url) connection.url = url;
    if (region) connection.region = region;
    if (model) connection.model = model;
    if (monthlyTokens) connection.monthly_tokens = monthlyTokens;
    if (guardrailId) connection.guardrail_id = guardrailId;
    if (guardrailId && guardrailVersion) connection.guardrail_version = guardrailVersion;

    // Left without a key, the connection keeps the saved one, if it still applies.
    if (key) {
      connection.key = key;
    } else if (this.removeKey) {
      connection.key = '';
    } else {
      connection.hasKey = this.savedKeyApplies();
    }

    this.attrs.onsave(name, connection);
    this.hide();
  }
}
