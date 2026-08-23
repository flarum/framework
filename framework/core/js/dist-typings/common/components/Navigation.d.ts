import Component from '../Component';
import type Mithril from 'mithril';
import ItemList from '../utils/ItemList';
/**
 * The `Navigation` component displays a set of navigation buttons. In `drawer`
 * mode — the fixed control strip at the top of the mobile layout — the drawer
 * toggle is always present, so the menu and the notification badge it carries
 * stay reachable on every page; the back button joins it only when there is
 * history to pop. Elsewhere (the desktop header) it is the back button alone.
 *
 * If the app has a pane, it will also include a 'pin' button which toggles the
 * pinned state of the pane.
 *
 * Accepts the following attrs:
 *
 * - `className` The name of a class to set on the root element.
 * - `drawer` Whether or not to show a button to toggle the app's drawer.
 */
export default class Navigation extends Component {
    view(): JSX.Element;
    /**
     * The controls shown in the navigation, as an ItemList so that other parts of
     * the app — and extensions — can contribute their own.
     */
    items(): ItemList<Mithril.Children>;
    /**
     * Get the back button.
     */
    protected getBackButton(): Mithril.Children;
    /**
     * Get the pane pinned toggle button.
     */
    protected getPaneButton(): Mithril.Children;
    /**
     * Get the drawer toggle button.
     */
    protected getDrawerButton(): Mithril.Children;
}
