import Page from '../../common/components/Page';
import Button from '../../common/components/Button';
import Switch from '../../common/components/Switch';
import Select from '../../common/components/Select';
import classList from '../../common/utils/classList';
import Stream from '../../common/utils/Stream';
import saveSettings from '../utils/saveSettings';
import AdminHeader from './AdminHeader';

export default class AdminPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    this.settings = {};

    this.loading = false;
  }

  view() {
    const className = classList(['AdminPage', this.headerInfo().className]);

    return (
      <div className={className}>
        {this.header()}
        <div className="container">{this.content()}</div>
      </div>
    );
  }

  content() {
    return '';
  }

  submitButton() {
    return (
      <Button onclick={this.saveSettings.bind(this)} className="Button Button--primary" loading={this.loading} disabled={!this.isChanged()}>
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>
    );
  }

  header() {
    const headerInfo = this.headerInfo();

    return (
      <AdminHeader icon={headerInfo.icon} description={headerInfo.description} className={headerInfo.className + '-header'}>
        {headerInfo.title}
      </AdminHeader>
    );
  }

  headerInfo() {
    return {
      className: '',
      icon: '',
      title: '',
      description: '',
    };
  }

  /**
   * buildSettingComponent takes a settings object and turns it into a component.
   * Depending on the type of input, you can set the type to 'bool', 'select', or
   * any standard <input> type. Any values inside the 'extra' object will be added
   * to the component as an attribute.
   *
   * Alternatively, you can pass a callback that will be executed in ExtensionPage's
   * context to include custom JSX elements.
   *
   * @example
   *
   * {
   *    setting: 'acme.checkbox',
   *    label: app.translator.trans('acme.admin.setting_label'),
   *    type: 'bool',
   *    help: app.translator.trans('acme.admin.setting_help'),
   *    className: 'Setting-item'
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
    const help = entry.help;
    delete entry.help;

    const value = this.setting([setting])();
    if (['bool', 'checkbox', 'switch', 'boolean'].includes(entry.type)) {
      return (
        <div className="Form-group">
          <Switch state={!!value && value !== '0'} onchange={this.settings[setting]} {...entry}>
            {entry.label}
          </Switch>
          <div className="helpText">{help}</div>
        </div>
      );
    } else if (['select', 'dropdown', 'selectdropdown'].includes(entry.type)) {
      return (
        <div className="Form-group">
          <label>{entry.label}</label>
          <div className="helpText">{help}</div>
          <Select value={value || entry.default} options={entry.options} buttonClassName="Button" onchange={this.settings[setting]} {...entry} />
        </div>
      );
    } else {
      entry.className = classList(['FormControl', entry.className]);
      return (
        <div className="Form-group">
          {entry.label ? <label>{entry.label}</label> : ''}
          <div className="helpText">{help}</div>
          <input type={entry.type} bidi={this.setting(setting)} {...entry} />
        </div>
      );
    }
  }

  onsaved() {
    this.loading = false;

    app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.settings.saved_message'));
  }

  setting(key, fallback = '') {
    this.settings[key] = this.settings[key] || Stream(app.data.settings[key] || fallback);

    return this.settings[key];
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

    return saveSettings(this.dirty()).then(this.onsaved.bind(this));
  }
}
