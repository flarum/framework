/**
 * The `Navigation` component displays a set of navigation buttons. Typically
 * this is just a back button which pops the app's History. If the user is on
 * the root page and there is no history to pop, then in some instances it may
 * show a button that toggles the app's drawer.
 *
 * If the app has a pane, it will also include a 'pin' button which toggles the
 * pinned state of the pane.
 *
 * Accepts the following attrs:
 *
 * - `className` The name of a class to set on the root element.
 * - `drawer` Whether or not to show a button to toggle the app's drawer if
 *   there is no more history to pop.
 */
export default class Navigation extends Component<import("../Component").ComponentAttrs, undefined> {
    constructor();
    /**
     * Get the back button.
     *
     * @return {import('mithril').Children}
     * @protected
     */
    protected getBackButton(): import('mithril').Children;
    /**
     * Get the pane pinned toggle button.
     *
     * @return {import('mithril').Children}
     * @protected
     */
    protected getPaneButton(): import('mithril').Children;
    /**
     * Get the drawer toggle button.
     *
     * @return {import('mithril').Children}
     * @protected
     */
    protected getDrawerButton(): import('mithril').Children;
}
import Component from "../Component";
