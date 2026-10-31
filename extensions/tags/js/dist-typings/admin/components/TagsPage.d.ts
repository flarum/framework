export default class TagsPage extends ExtensionPage<import("flarum/admin/components/ExtensionPage").ExtensionPageAttrs> {
    constructor();
    oninit(vnode: any): void;
    forcedRefreshKey: number | undefined;
    sortable: typeof import("sortablejs") | undefined;
    /**
     * The Tags tab (the tag tree) first, then the tag settings, then core's Permissions tab.
     */
    pageTabItems(): any;
    pageTabContent(tab: any, vnode: any): any;
    /**
     * The tag tree: primary and secondary tags, each with a create button.
     */
    content(): JSX.Element;
    /**
     * The tag settings, saved with the page's own settings streams.
     */
    settingsContent(): JSX.Element;
    /**
     * The sections on the Settings tab.
     */
    settingsItems(): ItemList<any>;
    onListOnCreate(vnode: any): void;
    setMinTags(minTags: any, maxTags: any, value: any): void;
    onSortUpdate(e: any): void;
}
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import ItemList from 'flarum/common/utils/ItemList';
