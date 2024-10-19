/// <reference path="../../../../../../framework/core/@types/translator-icu-rich.d.ts" />
/// <reference types="flarum/@types/translator-icu-rich" />
export default class DiscussionTaggedPost extends EventPost {
    static initAttrs(attrs: any): void;
    descriptionKey(): "flarum-tags.forum.post_stream.added_and_removed_tags_text" | "flarum-tags.forum.post_stream.added_tags_text" | "flarum-tags.forum.post_stream.removed_tags_text";
    descriptionData(): {
        tagsAdded: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        tagsRemoved: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
}
import EventPost from "flarum/forum/components/EventPost";
