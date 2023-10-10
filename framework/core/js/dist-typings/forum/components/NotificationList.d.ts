/**
 * The `NotificationList` component displays a list of the logged-in user's
 * notifications, grouped by discussion.
 */
export default class NotificationList extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    controlItems(): ItemList<any>;
    content(state: any): any;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
