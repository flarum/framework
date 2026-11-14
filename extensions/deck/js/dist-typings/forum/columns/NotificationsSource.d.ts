import type Notification from 'flarum/common/models/Notification';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type { DeckColumnSource, DeckRealtimeEvent, DeckRealtimeResult } from './DeckColumnType';
/**
 * Shares `app.notifications` with the header dropdown, so reading a
 * notification in one place updates the other.
 */
export default class NotificationsSource implements DeckColumnSource {
    load(): Promise<unknown>;
    view(): JSX.Element;
    checkForNew(): Promise<number>;
    applyNew(): Promise<number | null>;
    /** Per-member and indexed by user, so the newest few are a cheap question. */
    protected unshown(): Promise<Notification[]>;
    /** Realtime sends each new notification on the member's channel: shown at once, no request. */
    onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult;
    showNew(): Promise<unknown>;
    controls(items: ItemList<Mithril.Children>): void;
}
