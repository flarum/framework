export default class DiscussionTaggedPost {
    static initAttrs(attrs: any): void;
    icon(): string;
    descriptionKey(): "flarum-tags.forum.post_stream.added_and_removed_tags_text" | "flarum-tags.forum.post_stream.added_tags_text" | "flarum-tags.forum.post_stream.removed_tags_text";
    descriptionData(): {
        tagsAdded: any;
        tagsRemoved: any;
    };
}
