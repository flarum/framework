import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import LinkButton from 'flarum/common/components/LinkButton';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import type Discussion from 'flarum/common/models/Discussion';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import { registerDefaultColumnTypes } from './columns/deckColumnTypes';
import { canUseDeck, makeColumn, maxColumns, saveColumns, storedColumns } from './utils/deckLayout';

export { default as extend } from './extend';
// The public API for other extensions; see the extender for how to use it.
export { default as DeckColumns } from './extenders/DeckColumns';

app.initializers.add('flarum-deck', () => {
  registerDefaultColumnTypes();

  extend(IndexSidebar.prototype, 'navItems', function (items) {
    if (!canUseDeck()) return;

    items.add(
      'deck',
      <LinkButton href={app.route('deck')} icon="fas fa-table-columns">
        {app.translator.trans('flarum-deck.forum.index.deck_link')}
      </LinkButton>,
      90
    );
  });

  extend(DiscussionControls, 'userControls', function (items: ItemList<Mithril.Children>, discussion: Discussion, context: unknown) {
    if (!canUseDeck() || !(context instanceof DiscussionPage)) return;

    const columns = storedColumns();
    const pinned = columns.some((column) => column.type === 'discussion' && String(column.params.id) === discussion.id());

    if (pinned || columns.length >= maxColumns()) return;

    items.add(
      'addToDeck',
      <Button
        icon="fas fa-table-columns"
        onclick={() =>
          saveColumns([...columns, makeColumn('discussion', { id: discussion.id()! })]).then(() =>
            app.alerts.show({ type: 'success' }, app.translator.trans('flarum-deck.forum.discussion_controls.added_message'))
          )
        }
      >
        {app.translator.trans('flarum-deck.forum.discussion_controls.add_to_deck_button')}
      </Button>,
      -10
    );
  });
});
