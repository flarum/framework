import app from 'flarum/forum/app';
import DeckColumns from 'ext:flarum/deck/forum/extenders/DeckColumns';
import DiscussionListSource from 'ext:flarum/deck/forum/columns/DiscussionListSource';
import extractText from 'flarum/common/utils/extractText';
import DeckTagSearchSource from './components/DeckTagSearchSource';
import tagIcon from '../common/helpers/tagIcon';
import type Tag from '../common/models/Tag';

/** A Tag column for flarum/deck. Only used while Deck is enabled; see extend.ts. */
export default function extendDeck() {
  return [
    new DeckColumns().add(
      'flarum-tags.tag',
      {
        icon: 'fas fa-tag',
        label: () => extractText(app.translator.trans('flarum-tags.forum.deck.column_label')),
        title: (config) => app.store.getBy<Tag>('tags', 'slug', String(config.params.slug))?.name() ?? String(config.params.slug),
        isAvailable: () => true,
        fields: () => [
          {
            key: 'tag',
            label: app.translator.trans('flarum-tags.forum.deck.tag_label'),
            placeholder: extractText(app.translator.trans('flarum-tags.forum.deck.tag_placeholder')),
            search: {
              source: () => new DeckTagSearchSource(),
              browse: true,
              display: (tag: Tag) => [tagIcon(tag), ' ', tag.name()],
              label: (tag: Tag) => tag.name(),
              params: (tag: Tag) => ({ slug: tag.slug() }),
            },
          },
        ],
        // `tags` is the param this extension's DiscussionListState hook reads.
        createSource: (config) => new DiscussionListSource({ tags: String(config.params.slug) } as any),
      },
      70
    ),
  ];
}
