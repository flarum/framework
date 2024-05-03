import Component from '../Component';
import Stream from '../utils/Stream';
import ItemList from '../utils/ItemList';
import type { IUploadImageButtonAttrs } from './UploadImageButton';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
/**
 * A type that matches any valid value for the `type` attribute on an HTML `<input>` element.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#attr-type
 *
 * Note: this will be exported from a different location in the future.
 *
 * @see https://github.com/flarum/core/issues/3039
 */
export declare type HTMLInputTypes = 'button' | 'checkbox' | 'color' | 'date' | 'datetime-local' | 'email' | 'file' | 'hidden' | 'image' | 'month' | 'number' | 'password' | 'radio' | 'range' | 'reset' | 'search' | 'submit' | 'tel' | 'text' | 'time' | 'url' | 'week';
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
declare const BooleanSettingTypes: readonly ["bool", "checkbox", "switch", "boolean"];
declare const SelectSettingTypes: readonly ["select", "dropdown", "selectdropdown"];
declare const TextareaSettingTypes: readonly ["textarea"];
declare const ColorPreviewSettingType: "color-preview";
declare const ImageUploadSettingType: "image-upload";
/**
 * Valid options for the setting component builder to generate a Switch.
 */
export interface SwitchFieldComponentOptions extends CommonFieldOptions {
    type: typeof BooleanSettingTypes[number];
}
/**
 * Valid options for the setting component builder to generate a Select dropdown.
 */
export interface SelectFieldComponentOptions extends CommonFieldOptions {
    type: typeof SelectSettingTypes[number];
    /**
     * Map of values to their labels
     */
    options: {
        [value: string]: Mithril.Children | {
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
    type: typeof TextareaSettingTypes[number];
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
export interface CustomFieldComponentOptions extends CommonFieldOptions {
    type: string;
    [key: string]: unknown;
}
/**
 * All valid options for the setting component builder.
 */
export declare type FieldComponentOptions = HTMLInputFieldComponentOptions | SwitchFieldComponentOptions | SelectFieldComponentOptions | TextareaFieldComponentOptions | ColorPreviewFieldComponentOptions | ImageUploadFieldComponentOptions | CustomFieldComponentOptions;
export declare type IFormGroupAttrs = ComponentAttrs & FieldComponentOptions & {
    stream?: Stream<any>;
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
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
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
    customFieldComponents(): ItemList<(attributes: CustomAttrs) => Mithril.Children>;
}
export {};
