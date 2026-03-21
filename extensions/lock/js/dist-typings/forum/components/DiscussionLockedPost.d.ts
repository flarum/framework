export default class DiscussionLockedPost extends EventPost {
    icon(): "fas fa-lock" | "fas fa-unlock";
    descriptionKey(): "flarum-lock.forum.post_stream.discussion_locked_text" | "flarum-lock.forum.post_stream.discussion_unlocked_text";
}
import EventPost from "flarum/forum/components/EventPost";
