export default class TagsPage extends Page<import("flarum/common/components/Page").IPageAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    tags: any[] | import("../../common/models/Tag").default[] | undefined;
    loading: boolean | undefined;
    oncreate(vnode: any): void;
    view(): JSX.Element;
    pageContent(): ItemList<any>;
    mainContent(): ItemList<any>;
    content(): JSX.Element;
    contentItems(): ItemList<any>;
    hero(): JSX.Element;
    sidebar(): JSX.Element;
    sidebarItems(): ItemList<import("mithril").Children>;
    tagTileListView(pinned: any): JSX.Element;
    tagTileView(tag: any): JSX.Element;
    cloudView(cloud: any): JSX.Element;
}
import Page from "flarum/common/components/Page";
import ItemList from "flarum/common/utils/ItemList";
