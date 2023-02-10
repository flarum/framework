/**
 * The `DiscussionRenamedPost` component displays a discussion event post
 * indicating that the discussion has been renamed.
 *
 * ### Attrs
 *
 * - All of the attrs for EventPost
 */
export default class DiscussionRenamedPost extends EventPost {
    description(data: any): JSX.Element;
    descriptionData(): {
        new: JSX.Element;
    };
}
import EventPost from "./EventPost";
