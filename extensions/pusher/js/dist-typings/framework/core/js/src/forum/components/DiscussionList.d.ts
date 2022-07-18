/**
 * The `DiscussionList` component displays a list of discussions.
 *
 * ### Attrs
 *
 * - `state` A DiscussionListState object that represents the discussion lists's state.
 */
declare class DiscussionList extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    loadingUpdated?: boolean | undefined;
}
export default DiscussionList;
import Component from "../../common/Component";
