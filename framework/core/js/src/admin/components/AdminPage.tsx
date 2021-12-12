import type Mithril from 'mithril';

import app from '../app';
import Page, { IPageAttrs } from '../../common/components/Page';
import Button from '../../common/components/Button';
import Switch from '../../common/components/Switch';
import Select from '../../common/components/Select';
import classList from '../../common/utils/classList';
import Stream from '../../common/utils/Stream';
import saveSettings from '../utils/saveSettings';
import AdminHeader from './AdminHeader';
import generateElementId from '../utils/generateElementId';
import ColorPreviewInput from '../../common/components/ColorPreviewInput';

export interface AdminHeaderOptions {
  title: Mithril.Children;
  description: Mithril.Children;
  icon: string;
  /**
   * Will be used as the class for the AdminPage.
   *
   * Will also be appended with `-header` and set as the class for the `AdminHeader` component.
   */
  className: string;
}

/**
 * A type that matches any valid value for the `type` attribute on an HTML `<input>` element.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#attr-type
 *
 * Note: this will be exported from a different location in the future.
 *
 * @see https://github.com/flarum/core/issues/3039
 */
export type HTMLInputTypes =
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
  label: Mithril.Children;
  help?: Mithril.Children;
  className?: string;
}

/**
 * Valid options for the setting component builder to generate an HTML input element.
 */
export interface HTMLInputSettingsComponentOptions extends CommonSettingsItemOptions {
  /**
   * Any valid HTML input `type` value.
   */
  type: HTMLInputTypes;
}

const BooleanSettingTypes = ['bool', 'checkbox', 'switch', 'boolean'] as const;
const SelectSettingTypes = ['select', 'dropdown', 'selectdropdown'] as const;
const TextareaSettingTypes = ['textarea'] as const;
const ColorPreviewSettingType = 'color-preview';

/**
 * Valid options for the setting component builder to generate a Switch.
 */
export interface SwitchSettingComponentOptions extends CommonSettingsItemOptions {
  type: typeof BooleanSettingTypes[number];
}

/**
 * Valid options for the setting component builder to generate a Select dropdown.
 */
export interface SelectSettingComponentOptions extends CommonSettingsItemOptions {
  type: typeof SelectSettingTypes[number];
  /**
   * Map of values to their labels
   */
  options: { [value: string]: Mithril.Children };
  default: string;
}

/**
 * Valid options for the setting component builder to generate a Textarea.
 */
export interface TextareaSettingComponentOptions extends CommonSettingsItemOptions {
  type: typeof TextareaSettingTypes[number];
}

/**
 * Valid options for the setting component builder to generate a ColorPreviewInput.
 */
export interface ColorPreviewSettingComponentOptions extends CommonSettingsItemOptions {
  type: typeof ColorPreviewSettingType;
}

/**
 * All valid options for the setting component builder.
 */
export type SettingsComponentOptions =
  | HTMLInputSettingsComponentOptions
  | SwitchSettingComponentOptions
  | SelectSettingComponentOptions
  | TextareaSettingComponentOptions
  | ColorPreviewSettingComponentOptions;

/**
 * Valid attrs that can be returned by the `headerInfo` function
 */
export type AdminHeaderAttrs = AdminHeaderOptions & Partial<Omit<Mithril.Attributes, 'class'>>;

export default abstract class AdminPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends Page<CustomAttrs> {
  settings: Record<string, Stream<string>> = {};
  loading: boolean = false;

  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
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
  abstract content(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;

  /**
   * Returns the submit button for this AdminPage.
   *
   * Calls `this.saveSettings` when the button is clicked.
   */
  submitButton(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    return (
      <Button onclick={this.saveSettings.bind(this)} className="Button Button--primary" loading={this.loading} disabled={!this.isChanged()}>
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>
    );
  }

  /**
   * Returns the Header component for this AdminPage.
   */
  header(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
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
  buildSettingComponent(entry: ((this: this) => Mithril.Children) | SettingsComponentOptions): Mithril.Children {
    if (typeof entry === 'function') {
      return entry.call(this);
    }

    const { setting, help, type, label, ...componentAttrs } = entry;

    const value = this.setting(setting)();

    const [inputId, helpTextId] = [generateElementId(), generateElementId()];

    let settingElement: Mithril.Children;

    // Typescript being Typescript
    // https://github.com/microsoft/TypeScript/issues/14520
    if ((BooleanSettingTypes as readonly string[]).includes(type)) {
      return (
        // TODO: Add aria-describedby for switch help text.
        //? Requires changes to Checkbox component to allow providing attrs directly for the element(s).
        <div className="Form-group">
          <Switch state={!!value && value !== '0'} onchange={this.settings[setting]} {...componentAttrs}>
            {label}
          </Switch>
          <div className="helpText">{help}</div>
        </div>
      );
    } else if ((SelectSettingTypes as readonly string[]).includes(type)) {
      const { default: defaultValue, options, ...otherAttrs } = componentAttrs;

      settingElement = (
        <Select
          id={inputId}
          aria-describedby={helpTextId}
          value={value || defaultValue}
          options={options}
          onchange={this.settings[setting]}
          {...otherAttrs}
        />
      );
    } else {
      componentAttrs.className = classList(['FormControl', componentAttrs.className]);

      if ((TextareaSettingTypes as readonly string[]).includes(type)) {
        settingElement = <textarea id={inputId} aria-describedby={helpTextId} bidi={this.setting(setting)} {...componentAttrs} />;
      } else {
        let Tag: VnodeElementTag = 'input';

        if (type === ColorPreviewSettingType) {
          Tag = ColorPreviewInput;
        } else {
          componentAttrs.type = type;
        }

        settingElement = <Tag id={inputId} aria-describedby={helpTextId} bidi={this.setting(setting)} {...componentAttrs} />;
      }
    }

    return (
      <div className="Form-group">
        {label && <label for={inputId}>{label}</label>}
        <div id={helpTextId} className="helpText">
          {help}
        </div>
        {settingElement}
      </div>
    );
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
  setting(key: string, fallback: string = ''): Stream<string> {
    this.settings[key] = this.settings[key] || Stream<string>(app.data.settings[key] || fallback);

    return this.settings[key];
  }

  /**
   * Returns a map of settings keys to values which includes only those which have been modified but not yet saved.
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
