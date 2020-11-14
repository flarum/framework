import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import LinkButton from '../../common/components/LinkButton';
import Page from '../../common/components/Page';
import Select from '../../common/components/Select';
import Switch from '../../common/components/Switch';
import icon from '../../common/helpers/icon';
import punctuateSeries from '../../common/helpers/punctuateSeries';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import LoadingModal from './LoadingModal';
import ExtensionPermissionGrid from './ExtensionPermissionGrid';
import saveSettings from '../utils/saveSettings';
import ExtensionData from '../utils/ExtensionData';
import extensionEnabled from '../utils/extensionEnabled';

export default class ExtensionPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = false;
    this.extension = app.data.extensions[this.attrs.id];
    this.changingState = false;
    this.settings = {};

    this.infoFields = {
      discuss: 'fas fa-comment-alt',
      documentation: 'fas fa-book',
      support: 'fas fa-life-ring',
      website: 'fas fa-link',
      donate: 'fas fa-donate',
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

  header() {
    return [
      <div className="ExtensionPage-header">
        <div className="container">
          <div className="ExtensionTitle">
            <span className="ExtensionIcon" style={this.extension.icon}>
              {this.extension.icon ? icon(this.extension.icon.name) : ''}
            </span>
            <div className="ExtensionName">
              <h2>{this.extension.extra['flarum-extension'].title}</h2>
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

  sections() {
    const items = new ItemList();

    items.add('content', this.content());

    items.add('permissions', [
      <div className="ExtensionPage-permissions">
        <div className="ExtensionPage-permissions-header">
          <div className="container">
            <h2 className="ExtensionTitle">{app.translator.trans('core.admin.extension.permissions_title')}</h2>
          </div>
        </div>
        <div className="container">
          {app.extensionData.extensionHasPermissions(this.extension.id) ? (
            ExtensionPermissionGrid.component({ extensionId: this.extension.id })
          ) : (
            <h2 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_permissions')}</h2>
          )}
        </div>
      </div>,
    ]);

    return items;
  }

  content() {
    const settings = app.extensionData.getSettings(this.extension.id);

    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          {typeof app.extensionData[this.extension.id] === 'function' ? (
            <Button onclick={app.extensionData[this.extension.id].bind(this)} className="Button Button--primary">
              {app.translator.trans('core.admin.extension.open_modal')}
            </Button>
          ) : settings ? (
            <div className="Form">
              {this.getSettings(settings)}
              <div className="Form-group">{this.submitButton()}</div>
            </div>
          ) : (
            <h2 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h2>
          )}
        </div>
      </div>
    );
  }

  topItems() {
    const items = new ItemList();

    items.add('version', <span className="ExtensionVersion">{this.extension.version}</span>);

    if (!this.isEnabled()) {
      const uninstall = () => {
        if (confirm(app.translator.trans('core.admin.extension.confirm_uninstall'))) {
          app
            .request({
              url: app.forum.attribute('apiUrl') + '/extensions/' + this.extension.id,
              method: 'DELETE',
            })
            .then(() => window.location.reload());

          app.modal.show(LoadingModal);
        }
      };

      items.add(
        'uninstall',
        <Button icon="fas fa-trash-alt" className="Button Button--primary" onclick={uninstall.bind(this)}>
          {app.translator.trans('core.admin.extension.uninstall_button')}
        </Button>
      );
    }

    return items;
  }

  infoItems() {
    const items = new ItemList();

    if (this.extension.authors) {
      let authors = [];

      Object.keys(this.extension.authors).map((author, i) => {
        const link = this.extension.authors[author].homepage
          ? this.extension.authors[author].homepage
          : 'mailto:' + this.extension.authors[author].email;

        authors.push(
          <Link href={link} external={true} target="_blank">
            {this.extension.authors[author].name}
          </Link>
        );
      });

      items.add('authors', [icon('fas fa-user'), <span>{punctuateSeries(authors)}</span>]);
    }

    const infoData = {};

    if (this.extension.source || this.extension.support) {
      infoData.source = {
        icon: 'fas fa-code',
        href: this.extension.source ? this.extension.source.url : this.extension.support.source,
      };
    }

    Object.keys(this.infoFields).map((field) => {
      if (this.extension.extra['flarum-extension'].info && this.extension.extra['flarum-extension'].info[field]) {
        infoData[field] = {
          icon: this.infoFields[field],
          href: this.extension.extra['flarum-extension'].info[field],
        };
      }
    });

    Object.entries(infoData).map(([field, value]) => {
      items.add(
        field,
        LinkButton.component(
          {
            href: value.href,
            icon: value.icon,
            external: true,
            target: '_blank',
          },
          app.translator.trans(`core.admin.extension.info_links.${field}`)
        )
      );
    });

    return items;
  }

  submitButton() {
    return (
      <Button onclick={this.saveSettings.bind(this)} className="Button Button--primary" loading={this.loading} disabled={!this.changed()}>
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>
    );
  }

  /**
   * getSettings accepts an array of settings.
   * Depending on the type of input, you can set the type to 'bool', 'select', or
   * any standard <input> type.
   *
   * @example
   * [
   *   {
   *      setting: 'acme.checkbox',
   *      label: app.translator.trans('acme.admin.setting_label'),
   *      type: 'bool'
   *   }
   * ]
   *
   * @example
   * [
   *   {
   *      setting: 'acme.select',
   *      label: app.translator.trans('acme.admin.setting_label'),
   *      type: 'select',
   *      options: {
   *        'option1': 'Option 1 label',
   *        'option2': 'Option 2 label',
   *      },
   *      default: 'option1',
   *   }
   * ]
   *
   * @param settings
   * @returns {JSX.Element}
   */
  getSettings(settings) {
    return settings.map((entry) => {
      const setting = entry.setting;
      const value = this.setting([setting])();
      if (['bool', 'checkbox', 'switch', 'boolean'].includes(entry.type)) {
        return (
          <div className="Form-group">
            <Switch state={!!value && value !== '0'} onchange={this.settings[setting]}>
              {entry.label}
            </Switch>
          </div>
        );
      } else if (['select', 'dropdown', 'selectdropdown'].includes(entry.type)) {
        return (
          <div className="Form-group">
            <label>{entry.label}</label>
            <Select value={value || entry.default} options={entry.options} buttonClassName="Button" onchange={this.settings[setting]} />
          </div>
        );
      } else {
        return (
          <div className="Form-group">
            <label>{entry.label}</label>
            <input type={entry.type} className="FormControl" bidi={this.setting(setting)} />
          </div>
        );
      }
    });
  }

  toggle() {
    const enabled = this.isEnabled();

    this.changingState = true;

    app
      .request({
        url: app.forum.attribute('apiUrl') + '/extensions/' + this.extension.id,
        method: 'PATCH',
        body: { enabled: !enabled },
        errorHandler: this.onerror.bind(this),
      })
      .then(() => {
        if (!enabled) localStorage.setItem('enabledExtension', this.extension.id);
        window.location.reload();
      });

    app.modal.show(LoadingModal);
  }

  dirty() {
    const dirty = {};

    Object.keys(this.settings).forEach((key) => {
      const value = this.settings[key]();

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

    app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.extension.saved_message'));
  }

  setting(key, fallback = '') {
    this.settings[key] = this.settings[key] || Stream(app.data.settings[key] || fallback);

    return this.settings[key];
  }

  isEnabled() {
    let isEnabled = extensionEnabled(this.extension.id);

    return this.changingState ? !isEnabled : isEnabled;
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
      { type: 'error' },
      app.translator.trans(`core.lib.error.${error.code}_message`, {
        extension: error.extension,
        extensions: error.extensions.join(', '),
      })
    );
  }
}
