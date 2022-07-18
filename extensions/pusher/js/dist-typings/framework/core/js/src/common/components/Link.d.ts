/**
 * The link component enables both internal and external links.
 * It will return a regular HTML link for any links to external sites,
 * and it will use Mithril's m.route.Link for any internal links.
 *
 * Links will default to internal; the 'external' attr must be set to
 * `true` for the link to be external.
 */
export default class Link extends Component<import("../Component").ComponentAttrs, undefined> {
    constructor();
    view(vnode: any): JSX.Element;
}
import Component from "../Component";
