export default class TagsPage extends Page<import("flarum/common/components/Page").IPageAttrs> {
    constructor();
    oninit(vnode: any): void;
    tags: any;
    loading: boolean | undefined;
    view(): JSX.Element;
    oncreate(vnode: any): void;
}
import Page from "flarum/common/components/Page";
