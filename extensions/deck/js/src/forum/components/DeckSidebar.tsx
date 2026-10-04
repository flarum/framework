import app from 'flarum/forum/app';
import IndexSidebar, { type IndexSidebarAttrs } from 'flarum/forum/components/IndexSidebar';
import Button from 'flarum/common/components/Button';
import extractText from 'flarum/common/utils/extractText';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export interface IDeckSidebarAttrs extends IndexSidebarAttrs {
  canAddColumn: boolean;
  onaddcolumn: () => void;
}

/**
 * The forum's side nav, with Deck's own primary action in place of starting a
 * discussion, as the Messages page does with sending a message.
 */
export default class DeckSidebar<CustomAttrs extends IDeckSidebarAttrs = IDeckSidebarAttrs> extends IndexSidebar<CustomAttrs> {
  static initAttrs(attrs: IDeckSidebarAttrs) {
    attrs.className = 'DeckPage-nav';
  }

  items(): ItemList<Mithril.Children> {
    const items = super.items();
    const { canAddColumn, onaddcolumn } = this.attrs;

    items.remove('newDiscussion');

    items.add(
      'addColumn',
      <Button
        icon="fas fa-plus"
        className="Button Button--primary IndexPage-newDiscussion DeckPage-addColumn"
        itemClassName="App-primaryControl"
        disabled={!canAddColumn}
        title={canAddColumn ? undefined : extractText(app.translator.trans('flarum-deck.forum.page.column_limit_reached_text'))}
        onclick={() => onaddcolumn()}
      >
        {app.translator.trans('flarum-deck.forum.page.add_column_button')}
      </Button>,
      10
    );

    return items;
  }
}
