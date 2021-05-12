/**
 * The `Page` component
 *
 * @abstract
 */
export default class Page extends Component<import("../Component").ComponentAttrs> {
    constructor();
    /**
     * A class name to apply to the body while the route is active.
     *
     * @type {String}
     */
    bodyClass: string | undefined;
    /**
     * Whether we should scroll to the top of the page when its rendered.
     *
     * @type {Boolean}
     */
    scrollTopOnCreate: boolean | undefined;
    /**
     * Whether the browser should restore scroll state on refreshes.
     *
     * @type {Boolean}
     */
    useBrowserScrollRestoration: boolean | undefined;
}
import Component from "../Component";
