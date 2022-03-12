/**
 * The `NotificationList` component displays a list of the logged-in user's
 * notifications, grouped by discussion.
 */
export default class NotificationList extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    controlItems(): ItemList<any>;
    content(state: any): any;
    oncreate(vnode: any): void;
    $notifications: JQuery<HTMLElement> | undefined;
    $scrollParent: JQuery<HTMLElement> | JQuery<Window & typeof globalThis> | undefined;
    boundScrollHandler: (() => void) | undefined;
    onremove(vnode: any): void;
    scrollHandler(): void;
    /**
     * If the NotificationList component isn't in a panel (e.g. on NotificationPage when mobile),
     * we need to listen to scroll events on the window, and get scroll state from the body.
     */
    inPanel(): boolean;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
