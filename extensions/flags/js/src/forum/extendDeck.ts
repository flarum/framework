import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';
import DeckColumns from 'ext:flarum/deck/forum/extenders/DeckColumns';
import FlaggedPostsSource from './FlaggedPostsSource';

/** A Flagged posts column for flarum/deck. Only used while Deck is enabled; see extend.ts. */
export default function extendDeck() {
  return [
    new DeckColumns().add(
      'flarum-flags.flagged',
      {
        icon: 'fas fa-flag',
        label: () => extractText(app.translator.trans('flarum-flags.forum.flagged_posts.title')),
        title: () => extractText(app.translator.trans('flarum-flags.forum.flagged_posts.title')),
        // Hides the column from everyone else; what it lists is up to the server.
        isAvailable: () => !!app.forum.attribute<boolean>('canViewFlags'),
        createSource: () => new FlaggedPostsSource(),
      },
      60
    ),
  ];
}
