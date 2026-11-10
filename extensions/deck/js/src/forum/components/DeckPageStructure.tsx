import PageStructure, { type PageStructureAttrs } from 'flarum/forum/components/PageStructure';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export interface IDeckPageStructureAttrs extends PageStructureAttrs {
  deck: () => Mithril.Children;
}

/**
 * Core's page layout with the deck taken out of the container: the side nav
 * stays in the usual centred, width-capped container, as on the Tags and
 * Messages pages, and the deck below it gets the full width of the screen.
 */
export default class DeckPageStructure<CustomAttrs extends IDeckPageStructureAttrs = IDeckPageStructureAttrs> extends PageStructure<CustomAttrs> {
  mainItems(): ItemList<Mithril.Children> {
    const items = super.mainItems();

    // Carries the id core's "skip to main content" link points at.
    items.add(
      'deck',
      <div className="DeckPage-deck" id="main-content">
        {this.attrs.deck()}
      </div>,
      5
    );

    return items;
  }

  containerItems(): ItemList<Mithril.Children> {
    const items = super.containerItems();

    items.remove('content');

    return items;
  }
}
