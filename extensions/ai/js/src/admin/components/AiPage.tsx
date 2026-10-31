import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import Select from 'flarum/common/components/Select';
import type Mithril from 'mithril';
import EditConnectionModal from './EditConnectionModal';
import {
  aiPayload,
  CONNECTIONS_SETTING,
  type Connection,
  type Connections,
  DEFAULT_SETTING,
  driverLabel,
  parseConnections,
  serializeConnections,
  withoutKeys,
} from '../connections';

export default class AiPage extends ExtensionPage {
  connections!: Connections;

  oninit(vnode: Mithril.Vnode<any, this>) {
    super.oninit(vnode);

    this.connections = parseConnections(this.setting(CONNECTIONS_SETTING)());
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
            <div className="Form-group Form-controls">{this.submitButton()}</div>
          </Form>
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
        {!!connection.url && <code className="AiPage-connection-url">{connection.url}</code>}
        <span className="AiPage-connection-key">{app.translator.trans(keyText)}</span>
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
    const { lockedDefault, lockedConnections } = aiPayload();

    if (lockedDefault) {
      return (
        <div className="Form-group">
          <label>{app.translator.trans('flarum-ai.admin.connections.default_label')}</label>
          <div className="helpText">{app.translator.trans('flarum-ai.admin.connections.default_locked_text', { name: lockedDefault })}</div>
        </div>
      );
    }

    const options: Record<string, Mithril.Children> = { '': app.translator.trans('flarum-ai.admin.connections.default_none_option') };

    [...Object.keys(lockedConnections), ...Object.keys(this.connections)].forEach((name) => (options[name] = name));

    const value = this.setting(DEFAULT_SETTING)();

    return (
      <div className="Form-group">
        <label>{app.translator.trans('flarum-ai.admin.connections.default_label')}</label>
        <div className="helpText">{app.translator.trans('flarum-ai.admin.connections.default_help')}</div>
        <Select options={options} value={value in options ? value : ''} onchange={this.setting(DEFAULT_SETTING)} />
      </div>
    );
  }

  edit(name?: string) {
    app.modal.show(EditConnectionModal, {
      name,
      connection: name ? this.connections[name] : undefined,
      takenNames: [...Object.keys(this.connections), ...Object.keys(aiPayload().lockedConnections)].filter((other) => other !== name),
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

    this.sync();
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
