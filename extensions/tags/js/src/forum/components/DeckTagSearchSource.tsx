import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';
import highlight from 'flarum/common/helpers/highlight';
import type { GlobalSearchSource } from 'flarum/common/components/AbstractGlobalSearch';
import type Mithril from 'mithril';
import tagIcon from '../../common/helpers/tagIcon';
import sortTags from '../../common/utils/sortTags';
import type Tag from '../../common/models/Tag';

/**
 * Tags for flarum/deck's picker, filtered from the ones already loaded rather
 * than searched for, as the forum payload has every tag the member can see.
 */
export default class DeckTagSearchSource implements GlobalSearchSource {
  public resource = 'tags';

  title(): string {
    return extractText(app.translator.trans('flarum-tags.forum.deck.tag_label'));
  }

  /** Nothing to fetch. */
  isCached(): boolean {
    return true;
  }

  async search(): Promise<void> {}

  view(query: string): Array<Mithril.Vnode> {
    const lower = query.toLowerCase();

    return sortTags(app.store.all<Tag>('tags'))
      .filter((tag) => tag.name().toLowerCase().includes(lower))
      .map((tag) => (
        <li className="DeckTagSearchResult" data-index={'tags' + tag.id()} data-id={tag.id()}>
          <button type="button">
            {tagIcon(tag)}
            <span className="DeckTagSearchResult-name">{highlight(tag.name(), query)}</span>
          </button>
        </li>
      ));
  }

  customGrouping(): boolean {
    return false;
  }

  fullPage(): null {
    return null;
  }

  gotoItem(): null {
    return null;
  }
}
