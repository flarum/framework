import app from 'flarum/admin/app';
import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import Select from 'flarum/common/components/Select';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import { aiPayload, type Connection, driverLabel, nameIsValid, parseTokens } from '../utils/settings';

export interface EditConnectionModalAttrs extends IFormModalAttrs {
  /** Set when editing; a new connection has none yet. */
  name?: string;
  connection?: Connection;
  takenNames: string[];
  onsave: (name: string, connection: Connection) => void;
}

type Field = 'name' | 'driver' | 'url' | 'key' | 'model' | 'monthlyTokens';

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
  model!: Stream<string>;
  monthlyTokens!: Stream<string>;
  key!: Stream<string>;
  removeKey = false;
  invalid: Field | null = null;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    const connection = this.attrs.connection;

    this.name = Stream(this.attrs.name || '');
    this.driver = Stream(connection?.driver || aiPayload().drivers[0]);
    this.url = Stream(connection?.url || '');
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
          <div className="Form-group Form-controls">
            <Button className="Button Button--primary" type="submit">
              {app.translator.trans('flarum-ai.admin.edit_connection.submit_button')}
            </Button>
          </div>
        </Form>
      </div>
    );
  }

  fields(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const { drivers, keyOptional, urlRequired, rules } = aiPayload();
    const driver = this.driver();

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

    const options: Record<string, string> = {};
    drivers.forEach((name) => (options[name] = driverLabel(name)));

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

    items.add(
      'url',
      this.field(
        'url',
        'flarum-ai.admin.edit_connection.url_label',
        urlRequired.includes(driver) ? 'flarum-ai.admin.edit_connection.url_required_help' : 'flarum-ai.admin.edit_connection.url_optional_help',
        <input className="FormControl" type="url" bidi={this.url} spellcheck={false} {...this.fieldAttrs('url')} />
      ),
      40
    );

    items.add('key', this.keyField(keyOptional.includes(driver)), 30);

    items.add(
      'model',
      this.field(
        'model',
        'flarum-ai.admin.edit_connection.model_label',
        'flarum-ai.admin.edit_connection.model_help',
        <input className="FormControl" bidi={this.model} maxlength={rules.modelMaxLength} spellcheck={false} {...this.fieldAttrs('model')} />
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

    return items;
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
      dropped ? 'flarum-ai.admin.edit_connection.key_dropped_text' : optional ? 'flarum-ai.admin.edit_connection.key_optional_help' : null,
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
      normaliseUrl(connection.url) === normaliseUrl(this.url())
    );
  }

  /**
   * The first problem with the form, checked as the server would, so the
   * admin can fix it here, next to the field.
   */
  validate(): { field: Field; message: string } | null {
    const name = this.name().trim();
    const driver = this.driver();
    const { keyOptional, urlRequired } = aiPayload();

    if (!nameIsValid(name)) return { field: 'name', message: 'flarum-ai.admin.connections.name_invalid_message' };
    if (!this.attrs.name && this.attrs.takenNames.includes(name))
      return { field: 'name', message: 'flarum-ai.admin.edit_connection.name_taken_message' };
    if (!this.url().trim() && urlRequired.includes(driver)) return { field: 'url', message: 'flarum-ai.admin.connections.url_required_message' };
    if (!this.key().trim() && (this.removeKey || !this.savedKeyApplies()) && !keyOptional.includes(driver)) {
      return { field: 'key', message: 'flarum-ai.admin.connections.key_required_message' };
    }
    if (parseTokens(this.monthlyTokens()) === false) return { field: 'monthlyTokens', message: 'flarum-ai.admin.connections.tokens_invalid_message' };

    return null;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    const name = this.name().trim();
    const problem = this.validate();

    this.invalid = problem?.field ?? null;

    if (problem) {
      this.alertAttrs = { type: 'error', content: app.translator.trans(problem.message, { name }) };

      // Once the alert and the field's state have been drawn.
      setTimeout(() => document.getElementById(id(problem.field))?.focus());

      return;
    }

    const connection: Connection = { driver: this.driver() };
    const url = this.url().trim();
    const model = this.model().trim();
    const monthlyTokens = parseTokens(this.monthlyTokens());
    const key = this.key().trim();

    if (url) connection.url = url;
    if (model) connection.model = model;
    if (monthlyTokens) connection.monthly_tokens = monthlyTokens;

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
