import Model from 'flarum/common/Model';
import type Discussion from 'flarum/common/models/Discussion';
export default class Tag extends Model {
    name(): string;
    slug(): string;
    description(): string | null;
    color(): string | null;
    backgroundUrl(): string | null;
    backgroundMode(): string | null;
    icon(): string | null;
    position(): number | null;
    parent(): false | Tag | null;
    children(): false | (Tag | undefined)[];
    defaultSort(): string | null;
    isChild(): boolean;
    isHidden(): boolean;
    discussionCount(): number;
    lastPostedAt(): Date | null | undefined;
    lastPostedDiscussion(): false | Discussion | null;
    isRestricted(): boolean;
    canStartDiscussion(): boolean;
    canAddToDiscussion(): boolean;
    isPrimary(): boolean;
}
