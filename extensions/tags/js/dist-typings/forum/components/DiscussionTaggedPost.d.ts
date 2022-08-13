export default class DiscussionTaggedPost extends EventPost {
    static initAttrs(attrs: any): void;
    descriptionKey(): "flarum-tags.forum.post_stream.added_and_removed_tags_text" | "flarum-tags.forum.post_stream.added_tags_text" | "flarum-tags.forum.post_stream.removed_tags_text";
    descriptionData(): {
        tagsAdded: any;
        tagsRemoved: any;
    };
}
import EventPost from "flarum/forum/components/EventPost";
