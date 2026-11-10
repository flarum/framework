export default class DiscussionTaggedPost extends EventPost {
    oninit(vnode: any): void;
    tagsAdded: any;
    tagsRemoved: any;
    fetchRequired: boolean | undefined;
    diffTags(tags1: any, tags2: any): any;
    descriptionKey(): "flarum-tags.forum.post_stream.added_and_removed_tags_text" | "flarum-tags.forum.post_stream.added_tags_text" | "flarum-tags.forum.post_stream.removed_tags_text";
    descriptionData(): {
        tagsAdded: any[];
        tagsRemoved: any[];
    };
}
import EventPost from 'flarum/forum/components/EventPost';
