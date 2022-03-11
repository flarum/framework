import Model from '../Model';
import ItemList from '../utils/ItemList';
import Mithril from 'mithril';
import Post from './Post';
import User from './User';
export default class Discussion extends Model {
    title(): string;
    slug(): string;
    createdAt(): Date | undefined;
    user(): false | User | null;
    firstPost(): false | Post | null;
    lastPostedAt(): Date | null | undefined;
    lastPostedUser(): false | User | null;
    lastPost(): false | Post | null;
    lastPostNumber(): number | null | undefined;
    commentCount(): number | undefined;
    replyCount(): Number;
    posts(): false | (Post | undefined)[];
    mostRelevantPost(): false | Post | null;
    lastReadAt(): Date | null | undefined;
    lastReadPostNumber(): number | null | undefined;
    isUnread(): boolean;
    isRead(): boolean;
    hiddenAt(): Date | null | undefined;
    hiddenUser(): false | User | null;
    isHidden(): boolean;
    canReply(): boolean | undefined;
    canRename(): boolean | undefined;
    canHide(): boolean | undefined;
    canDelete(): boolean | undefined;
    /**
     * Remove a post from the discussion's posts relationship.
     */
    removePost(id: string): void;
    /**
     * Get the estimated number of unread posts in this discussion for the current
     * user.
     */
    unreadCount(): number;
    /**
     * Get the Badge components that apply to this discussion.
     */
    badges(): ItemList<Mithril.Children>;
    /**
     * Get a list of all of the post IDs in this discussion.
     */
    postIds(): string[];
}
