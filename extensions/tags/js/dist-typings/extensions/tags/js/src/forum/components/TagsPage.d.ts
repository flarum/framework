export default class TagsPage extends Page<import("flarum/common/components/Page").IPageAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    tags: any[] | undefined;
    loading: boolean | undefined;
    view(): JSX.Element;
    oncreate(vnode: any): void;
}
import Page from "flarum/common/components/Page";
