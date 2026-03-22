import Group from 'flarum/common/models/Group';
import MentionableModel from './MentionableModel';
import type Mithril from 'mithril';
import type AtMentionFormat from './formats/AtMentionFormat';
export default class GroupMention extends MentionableModel<Group, AtMentionFormat> {
    type(): string;
    initialResults(): Group[];
    /**
     * Generates the mention syntax for a group mention.
     *
     * @"Name Plural"#gGroupID
     *
     * @example <caption>Group mention</caption>
     * // '@"Mods"#g4'
     * forGroup(group) // Group display name is 'Mods', group ID is 4
     */
    replacement(group: Group): string;
    suggestion(model: Group, typed: string): Mithril.Children;
    matches(model: Group, typed: string): boolean;
    maxStoreMatchedResults(): null;
    /**
     * All groups are already loaded, so we don't need to search for them.
     */
    search(typed: string): Promise<Group[]>;
    enabled(): boolean;
}
