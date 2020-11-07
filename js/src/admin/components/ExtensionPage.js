import Alert from '../../common/components/Alert';
import Page from '../../common/components/Page';
import Button from '../../common/components/Button';
import icon from '../../common/helpers/icon';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import LoadingModal from './LoadingModal';
import ExtensionPermissionGrid from './ExtensionPermissionGrid';
import Switch from '../../common/components/Switch';
import saveSettings from '../utils/saveSettings';
import Stream from '../../common/utils/Stream';

export default class ExtensionPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = false;
    this.extension = app.data.extensions[this.attrs.id];
    this.changingState = false;
    this.settings = {};

    // Backwards compatibility layer will be removed in
    // Beta 16
    if (app.extensionSettings[this.extension.id]) {
      app.extensionData[this.extension.id] = app.extensionSettings[this.extension.id];
    }
  }

  className() {
    return this.extension.id + '-Page';
  }

  view() {
    return (
      <div className={'ExtensionPage ' + this.className()}>
        {this.header()}
        {!this.isEnabled() ? (
          <div className="container">
            <h2 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.enable_to_see')}</h2>
          </div>
        ) : (
          <div className="ExtensionPage-body">{listItems(this.sections().toArray())}</div>
        )}
      </div>
    );
  }

  content() {
    const settings = this.getContent('settings');

    if (settings) {
      const items = new ItemList();

      Object.keys(settings).map((key) => {
        const value = this.setting([key])();
        if (['bool', 'checkbox', 'switch', 'boolean'].indexOf(settings[key].type) >= 0) {
          items.add(
            key,
            <div className="Form-group">
              <Switch state={!!value && value !== '0'} onchange={this.settings[key]}>
                {settings[key].label}
              </Switch>
            </div>
          );
        } else {
          items.add(
            key,
            <div className="Form-group">
              <label>{settings[key].label}</label>
              <input type={settings[key].type} className="FormControl" bidi={this.setting(key)} />
            </div>
          );
        }
      });

      return items;
    }
  }

  sections() {
    const items = new ItemList();

    items.add('settings', [
      <div className="ExtensionPage-settings">
        <div className="container">
          {typeof app.extensionData[this.extension.id] === 'function' ? (
            <Button onclick={app.extensionData[this.extension.id].bind(this)} className="Button Button--primary">
              {app.translator.trans('core.admin.extension.open_modal')}
            </Button>
          ) : this.content() ? (
            <div className="Form">
              {this.content().toArray()}
              <div className="Form-group">{this.submitButton()}</div>
            </div>
          ) : (
            <h2 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h2>
          )}
        </div>
      </div>,
    ]);

    items.add('permissions', [
      <div className="ExtensionPage-permissions">
        <div className="ExtensionPage-permissions-header">
          <h2 className="ExtensionTitle">{app.translator.trans('core.admin.extension.permissions_title')}</h2>
        </div>
        <div className="container">
          {this.getContent('permissions') ? (
            ExtensionPermissionGrid.component({ extensionId: this.extension.id })
          ) : (
            <h2 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_permissions')}</h2>
          )}
        </div>
      </div>,
    ]);

    return items;
  }

  header() {
    return [
      <div className="ExtensionPage-header">
        <div className="container">
          <h2 className="ExtensionTitle">
            <span className="ExtensionIcon" style={this.extension.icon}>
              {this.extension.icon ? icon(this.extension.icon.name) : ''}
            </span>
            {this.extension.extra['flarum-extension'].title}
            <span>{this.extension.version}</span>
          </h2>
          <aside className="ExtensionInfo">
            <ul>{listItems(this.infoItems().toArray())}</ul>
          </aside>
          <div className="helpText">{this.extension.description}</div>
          <div className="ExtensionPage-headerItems">
            {Switch.component(
              {
                state: this.isEnabled(),
                onchange: this.toggle.bind(this, this.extension.id),
              },
              this.isEnabled(this.extension.id)
                ? app.translator.trans('core.admin.extension.enabled')
                : app.translator.trans('core.admin.extension.disabled')
            )}
            <div className="ExtensionPage-headerActionItems">
              <ul>{listItems(this.actionItems().toArray())}</ul>
            </div>
          </div>
        </div>
      </div>,
    ];
  }

  actionItems() {
    const items = new ItemList();

    items.add(
      'uninstall',
      Button.component(
        {
          icon: 'far fa-trash-alt',
          onclick: () => {
            app
              .request({
                url: app.forum.attribute('apiUrl') + '/extensions/' + this.extension.id,
                method: 'DELETE',
              })
              .then(() => window.location.reload());

            app.modal.show(LoadingModal);
          },
        },
        app.translator.trans('core.admin.extension.uninstall_button')
      )
    );

    return items;
  }

  infoItems() {
    const items = new ItemList();

    if (this.extension.authors) {
      let authors = [];

      Object.keys(this.extension.authors).map((author) => {
        authors.push(this.extension.authors[author].name);
      });

      items.add('authors', [icon('fas fa-user'), <span>{authors.join(', ')}</span>]);
    }

    if (this.extension.source || this.extension.support) {
      items.add(
        'source',
        Button.component(
          {
            href: this.extension.source ? this.extension.source.url : this.extension.support.source,
            icon: 'fas fa-code',
          },
          app.translator.trans('core.admin.extension.source')
        )
      );
    }

    return items;
  }

  isEnabled() {
    const enabled = JSON.parse(app.data.settings.extensions_enabled);

    let isEnabled = enabled.indexOf(this.extension.id) !== -1;

    if (this.changingState) {
      return !isEnabled;
    } else {
      return isEnabled;
    }
  }

  toggle() {
    const enabled = this.isEnabled();

    this.changingState = true;

    app
      .request({
        url: app.forum.attribute('apiUrl') + '/extensions/' + this.extension.id,
        method: 'PATCH',
        body: { enabled: !enabled },
      })
      .then(() => {
        if (!enabled) localStorage.setItem('enabledExtension', this.extension.id);
        window.location.reload();
      });

    app.modal.show(LoadingModal);
  }

  submitButton() {
    return (
      <Button onclick={this.saveSettings.bind(this)} className="Button Button--primary" loading={this.loading} disabled={!this.changed()}>
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>
    );
  }

  dirty() {
    const dirty = {};

    Object.keys(this.settings).forEach((key) => {
      const value = this.settings[key];

      if (value !== app.data.settings[key]) {
        dirty[key] = value;
      }
    });

    return dirty;
  }

  changed() {
    return Object.keys(this.dirty()).length;
  }

  saveSettings(e) {
    e.preventDefault();

    app.alerts.clear();

    this.loading = true;

    saveSettings(this.dirty()).then(this.onsaved.bind(this));
  }

  onsaved() {
    this.loading = false;

    app.alerts.show(
      new Alert(
        {
          type: 'success',
        },
        app.translator.trans('core.admin.extension.saved_message')
      )
    );
  }

  setting(key, fallback = '') {
    this.settings[key] = this.settings[key] || Stream(app.data.settings[key] || fallback);

    return this.settings[key];
  }

  getContent(type) {
    if (app.extensionData[this.extension.id] && app.extensionData[this.extension.id][type]) {
      return app.extensionData[this.extension.id][type];
    }

    return false;
  }
}
