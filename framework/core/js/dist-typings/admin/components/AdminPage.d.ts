import type Mithril from 'mithril';
import Page, { IPageAttrs } from '../../common/components/Page';
import Stream from '../../common/utils/Stream';
import { FieldComponentOptions } from '../../common/components/FormGroup';
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
export declare type SettingsComponentOptions = FieldComponentOptions & {
    setting: string;
    json?: boolean;
    refreshAfterSaving?: boolean;
};
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
    refreshAfterSaving: string[];
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
    modelLocale(): Record<string, string>;
}
