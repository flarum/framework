/**
 * The `AffixedSidebar` component uses Bootstrap's "affix" plugin to keep a
 * sidebar navigation at the top of the viewport when scrolling.
 *
 * ### Children
 *
 * The component must wrap an element that itself wraps an <ul> element, which
 * will be "affixed".
 *
 * @see https://getbootstrap.com/docs/3.4/javascript/#affix
 */
export default class AffixedSidebar extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    view(vnode: any): any;
    oncreate(vnode: any): void;
    boundOnresize: (() => void) | undefined;
    onremove(vnode: any): void;
    onresize(): void;
    bottom: number | undefined;
}
import Component from "../../common/Component";
