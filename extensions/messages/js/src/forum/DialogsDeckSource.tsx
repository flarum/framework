import app from 'flarum/forum/app';
import type { DeckColumnSource, DeckRealtimeEvent, DeckRealtimeResult } from 'ext:flarum/deck/forum/columns/DeckColumnType';
import DialogList from './components/DialogList';
import type Dialog from '../common/models/Dialog';

/**
 * A Deck column of the member's conversations. It shares `app.dialogs` with
 * the messages page, so reading a conversation in one updates the other, and
 * with Realtime on, extendRealtime() already refreshes it as messages arrive.
 */
export default class DialogsDeckSource implements DeckColumnSource {
  /** The newest `lastMessageAt` on screen. */
  protected key: Date | null = null;

  load(): Promise<unknown> {
    return (app.dialogs.hasItems() ? Promise.resolve() : app.dialogs.refresh()).then(() => this.snapshot());
  }

  view() {
    return <DialogList state={app.dialogs} itemActions={true} />;
  }

  async checkForNew(): Promise<number> {
    return (await this.newer()).length;
  }

  async applyNew(): Promise<number | null> {
    const count = (await this.newer()).length;

    if (count) await app.dialogs.revalidate();
    this.snapshot();

    return count;
  }

  /** extendRealtime() already moves the dialog to the top of `app.dialogs`, which this shares. */
  onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult {
    return event.name === 'Flarum\\Messages\\DialogMessage\\Event\\Created' ? 'updated' : undefined;
  }

  showNew(): Promise<unknown> {
    return app.dialogs.revalidate().then(() => this.snapshot());
  }

  /** Conversations with a message since the key, other than the member's own. */
  protected async newer(): Promise<Dialog[]> {
    const latest = await app.store.find<Dialog[]>('dialogs', {
      sort: '-lastMessageAt',
      include: 'lastMessage,lastMessage.user',
      page: { limit: 5, total: 0 },
    });

    return latest.filter((dialog) => {
      const at = dialog.lastMessageAt();

      const lastMessage = dialog.lastMessage();

      return at && (!this.key || at > this.key) && !(lastMessage && lastMessage.user() === app.session.user);
    });
  }

  protected snapshot(): void {
    this.key = null;

    app.dialogs.getAllItems().forEach((dialog: Dialog) => {
      const at = dialog.lastMessageAt();

      if (at && (!this.key || at > this.key)) this.key = at;
    });
  }
}
