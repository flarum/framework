/**
 * The `ConfirmDocumentUnload` component can be used to register a global
 * event handler that prevents closing the browser window/tab based on the
 * return value of a given callback prop.
 *
 * ### Attrs
 *
 * - `when` - a callback returning true when the browser should prompt for
 *            confirmation before closing the window/tab
 */
export default class ConfirmDocumentUnload extends Component<import("../Component").ComponentAttrs, undefined> {
    constructor();
    handler(): any;
    oncreate(vnode: any): void;
    boundHandler: (() => any) | undefined;
    onremove(vnode: any): void;
    view(vnode: any): JSX.Element;
}
import Component from "../Component";
