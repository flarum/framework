import type Tag from 'ext:flarum/tags/common/models/Tag';
import type Mithril from 'mithril';
import MentionableModel from './MentionableModel';
import type HashMentionFormat from './formats/HashMentionFormat';
export default class TagMention extends MentionableModel<Tag, HashMentionFormat> {
    type(): string;
    initialResults(): Tag[];
    /**
     * Generates the mention syntax for a tag mention.
     *
     * ~tagSlug
     *
     * @example <caption>Tag mention</caption>
     * // ~general
     * forTag(tag) // Tag display name is 'Tag', tag ID is 5
     */
    replacement(tag: Tag): string;
    matches(model: Tag, typed: string): boolean;
    maxStoreMatchedResults(): null;
    search(typed: string): Promise<Tag[]>;
    suggestion(model: Tag, typed: string): Mithril.Children;
    enabled(): boolean;
}
