import Component from '../Component';
import generateElementId from '../utils/generateElementId';
import Switch from './Switch';
import Select from './Select';
import UploadImageButton from './UploadImageButton';
import classList from '../utils/classList';
import ColorPreviewInput from './ColorPreviewInput';
import Stream from '../utils/Stream';
import ItemList from '../utils/ItemList';
import type { IUploadImageButtonAttrs } from './UploadImageButton';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import MultiSelect from './MultiSelect';
import FieldSet from './FieldSet';

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

export interface CommonFieldOptions extends Mithril.Attributes {
  label?: Mithril.Children;
  help?: Mithril.Children;
  className?: string;
}

/**
 * Valid options for the setting component builder to generate an HTML input element.
 */
export interface HTMLInputFieldComponentOptions extends CommonFieldOptions {
  /**
   * Any valid HTML input `type` value.
   */
  type: HTMLInputTypes;
}

const BooleanSettingTypes = ['bool', 'checkbox', 'switch', 'boolean'] as const;
const SelectSettingTypes = ['select', 'dropdown', 'selectdropdown'] as const;
const RadioSettingTypes = ['radio'] as const;
const TextareaSettingTypes = ['textarea'] as const;
const ColorPreviewSettingType = 'color-preview' as const;
const ImageUploadSettingType = 'image-upload' as const;
const StackedFormControlType = 'stacked-text' as const;

/**
 * Valid options for the setting component builder to generate a Switch.
 */
export interface SwitchFieldComponentOptions extends CommonFieldOptions {
  type: (typeof BooleanSettingTypes)[number];
}

/**
 * Valid options for the setting component builder to generate a Select dropdown.
 */
export interface SelectFieldComponentOptions extends CommonFieldOptions {
  type: (typeof SelectSettingTypes)[number] | (typeof RadioSettingTypes)[number];
  /**
   * Map of values to their labels
   */
  options: {
    [value: string]:
      | Mithril.Children
      | {
          label: Mithril.Children;
          disabled?: boolean;
        };
  };
  default: string;
  multiple?: boolean;
}

/**
 * Valid options for the setting component builder to generate a Textarea.
 */
export interface TextareaFieldComponentOptions extends CommonFieldOptions {
  type: (typeof TextareaSettingTypes)[number];
}

/**
 * Valid options for the setting component builder to generate a ColorPreviewInput.
 */
export interface ColorPreviewFieldComponentOptions extends CommonFieldOptions {
  type: typeof ColorPreviewSettingType;
}

export interface ImageUploadFieldComponentOptions extends CommonFieldOptions, IUploadImageButtonAttrs {
  type: typeof ImageUploadSettingType;
}

export interface StackedFormControlFieldComponentOptions extends CommonFieldOptions {
  type: typeof StackedFormControlType;
  textArea: {
    setting: string;
    [key: string]: unknown;
  };
}

export interface CustomFieldComponentOptions extends CommonFieldOptions {
  type: string;
  [key: string]: unknown;
}

/**
 * All valid options for the setting component builder.
 */
export type FieldComponentOptions =
  | HTMLInputFieldComponentOptions
  | SwitchFieldComponentOptions
  | SelectFieldComponentOptions
  | TextareaFieldComponentOptions
  | ColorPreviewFieldComponentOptions
  | ImageUploadFieldComponentOptions
  | StackedFormControlFieldComponentOptions
  | CustomFieldComponentOptions;

export type IFormGroupAttrs = ComponentAttrs &
  FieldComponentOptions & {
    stream?: Stream<any>;
    getSetting?: (key: string) => Stream<any>;
  };

/**
 * Builds a field component based on the provided attributes.
 * Depending on the type of input, you can set the type to 'bool', 'select', or
 * any standard <input> type. Any values inside the 'extra' object will be added
 * to the component as an attribute.
 *
 * Alternatively, you can pass a callback that will be executed in ExtensionPage's
 * context to include custom JSX elements.
 *
 * @example
 *
 * <FormGroup key="acme.checkbox"
 *            label={app.translator.trans('acme.admin.setting_label')}
 *            type="bool"
 *            help={app.translator.trans('acme.admin.setting_help')}
 *            className="Setting-item" />
 *
 * @example
 *
 * <FormGroup key="acme.select"
 *            label={app.translator.trans('acme.admin.setting_label')}
 *            type="select"
 *            options={{
 *              'option1': 'Option 1 label',
 *              'option2': 'Option 2 label',
 *            }}
 *            default="option1" />
 */
