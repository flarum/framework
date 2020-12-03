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
import isExtensionEnabled from '../utils/isExtensionEnabled';

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
      source: 'fas fa-code',
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
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.enable_to_see')}</h3>
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
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_permissions')}</h3>
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
              {settings.map(this.buildSettingComponent.bind(this))}
              <div className="Form-group">{this.submitButton()}</div>
            </div>
          ) : (
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h3>
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

    const links = this.extension.links;

    if (links.authors.length) {
      let authors = [];

      links.authors.map((author) => {
        authors.push(
          <Link href={author.link} external={true} target="_blank">
            {author.name}
          </Link>
        );
      });

      items.add('authors', [icon('fas fa-user'), <span>{punctuateSeries(authors)}</span>]);
    }

    Object.keys(this.infoFields).map((field) => {
      if (links[field]) {
        items.add(
          field,
          <LinkButton href={links[field]} icon={this.infoFields[field]} external={true} target="_blank">
            {app.translator.trans(`core.admin.extension.info_links.${field}`)}
          </LinkButton>
        );
      }
    });

    return items;
  }

  submitButton() {
    return (
      <Button onclick={this.saveSettings.bind(this)} className="Button Button--primary" loading={this.loading} disabled={!this.isChanged()}>
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>
    );
  }

  /**
   * getSetting takes a settings object and turns it into a component.
   * Depending on the type of input, you can set the type to 'bool', 'select', or
   * any standard <input> type.
   *
   * Alternatively, you can pass a callback that will be executed in ExtensionPage's
   * context to include custom JSX elements.
   *
   * @example
   *
   * {
   *    setting: 'acme.checkbox',
   *    label: app.translator.trans('acme.admin.setting_label'),
   *    type: 'bool'
   * }
   *
   * @example
   *
   * {
   *    setting: 'acme.select',
   *    label: app.translator.trans('acme.admin.setting_label'),
   *    type: 'select',
   *    options: {
   *      'option1': 'Option 1 label',
   *      'option2': 'Option 2 label',
   *    },
   *    default: 'option1',
   * }
   *
   * @param setting
   * @returns {JSX.Element}
   */
  buildSettingComponent(entry) {
    if (typeof entry === 'function') {
      return entry.call(this);
    }

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

  isChanged() {
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
    let isEnabled = isExtensionEnabled(this.extension.id);

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
