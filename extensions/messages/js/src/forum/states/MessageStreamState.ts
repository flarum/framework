import PaginatedListState, { PaginatedListParams } from 'flarum/common/states/PaginatedListState';
import DialogMessage from '../../common/models/DialogMessage';

export interface MessageStreamParams extends PaginatedListParams {
  //
}

/**
 * The messages of one conversation, newest first, as pages of offsets from
 * the newest end. Opened from a permalink, the pages hold a block part-way
 * through and grow in both directions; otherwise they start at the newest end
 * and grow back.
 */
export default class MessageStreamState<P extends MessageStreamParams = MessageStreamParams> extends PaginatedListState<DialogMessage, P> {
  constructor(params: P, page: number = 1) {
    super(params, page, null);
  }

  get type(): string {
    return 'dialog-messages';
  }

  public getAllItems(): DialogMessage[] {
    return super.getAllItems();
  }

  /** Whether the newest messages are held, rather than a block further back. */
  atNewestEnd(): boolean {
    return this.hasItems() && !this.hasPrev();
  }

  holds(id: string): boolean {
    return this.getAllItems().some((message) => message.id() === id);
  }

  /**
   * Takes in a message just sent to the conversation.
   *
   * Every page held is now one further from the newest end, so the offsets
   * the next load is worked out from move with it. The message itself joins
   * the newest page when that is held; otherwise it arrives with the newer
   * messages when they are loaded, in order. The base push() appended a page
   * of its own, which the next load of older messages then skipped over.
   */
  push(message: DialogMessage): void {
    this.pages.forEach((page) => {
      const meta = 'payload' in page.items ? page.items.payload.meta?.page : undefined;

      if (meta && typeof meta.offset === 'number') meta.offset += 1;
    });

    if (this.atNewestEnd() && !this.holds(message.id()!)) this.pages[0].items.unshift(message);

    m.redraw();
  }

  /**
   * Brings in what arrived while events were not being received, after a
   * reconnect: the newest page is fetched and whatever it holds that this
   * doesn't is taken in, oldest first. When none of it is held already, more
   * than a page arrived meanwhile, and the conversation starts over from its
   * newest end. Away from the newest end nothing is missing from what is
   * held, so there is nothing to fetch.
   */
  catchUp(): Promise<void> {
    if (!this.atNewestEnd()) return Promise.resolve();

    return this.loadPage(1)
      .then((results) => {
        const fresh = results.filter((message) => !this.holds(message.id()!));

        if (fresh.length && fresh.length === results.length) {
          this.pages = [];
          this.parseResults(1, results);

          return;
        }

        fresh.sort((a, b) => a.number() - b.number()).forEach((message) => this.push(message));
      })
      .catch(() => {
        // A background catch-up that failed leaves the conversation as it was.
      });
  }
}
