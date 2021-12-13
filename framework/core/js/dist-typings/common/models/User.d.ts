import { Color } from 'color-thief-browser';
import Model from '../Model';
import ItemList from '../utils/ItemList';
import Mithril from 'mithril';
import Group from './Group';
export default class User extends Model {
    username(): string;
    slug(): string;
    displayName(): string;
    email(): string | undefined;
    isEmailConfirmed(): boolean | undefined;
    password(): string | undefined;
    avatarUrl(): string | null;
    preferences(): Record<string, any> | null | undefined;
    groups(): false | (Group | undefined)[];
    joinTime(): Date | null | undefined;
    lastSeenAt(): Date | null | undefined;
    markedAllAsReadAt(): Date | null | undefined;
    unreadNotificationCount(): number | undefined;
    newNotificationCount(): number | undefined;
    discussionCount(): number | undefined;
    commentCount(): number | undefined;
    canEdit(): boolean | undefined;
    canEditCredentials(): boolean | undefined;
    canEditGroups(): boolean | undefined;
    canDelete(): boolean | undefined;
    color(): string;
    protected avatarColor: Color | null;
    /**
     * Check whether or not the user has been seen in the last 5 minutes.
     */
    isOnline(): boolean;
    /**
     * Get the Badge components that apply to this user.
     */
    badges(): ItemList<Mithril.Children>;
    /**
     * Calculate the dominant color of the user's avatar. The dominant color will
     * be set to the `avatarColor` property once it has been calculated.
     */
    protected calculateAvatarColor(): void;
    /**
     * Update the user's preferences.
     */
    savePreferences(newPreferences: Record<string, unknown>): Promise<this>;
}
