/**
 * The `DiscussionsUserPage` component shows a discussion list inside of a user
 * page.
 */
export default class DiscussionsUserPage extends UserPage {
    state: DiscussionListState | undefined;
}
import UserPage from "./UserPage";
import DiscussionListState from "../states/DiscussionListState";
