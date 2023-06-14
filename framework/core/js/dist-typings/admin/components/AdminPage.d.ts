import type Mithril from 'mithril';
import Page, { IPageAttrs } from '../../common/components/Page';
import Stream from '../../common/utils/Stream';
import ItemList from '../../common/utils/ItemList';
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
export declare type HTMLInputTypes = 'button' | 'checkbox' | 'color' | 'date' | 'datetime-local' | 'email' | 'file' | 'hidden' | 'image' | 'month' | 'number' | 'password' | 'radio' | 'range' | 'reset' | 'search' | 'submit' | 'tel' | 'text' | 'time' | 'url' | 'week';
export interface CommonSettingsItemOptions extends Mithril.Attributes {
    setting: string;
    label?: Mithril.Children;
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
declare const BooleanSettingTypes: readonly ["bool", "checkbox", "switch", "boolean"];
declare const SelectSettingTypes: readonly ["select", "dropdown", "selectdropdown"];
declare const TextareaSettingTypes: readonly ["textarea"];
declare const ColorPreviewSettingType: "color-preview";
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
    options: {
        [value: string]: Mithril.Children;
    };
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
export interface CustomSettingComponentOptions extends CommonSettingsItemOptions {
    type: string;
    [key: string]: unknown;
}
/**
 * All valid options for the setting component builder.
 */
export declare type SettingsComponentOptions = HTMLInputSettingsComponentOptions | SwitchSettingComponentOptions | SelectSettingComponentOptions | TextareaSettingComponentOptions | ColorPreviewSettingComponentOptions | CustomSettingComponentOptions;
/**
 * Valid attrs that can be returned by the `headerInfo` function
 */
export declare type AdminHeaderAttrs = AdminHeaderOptions & Partial<Omit<Mithril.Attributes, 'class'>>;
export declare type SettingValue = string;
export declare type MutableSettings = Record<string, Stream<SettingValue>>;
export declare type SaveSubmitEvent = SubmitEvent & {
    redraw: boolean;
};
export default abstract class AdminPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends Page<CustomAttrs> {
    settings: MutableSettings;
    loading: boolean;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    /**
     * Returns the content of the AdminPage.
     */
    abstract content(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    /**
     * Returns the submit button for this AdminPage.
     *
     * Calls `this.saveSettings` when the button is clicked.
     */
    submitButton(): Mithril.Children;
    /**
     * Returns the Header component for this AdminPage.
     */
    header(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    /**
     * Returns the options passed to the AdminHeader component.
     */
    headerInfo(): AdminHeaderAttrs;
    /**
     * A list of extension-defined custom setting components to be available through
     * {@link AdminPage.buildSettingComponent}.
     *
     * The ItemList key represents the value for `type` to be provided when calling
     * {@link AdminPage.buildSettingComponent}. Other attributes passed are provided
     * as arguments to the function added to the ItemList.
     *
     * ItemList priority has no effect here.
     *
     * @example
     * ```tsx
     * extend(AdminPage.prototype, 'customSettingComponents', function (items) {
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
    customSettingComponents(): ItemList<(attributes: CommonSettingsItemOptions) => Mithril.Children>;
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
    buildSettingComponent(entry: ((this: this) => Mithril.Children) | SettingsComponentOptions): Mithril.Children;
    /**
     * Called when `saveSettings` completes successfully.
     */
    onsaved(): void;
    /**
     * Returns a function that fetches the setting from the `app` global.
     */
    setting(key: string, fallback?: string): Stream<string>;
    /**
     * Returns a map of settings keys to values which includes only those which have been modified but not yet saved.
     */
    dirty(): Record<string, string>;
    /**
     * Returns the number of settings that have been modified.
     */
    isChanged(): number;
    /**
     * Saves the modified settings to the database.
     */
    saveSettings(e: SaveSubmitEvent): Promise<void>;
}
export {};
