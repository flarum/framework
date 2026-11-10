import Model from 'flarum/common/Model';
import type Discussion from 'flarum/common/models/Discussion';
export default class Tag extends Model {
    name(): string;
    slug(): string;
    storedSlug(): string;
    description(): string | null;
    color(): string | null;
    /**
     * The Font Awesome icon classes, with the classes that show the uploaded
     * image in their place when the tag has one. The stored classes alone are
     * `attribute('icon')`.
     */
    icon(): string | null;
    iconImageUrl(): string | null;
    iconImageSrcset(): string | null;
    position(): number | null;
    parent(): false | Tag | null;
    children(): false | (Tag | undefined)[];
    defaultSort(): string | null;
    isChild(): boolean;
    isHidden(): boolean;
    isPrimary(): boolean;
    discussionCount(): number;
    lastPostedAt(): Date | null | undefined;
    lastPostedDiscussion(): false | Discussion | null;
    isRestricted(): boolean;
    canStartDiscussion(): boolean;
    canAddToDiscussion(): boolean;
    isPrimaryParent(): boolean;
}
