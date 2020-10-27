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

export default class ExtensionPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = false;
    this.extension = app.data.extensions[this.attrs.id];
    this.changingState = false;

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
          <div className="ExtensionPage-body">
            {listItems(this.sections().toArray())}
          </div>
        )}
      </div>
    );
  }

  content() {
    if (this.contentAvalible('settings')) {
      return app.extensionData[this.extension.id].settings;
    }
    return '';
  }

  sections() {
    const items = new ItemList();


    items.add('settings',
      [
        <div className="ExtensionPage-settings">
          <div className="container">
            {this.content() ? (
              <div className="Form">
                {this.content()}

                <div className="Form-group">{this.submitButton()}</div>
              </div>
            ) : typeof app.extensionData[this.extension.id] === 'function' ? (
              <Button onclick={app.extensionData[this.extension.id].bind(this)} className="Button Button--primary">
                {app.translator.trans('core.admin.extension.open_modal')}
              </Button>
            ) : (
              <h2 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h2>
            )}
          </div>
        </div>
      ]
    )

    items.add('permissions',
      [
        <div className="ExtensionPage-permissions">
          <div className="ExtensionPage-permissions-header">
            <h2 className="ExtensionTitle">{app.translator.trans('core.admin.extension.permissions_title')}</h2>
          </div>
          <div className="container">
            {this.contentAvalible('permissions') ? (
              ExtensionPermissionGrid.component({ extensionId: this.extension.id })
            ) : (
              <h2 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_permissions')}</h2>
            )}
          </div>
        </div>
      ]
    )

    return items;
  }

  header() {
    return [
      <div className="ExtensionPage-header">
        <div className="container">
          <div className="ExtensionPage-headerItems">
            <ul>{listItems(this.headerItems().toArray())}</ul>
          </div>
          <h2 className="ExtensionTitle">
              <span className="ExtensionIcon" style={this.extension.icon}>
                {this.extension.icon ? icon(this.extension.icon.name) : ''}
              </span>
            {this.extension.extra['flarum-extension'].title}
            <span>{this.extension.version}</span>
          </h2>
          <div className="helpText">{this.extension.description}</div>
          <div className="ExtensionPage-actionItems">
            <aside className="ExtensionInfo">
              <ul>{listItems(this.infoItems().toArray())}</ul>
            </aside>
            {Switch.component(
              {
                state: this.isEnabled(),
                onchange: this.toggle.bind(this, this.extension.id),
              },
              this.isEnabled(this.extension.id)
                ? app.translator.trans('core.admin.extension.enabled')
                : app.translator.trans('core.admin.extension.disabled')
            )}
          </div>
        </div>
      </div>
    ]
  }

  headerItems() {
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

  oncreate(vnode) {
    super.oncreate(vnode)

    app.pendingSettings = {};
  }

  dirty() {
    const dirty = {};

    Object.keys(app.pendingSettings).forEach((key) => {
      const value = app.pendingSettings[key]();

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

  contentAvalible(type) {
    return app.extensionData[this.extension.id] && app.extensionData[this.extension.id][type];
  }
}
