import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import MentionableModel from './MentionableModel';
import AtMentionFormat from './formats/AtMentionFormat';
export default class UserMention extends MentionableModel<User, AtMentionFormat> {
    type(): string;
    initialResults(): User[];
    /**
     * Automatically determines which mention syntax to be used based on the option in the
     * admin dashboard. Also performs display name clean-up automatically.
     *
     * @"Display name"#UserID or `@username`
     *
     * @example <caption>New display name syntax</caption>
     * // '@"user"#1'
     * forUser(User) // User is ID 1, display name is 'User'
     *
     * @example <caption>Using old syntax</caption>
     * // '@username'
     * forUser(user) // User's username is 'username'
     */
    replacement(user: User): string;
    suggestion(model: User, typed: string): Mithril.Children;
    matches(model: User, typed: string): boolean;
    maxStoreMatchedResults(): null;
    search(typed: string): Promise<User[]>;
    enabled(): boolean;
}
