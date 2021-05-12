/**
 * The `DiscussionListPane` component displays the list of previously viewed
 * discussions in a panel that can be displayed by moving the mouse to the left
 * edge of the screen, where it can also be pinned in place.
 *
 * ### Attrs
 *
 * - `state` A DiscussionListState object that represents the discussion lists's state.
 */
export default class DiscussionListPane extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    /**
     * Are we on a device that's larger than we consider "mobile"?
     *
     * @returns {boolean}
     */
    enoughSpace(): boolean;
}
import Component from "../../common/Component";
