import Page from '../../common/components/Page';
import Button from '../../common/components/Button';
import Switch from '../../common/components/Switch';
import Select from '../../common/components/Select';
import classList from '../../common/utils/classList';
import Stream from '../../common/utils/Stream';
import saveSettings from '../utils/saveSettings';
import AdminHeader from './AdminHeader';
import type Mithril from 'mithril';

interface AdminHeaderOptions {
  title: string;
  description: string;
  icon: string;
  /**
   * Will be used as the class for the AdminPage.
   *
   * Will also be appended with `-header` and set as the class for the `AdminHeader` component.
   */
  className: string;
}

type HTMLInputTypes =
  | 'button'
  | 'checkbox'
  | 'color'
  | 'date'
  | 'datetime-local'
  | 'email'
  | 'file'
  | 'hidden'
  | 'image'
  | 'month'
  | 'number'
  | 'password'
  | 'radio'
  | 'range'
  | 'reset'
  | 'search'
  | 'submit'
  | 'tel'
  | 'text'
  | 'time'
  | 'url'
  | 'week';

interface CommonSettingsItemOptions extends Mithril.Attributes {
  setting: string;
  label: string | ReturnType<typeof app.translator.trans>;
  help?: string | ReturnType<typeof app.translator.trans>;
  className?: string;
}

interface HTMLInputSettingsComponentOptions extends CommonSettingsItemOptions {
  /**
   * Any valid HTML input `type` value.
   */
  type: HTMLInputTypes;
}

interface SwitchSettingComponentOptions extends CommonSettingsItemOptions {
  type: 'bool' | 'checkbox' | 'switch' | 'boolean';
}

interface SelectSettingComponentOptions extends CommonSettingsItemOptions {
  type: 'select' | 'dropdown' | 'selectdropdown';
  /**
   * Map of values to their labels
   */
  options: { [value: string]: string | ReturnType<typeof app.translator.trans> };
  default: string;
}

export type SettingsComponentOptions = HTMLInputSettingsComponentOptions | SwitchSettingComponentOptions | SelectSettingComponentOptions;

export type AdminHeaderAttrs = AdminHeaderOptions & Partial<Omit<Mithril.Attributes, 'class'>>;

export default class AdminPage extends Page {
  settings: Record<string, () => string> = {};
  loading: boolean = false;

  oninit(vnode: Mithril.Vnode<Record<string, unknown>, this>) {
    super.oninit(vnode);
  }

  view(vnode: Mithril.Vnode<Record<string, unknown>, this>) {
    const className = classList('AdminPage', this.headerInfo().className);

    return (
      <div className={className}>
        {this.header(vnode)}
        <div className="container">{this.content(vnode)}</div>
      </div>
    );
  }

  /**
   * Returns the content of the AdminPage.
   */
  content(vnode: Mithril.Vnode<Record<string, unknown>, this>): Mithril.Children {
    return '';
  }

  /**
   * Returns the submit button for this AdminPage.
   *
   * Calls `this.saveSettings` when the button is clicked.
   */
  submitButton(vnode: Mithril.Vnode<Record<string, unknown>, this>): Mithril.Children {
    return (
      <Button onclick={this.saveSettings.bind(this)} className="Button Button--primary" loading={this.loading} disabled={!this.isChanged()}>
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>
    );
  }

  /**
   * Returns the Header component for this AdminPage.
   */
  header(vnode: Mithril.Vnode<Record<string, unknown>, this>): Mithril.Children {
    const { title, className, ...headerAttrs } = this.headerInfo();

    return (
      <AdminHeader className={className ? `${className}-header` : undefined} {...headerAttrs}>
        {title}
      </AdminHeader>
    );
  }

  /**
   * Returns the options passed to the AdminHeader component.
   */
  headerInfo(): AdminHeaderAttrs {
    return {
      className: '',
      icon: '',
      title: '',
      description: '',
    };
  }

  /**
   * `buildSettingComponent` takes a settings object and turns it into a component.
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
   * @example
   *
   * () => {
   *   return <p>My cool component</p>;
   * }
   */
  buildSettingComponent(entry: ((this: typeof this) => Mithril.Children) | SettingsComponentOptions): Mithril.Children {
    if (typeof entry === 'function') {
      return entry.call(this);
    }

    const { setting, help, type, label, ...componentAttrs } = entry;

    const value = this.setting(setting)();

    if (['bool', 'checkbox', 'switch', 'boolean'].includes(type)) {
      return (
        <div className="Form-group">
          <Switch state={!!value && value !== '0'} onchange={this.settings[setting]} {...componentAttrs}>
            {label}
          </Switch>
          <div className="helpText">{help}</div>
        </div>
      );
    } else if (['select', 'dropdown', 'selectdropdown'].includes(type)) {
      const { default: defaultValue, options, ...otherAttrs } = componentAttrs;

      return (
        <div className="Form-group">
          <label>{label}</label>
          <div className="helpText">{help}</div>
          <Select value={value || defaultValue} options={options} onchange={this.settings[setting]} {...otherAttrs} />
        </div>
      );
    } else {
      componentAttrs.className = classList(['FormControl', componentAttrs.className]);

      return (
        <div className="Form-group">
          {label ? <label>{label}</label> : ''}
          <div className="helpText">{help}</div>
          <input type={type} bidi={this.setting(setting)} {...componentAttrs} />
        </div>
      );
    }
  }

  /**
   * Called when `saveSettings` completes successfully.
   */
  onsaved(): void {
    this.loading = false;

    app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.settings.saved_message'));
  }

  /**
   * Returns a function that fetches the setting from the `app` global.
   */
  setting(key: string, fallback: string = ''): () => string {
    this.settings[key] = this.settings[key] || Stream<string>(app.data.settings[key] || fallback);

    return this.settings[key];
  }

  /**
   * Returns a list of settings that have been modified, but not yet saved.
   */
  dirty(): Record<string, string> {
    const dirty: Record<string, string> = {};

    Object.keys(this.settings).forEach((key) => {
      const value = this.settings[key]();

      if (value !== app.data.settings[key]) {
        dirty[key] = value;
      }
    });

    return dirty;
  }

  /**
   * Returns the number of settings that have been modified.
   */
  isChanged(): number {
    return Object.keys(this.dirty()).length;
  }

  /**
   * Saves the modified settings to the database.
   */
  saveSettings(e: SubmitEvent & { redraw: boolean }) {
    e.preventDefault();

    app.alerts.clear();

    this.loading = true;

    return saveSettings(this.dirty()).then(this.onsaved.bind(this));
  }
}
