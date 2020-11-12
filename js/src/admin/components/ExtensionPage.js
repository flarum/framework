import Alert from '../../common/components/Alert';
import Button from '../../common/components/Button';
import LinkButton from "../../common/components/LinkButton";
import Page from '../../common/components/Page';
import Select from '../../common/components/Select';
import Switch from '../../common/components/Switch';
import icon from '../../common/helpers/icon';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import LoadingModal from './LoadingModal';
import ExtensionPermissionGrid from './ExtensionPermissionGrid';
import saveSettings from '../utils/saveSettings';

export default class ExtensionPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = false;
    this.extension = app.data.extensions[this.attrs.id];
    this.changingState = false;
    this.settings = {};

    this.infoFields = {
      'discuss': 'fas fa-comment-alt',
      'documentation': 'fas fa-book',
      'support': 'fas fa-life-ring',
      'website': 'fas fa-link',
      'donate': 'fas fa-donate',
    };

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
          <div className="ExtensionPage-body">{this.sections().toArray()}</div>
        )}
      </div>
    );
  }

  content() {
    const settings = this.getContent('settings');

    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          {typeof app.extensionData[this.extension.id] === 'function' ? (
            <Button onclick={app.extensionData[this.extension.id].bind(this)} className="Button Button--primary">
              {app.translator.trans('core.admin.extension.open_modal')}
            </Button>
          ) : settings ? this.getSettings(settings)
            : (
              <h2 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h2>
            )}
        </div>
      </div>
    )
  }

  sections() {
    const items = new ItemList();

    items.add('content', this.content());

    items.add('permissions', [
      <div className="ExtensionPage-permissions">
        <div className="ExtensionPage-permissions-header">
          <h2 className="ExtensionTitle">{app.translator.trans('core.admin.extension.permissions_title')}</h2>
        </div>
        <div className="container">
          {this.getContent('permissions') ? (
            ExtensionPermissionGrid.component({extensionId: this.extension.id})
          ) : (
            <h2 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_permissions')}</h2>
          )}
        </div>
      </div>,
    ]);

    return items;
  }

  getSettings(settings) {
    return (
      <div className="Form">
        {Object.keys(settings).map((key) => {
          const value = this.setting([key])();
          if (['bool', 'checkbox', 'switch', 'boolean'].includes(settings[key].type)) {
            return (
              <div className="Form-group">
                <Switch state={!!value && value !== '0'} onchange={this.settings[key]}>
                  {settings[key].label}
                </Switch>
              </div>
            );
          } else if (['select', 'dropdown', 'selectdropdown'].includes(settings[key].type)) {
            return (
              <div className="Form-group">
                <label>{settings[key].label}</label>
                <Select
                  value={value || settings[key].default}
                  options={settings[key].options}
                  buttonClassName="Button"
                  onchange={this.settings[key]}
                />
              </div>
            );
          } else {
            return (
              <div className="Form-group">
                <label>{settings[key].label}</label>
                <input type={settings[key].type} className="FormControl" bidi={this.setting(key)}/>
              </div>
            );
          }
        })}
        <div className="Form-group">{this.submitButton()}</div>
      </div>
    );
  }

  header() {
    return [
      <div className="ExtensionPage-header">
        <div className="container">
          <div className="ExtensionTitle">
            <span className="ExtensionIcon" style={this.extension.icon}>
              {this.extension.icon ? icon(this.extension.icon.name) : ''}
            </span>
            <div className="ExtensionName">
              <label>{this.extension.extra['flarum-extension'].title}</label>
            </div>
            <div className="ExtensionPage-headerTopItems">
              <ul>{listItems(this.topItems().toArray())}</ul>
            </div>
          </div>
          <div className="helpText">{this.extension.description}</div>
          <div className="ExtensionPage-headerItems">
            <Switch state={this.isEnabled()} onchange={this.toggle.bind(this, this.extension.id)}>
              {this.isEnabled(this.extension.id)
                ? app.translator.trans('core.admin.extension.enabled')
                : app.translator.trans('core.admin.extension.disabled')}
            </Switch>
            <aside className="ExtensionInfo">
              <ul>{listItems(this.infoItems().toArray())}</ul>
            </aside>
          </div>
        </div>
      </div>,
    ];
  }

  topItems() {
    const items = new ItemList();

    items.add(
      'version',
      <span className="ExtensionVersion">{this.extension.version}</span>
    )

    if (!this.isEnabled()) {
      items.add(
        'uninstall',
        <Button icon='fas fa-trash-alt' className="Button Button--primary" onclick={() => {
          if (confirm(app.translator.trans('core.admin.extension.confirm_uninstall'))) {
            app
              .request({
                url: app.forum.attribute('apiUrl') + '/extensions/' + this.extension.id,
                method: 'DELETE',
              })
              .then(() => window.location.reload());

            app.modal.show(LoadingModal);
          }
        }
        }
        >
          {app.translator.trans('core.admin.extension.uninstall_button')}
        </Button>
      )
    }

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
        LinkButton.component(
          {
            href: this.extension.source ? this.extension.source.url : this.extension.support.source,
            icon: 'fas fa-code',
            external: true,
            target: '_blank',
          },
          app.translator.trans('core.admin.extension.info_links.source')
        )
      );
    }

    if (this.extension.extra['flarum-extension'].info) {

      Object.keys(this.infoFields).map(field => {
        if (this.extension.extra['flarum-extension'].info[field]) {
          items.add(
            field,
            LinkButton.component(
              {
                href: this.extension.extra['flarum-extension'].info[field],
                icon: this.infoFields[field],
                external: true,
                target: '_blank',
              },
              app.translator.trans(`core.admin.extension.info_links.${field}`)
            )
          );
        }
      });
    }

    return items;
  }

  isEnabled() {
    const enabled = JSON.parse(app.data.settings.extensions_enabled);

    let isEnabled = enabled.includes(this.extension.id);

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
        body: {enabled: !enabled},
        errorHandler: this.onerror.bind(this),
      })
      .then(() => {
        if (!enabled) localStorage.setItem('enabledExtension', this.extension.id);
        window.location.reload();
      });

    app.modal.show(LoadingModal);
  }

  submitButton() {
    return (
      <Button onclick={this.saveSettings.bind(this)} className="Button Button--primary" loading={this.loading}
              disabled={!this.changed()}>
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

    app.alerts.show({type: 'success'}, app.translator.trans('core.admin.extension.saved_message'));
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

  onerror(e) {
    // We need to give the modal animation time to start; if we close the modal too early,
    // it breaks the bootstrap modal library.
    // TODO: This workaround should be removed when we move away from bootstrap JS for modals.
    setTimeout(() => {
      app.modal.close();
    }, 300); // Bootstrap's Modal.TRANSITION_DURATION is 300 ms.

    if (e.status !== 409) {
      throw e;
    }

    const error = e.response.errors[0];

    app.alerts.show(
      {type: 'error'},
      app.translator.trans(`core.lib.error.${error.code}_message`, {
        extension: error.extension,
        extensions: error.extensions.join(', '),
      })
    );
  }
}