export default class FormGroup<CustomAttrs extends IFormGroupAttrs = IFormGroupAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const customFieldComponents = this.customFieldComponents();

    const { help, type, label, stream, getSetting, containerClassName, ...componentAttrs } = this.attrs;

    // TypeScript being TypeScript
    const attrs = componentAttrs as unknown as Omit<IFormGroupAttrs, 'stream' | 'label' | 'help' | 'type'>;

    const value = stream ? stream() : null;

    const [inputId, helpTextId] = [generateElementId(), generateElementId()];

    let settingElement: Mithril.Children;

    // Typescript being Typescript
    // https://github.com/microsoft/TypeScript/issues/14520
    if ((BooleanSettingTypes as readonly string[]).includes(type)) {
      return (
        // TODO: Add aria-describedby for switch help text.
        //? Requires changes to Checkbox component to allow providing attrs directly for the element(s).
        <div className={classList('Form-group', containerClassName)}>
          <Switch state={!!value && value !== '0'} onchange={stream} {...attrs}>
            {label}
          </Switch>
          {help ? <div className="helpText">{help}</div> : null}
        </div>
      );
    } else if ((SelectSettingTypes as readonly string[]).includes(type)) {
      const { default: defaultValue, options, multiple, ...otherAttrs } = attrs;

      const Tag = multiple ? MultiSelect : Select;

      settingElement = (
        <Tag id={inputId} aria-describedby={helpTextId} value={value || defaultValue} options={options} onchange={stream} {...otherAttrs} />
      );
    } else if ((RadioSettingTypes as readonly string[]).includes(type)) {
      const { default: defaultValue, options, multiple, ...otherAttrs } = attrs;

      settingElement = (
        <FieldSet {...otherAttrs}>
          {options.map(({ value, label }: { value: string; label: string }) => (
            <label className="checkbox">
              <input type="radio" name="homePage" value={value} bidi={stream} />
              {label}
            </label>
          ))}
        </FieldSet>
      );
    } else if (type === StackedFormControlType) {
      const { textArea, ...otherAttrs } = attrs;
      const { setting: textAreaSetting, ...textAreaAttrs } = textArea;

      settingElement = (
        <div className="StackedFormControl" {...otherAttrs}>
          <input type="text" className="FormControl" bidi={stream} />
          <textarea className="FormControl" bidi={getSetting?.(textAreaSetting)} {...textAreaAttrs} />
        </div>
      );
    } else if (type === ImageUploadSettingType) {
      const { value, ...otherAttrs } = attrs;

      settingElement = <UploadImageButton value={stream} {...otherAttrs} />;
    } else if (customFieldComponents.has(type)) {
      return customFieldComponents.get(type)({ ...this.attrs, bidi: this.attrs.stream });
    } else {
      attrs.className = classList('FormControl', attrs.className);

      if ((TextareaSettingTypes as readonly string[]).includes(type)) {
        settingElement = <textarea id={inputId} aria-describedby={helpTextId} bidi={stream} {...attrs} />;
      } else {
        let Tag: VnodeElementTag | typeof ColorPreviewInput = 'input';

        if (type === ColorPreviewSettingType) {
          Tag = ColorPreviewInput;
        } else {
          attrs.type = type;
        }

        settingElement = <Tag id={inputId} aria-describedby={helpTextId} bidi={stream} {...attrs} />;
      }
    }

    return (
      <div className={classList('Form-group', containerClassName)}>
        {label && <label for={inputId}>{label}</label>}
        {help && (
          <div id={helpTextId} className="helpText">
            {help}
          </div>
        )}
        {settingElement}
      </div>
    );
  }

  /**
   * A list of extension-defined custom setting components to be available.
   *
   * The ItemList key represents the value for the `type` attribute.
   * All attributes passed are provided as arguments to the function added to the ItemList.
   *
   * ItemList priority has no effect here.
   *
   * @example
   * ```tsx
   * extend(AdminPage.prototype, 'customFieldComponents', function (items) {
   *   // You can access the AdminPage instance with `this` to access its `settings` property.
   *
   *   // Prefixing the key with your extension ID is recommended to avoid collisions.
   *   items.add('my-ext.setting-component', (attrs) => {
   *     return (
   *       <div className={attrs.className}>
   *         <label>{attrs.label}</label>
   *         {attrs.help && <p className="helpText">{attrs.help}</p>}
   *
   *         My setting component!
   *       </div>
   *     );
   *   })
   * })
   * ```
   */
  customFieldComponents(): ItemList<(attributes: CustomAttrs) => Mithril.Children> {
    const items = new ItemList<(attributes: CustomAttrs) => Mithril.Children>();

    return items;
  }
}
