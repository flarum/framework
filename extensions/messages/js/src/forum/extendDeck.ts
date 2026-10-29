import app from 'flarum/forum/app';
import DeckColumns from 'ext:flarum/deck/forum/extenders/DeckColumns';
import extractText from 'flarum/common/utils/extractText';
import DialogsDeckSource from './DialogsDeckSource';

/** A Messages column for flarum/deck. Only used while Deck is enabled; see extend.ts. */
export default function extendDeck() {
  return [
    new DeckColumns().add(
      'flarum-messages.dialogs',
      {
        icon: 'far fa-envelope',
        label: () => extractText(app.translator.trans('flarum-messages.forum.dialog_list.title')),
        title: () => extractText(app.translator.trans('flarum-messages.forum.dialog_list.title')),
        isAvailable: () => !!app.session.user,
        createSource: () => new DialogsDeckSource(),
      },
      25
    ),
  ];
}
