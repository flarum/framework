/// <reference path="../../@types/translator-icu-rich.d.ts" />
export default class BasicsPage extends AdminPage<import("../../common/components/Page").IPageAttrs> {
    constructor();
    oninit(vnode: any): void;
    localeOptions: {} | undefined;
    displayNameOptions: {} | undefined;
    slugDriverOptions: {} | undefined;
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    content(): JSX.Element[];
    /**
     * Build a list of options for the default homepage. Each option must be an
     * object with `path` and `label` properties.
     *
     * @return {ItemList<{ path: string, label: import('mithril').Children }>}
     */
    homePageItems(): ItemList<{
        path: string;
        label: import('mithril').Children;
    }>;
}
import AdminPage from "./AdminPage";
import ItemList from "../../common/utils/ItemList";
