import type Mithril from 'mithril';
import Page, { IPageAttrs } from '../../common/components/Page';
import Stream from '../../common/utils/Stream';
import { FieldComponentOptions } from '../../common/components/FormGroup';
import ItemList from '../../common/utils/ItemList';
import type { SettingConfigInternal, SettingsSectionConfig } from '../utils/AdminRegistry';
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
export type SettingsComponentOptions = FieldComponentOptions & {
    setting: string;
    /** The id of the settings section to show this setting in. */
    section?: string;
    json?: boolean;
    refreshAfterSaving?: boolean;
};
/**
 * Valid attrs that can be returned by the `headerInfo` function
 */
export type AdminHeaderAttrs = AdminHeaderOptions & Partial<Omit<Mithril.Attributes, 'class'>>;
export type SettingValue = string;
export type MutableSettings = Record<string, Stream<SettingValue>>;
export type SaveSubmitEvent = SubmitEvent & {
    redraw: boolean;
};
export default abstract class AdminPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends Page<CustomAttrs> {
    /**
     * The id of the tab that holds the settings when a page registers no tabs of its own.
     */
    static DEFAULT_TAB: string;
    settings: MutableSettings;
    settingLabels: Record<string, Mithril.Children>;
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
     * Returns a button that opens a confirmation modal to delete all settings
     * tracked by this page from the database, reverting them to their PHP-side defaults.
     *
     * Calls can pass an explicit list of setting keys to reset; otherwise all keys
     * currently tracked in `this.settings` are used.
     */
    resetButton(settings?: import('./ResetExtensionSettingsModal').ResetSettingItem[], title?: string, extensionId?: string): Mithril.Children;
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
     * The AdminRegistry key whose registered settings this page shows: an
     * extension id, or a core page's key such as `core-basics`. `null` when the
     * page builds its settings itself.
     */
    settingsRegistryKey(): string | null;
    /**
     * The registered settings, grouped into their sections, followed by the save controls.
     *
     * @param tab Only show the settings on this tab; `null` shows them all.
     */
    settingsForm(tab?: string | null): Mithril.Children;
    /**
     * The buttons under the settings form.
     */
    settingsFormControls(): ItemList<Mithril.Children>;
    /**
     * Shown by `settingsForm()` when there are no settings to show.
     */
    noSettingsMessage(): Mithril.Children;
    /**
     * The registered settings on a tab, grouped into their sections, as one
     * `SettingsSection` each. Settings before the first section come first, without a heading.
     */
    settingsSections(tab?: string | null): ItemList<Mithril.Children>;
    /**
     * The registered settings on a tab, in display order, split into their sections.
     *
     * @param tab `null` for every tab.
     */
    settingsGroups(tab?: string | null): {
        section: SettingsSectionConfig | null;
        settings: SettingConfigInternal[];
    }[];
    /**
     * Renders an ItemList as titled sections, keeping the list's keys and priorities.
     *
     * Each section names the item keys it holds. An item it doesn't name, such as
     * one another extension added, joins the section of the item before it, so it
     * stays where it was shown before. Items before the first named one are shown
     * without a heading.
     *
     * @example
     * this.itemSections(this.brandingItems(), [
     *   { id: 'logos', label: app.translator.trans('acme.admin.logos'), items: ['logo', 'favicon'] },
     *   { id: 'code', label: app.translator.trans('acme.admin.code'), items: ['custom-css'] },
     * ]).toArray()
     */
    itemSections(items: ItemList<Mithril.Children>, sections: (SettingsSectionConfig & {
        items: string[];
    })[]): ItemList<Mithril.Children>;
    /**
     * The id of the tab a registered setting is shown on.
     */
    settingsTabOf(setting: SettingConfigInternal): string;
    /**
     * Called when `saveSettings` completes successfully.
     */
    onsaved(): void;
    /**
     * Called when `saveSettings` fails to complete.
     */
    onsavefailed(): void;
    /**
     * Returns a function that fetches the setting from the `app` global.
     *
     * An optional `label` can be provided to associate a human-readable label
     * with the setting key, which is used by `resetButton()` in the reset modal.
     */
    setting(key: string, fallback?: string, label?: Mithril.Children): Stream<string>;
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
    static modelLocale(): Record<string, string>;
}
