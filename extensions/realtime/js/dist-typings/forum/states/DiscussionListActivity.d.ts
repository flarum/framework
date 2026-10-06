import type { Channel } from 'pusher-js';
import WebsocketUpdates from '../extend/DiscussionList/WebsocketUpdates';
/** Events that put a discussion back at the top of the list. */
export declare const LIST_ACTIVITY_EVENTS: string[];
/**
 * New activity for the forum's discussion list (`app.discussions`), collected
 * for the whole session rather than only while the list is on screen. Activity
 * that arrives while the reader is on a discussion is still here when they
 * come back to the list, which keeps its loaded pages and scroll position
 * instead of reloading.
 */
export default class DiscussionListActivity {
    readonly updates: WebsocketUpdates;
    /**
     * The route the list was last shown under. Filtering needs it while another
     * page is open, when `app.current` is that page.
     */
    protected listRoute: string | null;
    protected listShown: boolean;
    protected readonly onActivity: (data: unknown) => void;
    /**
     * Safe to call again with the same channel, or with the new channels a
     * reconnect creates: the handler is never bound to a channel twice.
     */
    bind(channel: Channel): void;
    /**
     * The list is about to render. With auto-release on, what arrived while the
     * reader was away goes in before the first render, so the list is current
     * when it appears. With manual release, it waits behind the button.
     */
    showList(routeName: string | null): void;
    hideList(): void;
    /**
     * The list was reloaded, so it already shows everything collected so far.
     */
    listReloaded(): void;
    release(): void;
    receive(data: unknown): void;
}
