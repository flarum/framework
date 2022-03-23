/**
 * The `DiscussionsUserPage` component shows a discussion list inside of a user
 * page.
 */
export default class DiscussionsUserPage extends UserPage {
    show(user: any): void;
    state: DiscussionListState<{
        filter: {
            author: any;
        };
        sort: string;
    }>;
    content(): JSX.Element;
}
import UserPage from "./UserPage";
import DiscussionListState from "../states/DiscussionListState";
