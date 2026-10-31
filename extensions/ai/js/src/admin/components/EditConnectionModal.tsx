import app from 'flarum/admin/app';
import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import Select from 'flarum/common/components/Select';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import { aiPayload, type Connection, driverLabel } from '../connections';

export interface EditConnectionModalAttrs extends IFormModalAttrs {
  /** Set when editing; a new connection has none yet. */
  name?: string;
  connection?: Connection;
  takenNames: string[];
  onsave: (name: string, connection: Connection) => void;
}

/**
 * Adds or edits a connection on the page. Nothing is stored until the admin
 * saves the page.
 */
export default class EditConnectionModal<CustomAttrs extends EditConnectionModalAttrs = EditConnectionModalAttrs> extends FormModal<CustomAttrs> {
  name!: Stream<string>;
  driver!: Stream<string>;
  url!: Stream<string>;
  key!: Stream<string>;
  removeKey = false;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    const connection = this.attrs.connection;

    this.name = Stream(this.attrs.name || '');
    this.driver = Stream(connection?.driver || aiPayload().drivers[0]);
    this.url = Stream(connection?.url || '');
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
    const { drivers, keyOptional, urlRequired } = aiPayload();
    const driver = this.driver();

    items.add(
      'name',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-ai.admin.edit_connection.name_label')}</label>
        <div className="helpText">{app.translator.trans('flarum-ai.admin.edit_connection.name_help')}</div>
        {/* A saved key belongs to its connection's name, so a connection keeps the name it was added with. */}
        <input className="FormControl" bidi={this.name} disabled={!!this.attrs.name} maxlength={50} spellcheck={false} />
      </div>,
      50
    );

    const options: Record<string, string> = {};
    drivers.forEach((name) => (options[name] = driverLabel(name)));

    items.add(
      'driver',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-ai.admin.edit_connection.driver_label')}</label>
        <Select options={options} value={driver} onchange={this.driver} />
      </div>,
      40
    );

    items.add(
      'url',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-ai.admin.edit_connection.url_label')}</label>
        <div className="helpText">
          {app.translator.trans(
            urlRequired.includes(driver) ? 'flarum-ai.admin.edit_connection.url_required_help' : 'flarum-ai.admin.edit_connection.url_optional_help'
          )}
        </div>
        <input className="FormControl" type="url" bidi={this.url} spellcheck={false} />
      </div>,
      30
    );

    items.add('key', this.keyField(keyOptional.includes(driver)), 20);

    return items;
  }

  keyField(optional: boolean): Mithril.Children {
    const hasSavedKey = this.hasSavedKey();

    return (
      <div className="Form-group">
        <label>{app.translator.trans('flarum-ai.admin.edit_connection.key_label')}</label>
        {optional && <div className="helpText">{app.translator.trans('flarum-ai.admin.edit_connection.key_optional_help')}</div>}
        <input
          className="FormControl"
          type="password"
          // Keeps the browser from filling in the admin's own password.
          autocomplete="new-password"
          bidi={this.key}
          placeholder={hasSavedKey && !this.removeKey ? app.translator.trans('flarum-ai.admin.edit_connection.key_saved_placeholder') : ''}
          spellcheck={false}
        />
        {hasSavedKey &&
          (this.removeKey ? (
            <div className="helpText">{app.translator.trans('flarum-ai.admin.edit_connection.key_removed_text')}</div>
          ) : (
            <Button className="Button Button--link" onclick={() => (this.removeKey = true)}>
              {app.translator.trans('flarum-ai.admin.edit_connection.remove_key_button')}
            </Button>
          ))}
      </div>
    );
  }

  /**
   * A saved key only carries over while the provider stays the same: it's no
   * use for another one.
   */
  hasSavedKey(): boolean {
    const connection = this.attrs.connection;

    return !!connection?.hasKey && connection.key === undefined && connection.driver === this.driver();
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    const name = this.name().trim();
    const driver = this.driver();
    const url = this.url().trim();
    const key = this.key().trim();
    const { keyOptional, urlRequired } = aiPayload();

    // The server checks all of this again; checking here keeps the admin in
    // the modal, next to what needs fixing.
    const problem = !/^[a-z0-9][a-z0-9_-]{0,49}$/i.test(name)
      ? 'flarum-ai.admin.connections.name_invalid_message'
      : !this.attrs.name && this.attrs.takenNames.includes(name)
        ? 'flarum-ai.admin.edit_connection.name_taken_message'
        : !url && urlRequired.includes(driver)
          ? 'flarum-ai.admin.connections.url_required_message'
          : !key && (this.removeKey || !this.hasSavedKey()) && !keyOptional.includes(driver)
            ? 'flarum-ai.admin.connections.key_required_message'
            : null;

    if (problem) {
      this.alertAttrs = { type: 'error', content: app.translator.trans(problem, { name }) };

      return;
    }

    const connection: Connection = { driver };

    if (url) connection.url = url;

    // Left without a key, the connection keeps the one saved for it.
    if (key) {
      connection.key = key;
    } else if (this.removeKey) {
      connection.key = '';
    } else {
      connection.hasKey = this.hasSavedKey();
    }

    this.attrs.onsave(name, connection);
    this.hide();
  }
}
