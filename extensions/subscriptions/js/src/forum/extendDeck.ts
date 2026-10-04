import app from 'flarum/forum/app';
import DeckColumns from 'ext:flarum/deck/forum/extenders/DeckColumns';
import DiscussionListSource from 'ext:flarum/deck/forum/columns/DiscussionListSource';
import extractText from 'flarum/common/utils/extractText';

/** A Following column for flarum/deck. Only used while Deck is enabled; see extend.ts. */
export default function extendDeck() {
  return [
    new DeckColumns().add(
      'flarum-subscriptions.following',
      {
        icon: 'fas fa-star',
        label: () => extractText(app.translator.trans('flarum-subscriptions.forum.index.following_link')),
        title: () => extractText(app.translator.trans('flarum-subscriptions.forum.index.following_link')),
        isAvailable: () => !!app.session.user,
        createSource: () => new DiscussionListSource({ filter: { subscription: 'following' } }),
      },
      100
    ),
  ];
}
