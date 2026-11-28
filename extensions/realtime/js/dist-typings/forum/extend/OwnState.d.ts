export interface NotificationsPayload {
    read?: {
        id: string;
        type: string;
        subjectId: string;
    };
    /** When everything up to then was read or deleted. */
    allRead?: string;
    allDeleted?: string;
    unreadNotificationCount?: number;
    newNotificationCount?: number;
}
export declare function applyNotifications(data: NotificationsPayload): void;
export declare function applyDiscussionRead(data: {
    id: string;
    lastReadPostNumber: number;
    lastReadAt: string | null;
}): void;
export declare function applyAllDiscussionsRead(data: {
    markedAllAsReadAt: string;
}): void;
/**
 * Pushes missed while disconnected are not replayed.
 */
export declare function catchUpOwnState(): void;
export default function OwnState(): void;
