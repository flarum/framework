/**
 * The `ConfirmDocumentUnload` component can be used to register a global
 * event handler that prevents closing the browser window/tab based on the
 * return value of a given callback prop.
 *
 * ### Attrs
 *
 * - `when` - a callback returning true when the browser should prompt for
 *            confirmation before closing the window/tab
 *
 * ### Children
 *
 * NOTE: Only the first child will be rendered. (Use this component to wrap
 * another component / DOM element.)
 *
 */
export default class ConfirmDocumentUnload extends Component<import("../Component").ComponentAttrs> {
    constructor();
    handler(): any;
    boundHandler: (() => any) | undefined;
}
import Component from "../Component";
