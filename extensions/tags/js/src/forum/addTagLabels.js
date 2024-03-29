import { extend } from 'flarum/common/extend';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import DiscussionHero from 'flarum/forum/components/DiscussionHero';
import textContrastClass from 'flarum/common/helpers/textContrastClass';
import classList from 'flarum/common/utils/classList';

import tagsLabel from '../common/helpers/tagsLabel';
import sortTags from '../common/utils/sortTags';

export default function addTagLabels() {
  // Add tag labels to each discussion in the discussion list.
  extend(DiscussionListItem.prototype, 'infoItems', function (items) {
    const tags = this.attrs.discussion.tags();

    if (tags && tags.length) {
      items.add('tags', tagsLabel(tags), 10);
    }
  });

  // Restyle a discussion's hero to use its first tag's color.
  extend(DiscussionHero.prototype, 'view', function (view) {
    const tags = sortTags(this.attrs.discussion.tags());

    if (tags && tags.length) {
      const color = tags[0].color();
      if (color) {
        view.attrs.style = { '--hero-bg': color };
        view.attrs.className = classList(view.attrs.className, 'DiscussionHero--colored', textContrastClass(color));
      }
    }
  });

  // Add a list of a discussion's tags to the discussion hero, displayed
  // before the title. Put the title on its own line.
  extend(DiscussionHero.prototype, 'items', function (items) {
    const tags = this.attrs.discussion.tags();

    if (tags && tags.length) {
      items.add('tags', tagsLabel(tags, { link: true }), 5);
    }
  });
}
