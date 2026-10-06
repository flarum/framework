import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';
import type { GlobalSearchSource } from 'flarum/forum/components/GlobalSearch';
import type DialogMessage from '../../common/models/DialogMessage';
import MessageSearchResult from './MessageSearchResult';

/**
 * Finds the member's private messages by what they say. The API only searches
 * the conversations the member is part of, so nobody else's can turn up.
 */
export default class GlobalMessagesSearchSource implements GlobalSearchSource {
  protected results = new Map<string, DialogMessage[]>();

  public resource: string = 'dialog-messages';

  title(): string {
    return extractText(app.translator.trans('flarum-messages.forum.search_source.heading'));
  }

  isCached(query: string): boolean {
    return this.results.has(query.toLowerCase());
  }

  async search(query: string, limit: number): Promise<void> {
    query = query.toLowerCase();

    this.results.set(query, []);

    return app.store
      .find<DialogMessage[]>('dialog-messages', {
        filter: { q: query },
        page: { limit },
        // The conversation's members, to say who it is with.
        include: 'user,dialog.users',
      })
      .then((results) => {
        this.results.set(query, results);
        m.redraw();
      });
  }

  view(query: string): Array<Mithril.Vnode> {
    query = query.toLowerCase();

    return (this.results.get(query) || []).map((message) => <MessageSearchResult message={message} query={query} />) as Array<Mithril.Vnode>;
  }

  customGrouping(): boolean {
    return false;
  }

  /** Messages have no page of their own to list every result on. */
  fullPage(): null {
    return null;
  }

  gotoItem(id: string): string | null {
    const message = app.store.getById<DialogMessage>('dialog-messages', id);
    const dialog = message?.dialog();

    return message && dialog ? app.route.dialog(dialog, message.number()) : null;
  }
}
