/// <reference types="mithril" />
import type { DeckColumnSource, DeckRealtimeEvent, DeckRealtimeResult } from 'ext:flarum/deck/forum/columns/DeckColumnType';
import type Dialog from '../common/models/Dialog';
/**
 * A Deck column of the member's conversations. It shares `app.dialogs` with
 * the messages page, so reading a conversation in one updates the other, and
 * with Realtime on, extendRealtime() already refreshes it as messages arrive.
 */
export default class DialogsDeckSource implements DeckColumnSource {
    /** The newest `lastMessageAt` on screen. */
    protected key: Date | null;
    load(): Promise<unknown>;
    view(): JSX.Element;
    checkForNew(): Promise<number>;
    applyNew(): Promise<number | null>;
    /** extendRealtime() already moves the dialog to the top of `app.dialogs`, which this shares. */
    onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult;
    showNew(): Promise<unknown>;
    /** Conversations with a message since the key, other than the member's own. */
    protected newer(): Promise<Dialog[]>;
    protected snapshot(): void;
}
