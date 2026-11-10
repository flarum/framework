import IndexSidebar, { type IndexSidebarAttrs } from 'flarum/forum/components/IndexSidebar';

/**
 * The forum's side nav, starting a discussion included: Deck can be all
 * someone uses, so it's the one place they shouldn't have to leave to post.
 * Adding a column is the deck's own toolbar action.
 */
export default class DeckSidebar<CustomAttrs extends IndexSidebarAttrs = IndexSidebarAttrs> extends IndexSidebar<CustomAttrs> {
  static initAttrs(attrs: IndexSidebarAttrs) {
    attrs.className = 'DeckPage-nav';
  }
}
