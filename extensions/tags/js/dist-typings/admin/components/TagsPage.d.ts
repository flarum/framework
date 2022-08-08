export default class TagsPage extends ExtensionPage<import("flarum/admin/components/ExtensionPage").ExtensionPageAttrs> {
    constructor();
    oninit(vnode: any): void;
    forcedRefreshKey: number | undefined;
    content(): JSX.Element;
    onListOnCreate(vnode: any): void;
    setMinTags(minTags: any, maxTags: any, value: any): void;
    onSortUpdate(e: any): void;
}
import ExtensionPage from "flarum/admin/components/ExtensionPage";
