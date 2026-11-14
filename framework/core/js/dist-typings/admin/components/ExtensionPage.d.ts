import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import RequestError from '../../common/utils/RequestError';
import { Extension } from '../AdminApplication';
import { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
import { type TabItem } from '../../common/components/Tabs';
import type { SettingsTabConfig } from '../utils/AdminRegistry';
export interface ExtensionPageAttrs extends IPageAttrs {
    id: string;
    /** The settings tab to open, from the `?tab=` query parameter. */
    tab?: string;
}
export default class ExtensionPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs> extends AdminPage<Attrs> {
    extension: Extension;
    changingState: boolean;
    /**
     * The open settings tab, when the page has tabs. Kept in the URL as `?tab=`.
     */
    activePageTab: string | null;
    /** The last `?tab=` this page was routed with. */
    protected requestedTab?: string;
    infoFields: {
        discuss: string;
        documentation: string;
        support: string;
        website: string;
        donate: string;
        source: string;
    };
    /**
     * Whether the Permissions tab is shown. Off when another extension removed or
     * replaced the `permissions` item of `sections()`.
     */
    protected permissionsTabShown: boolean;
    oninit(vnode: Mithril.Vnode<Attrs, this>): void;
    onbeforeupdate(vnode: Mithril.VnodeDOM<Attrs, this>): void;
    className(): string;
    view(vnode: Mithril.VnodeDOM<Attrs, this>): JSX.Element | null;
    body(vnode: Mithril.VnodeDOM<Attrs, this>): JSX.Element;
    header(): JSX.Element[];
    sections(vnode: Mithril.VnodeDOM<Attrs, this>): ItemList<Mithril.Children>;
    /**
     * Whether the page is laid out in tabs. Custom pages that replace `content()`
     * keep the stacked layout they were written for until they opt in by
     * overriding `pageTabItems()`.
     */
    usesPageTabs(): boolean;
    /**
     * Whether a subclass of ExtensionPage defines the method itself.
     * Patches applied to ExtensionPage.prototype with `extend()` don't count.
     */
    protected subclassDefines(method: string): boolean;
    /**
     * The page's tabs: the registered settings tabs, or a General tab when the
     * extension registered none, then Permissions. Tabs with nothing in them are left out.
     *
     * A custom page can add its own tabs here and render them in `pageTabContent()`.
     */
    pageTabItems(): ItemList<TabItem>;
    /**
     * The registered settings tabs, or the default General tab.
     */
    settingsTabs(): SettingsTabConfig[];
    /**
     * Renders the tabs. A lone tab is shown without the tab bar.
     */
    pageTabs(vnode: Mithril.VnodeDOM<Attrs, this>, tabs: TabItem[]): Mithril.Children;
    /**
     * The content of one tab. Override to render tabs added in `pageTabItems()`.
     */
    pageTabContent(tab: string, vnode: Mithril.VnodeDOM<Attrs, this>): Mithril.Children;
    /**
     * Whether a tab is the General tab of a page that has not replaced `content()`.
     */
    protected usesDefaultContent(tab: string): boolean;
    changePageTab(tab: string): void;
    /**
     * The settings, for pages laid out without tabs.
     */
    content(vnode: Mithril.VnodeDOM<Attrs, this>): JSX.Element;
    settingsRegistryKey(): string;
    settingsFormControls(): ItemList<Mithril.Children>;
    noSettingsMessage(): Mithril.Children;
    /**
     * The tabs holding settings with unsaved changes.
     *
     * Changes to settings the registry doesn't know about, such as those inside a
     * custom setting, count against the tab that custom setting is on, when there is only one.
     */
    unsavedTabs(): Set<string>;
    topItems(): ItemList<Mithril.Children>;
    infoItems(): ItemList<Mithril.Children>;
    toggle(): void;
    isEnabled(): any;
    onerror(e: RequestError): void | false;
}
