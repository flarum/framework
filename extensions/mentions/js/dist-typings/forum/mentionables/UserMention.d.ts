import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import MentionableModel from './MentionableModel';
import AtMentionFormat from './formats/AtMentionFormat';
export default class UserMention extends MentionableModel<User, AtMentionFormat> {
    type(): string;
    initialResults(): User[];
    /**
     * Uses the user ID so autocomplete works regardless of the username's characters.
     */
    replacement(user: User): string;
    suggestion(model: User, typed: string): Mithril.Children;
    matches(model: User, typed: string): boolean;
    maxStoreMatchedResults(): null;
    search(typed: string): Promise<User[]>;
    enabled(): boolean;
}
