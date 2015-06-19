import { extend } from 'flarum/extension-utils';
import DiscussionList from 'flarum/components/discussion-list';
import DiscussionPage from 'flarum/components/discussion-page';
import DiscussionHero from 'flarum/components/discussion-hero';

import tagsLabel from 'flarum-tags/helpers/tags-label';
import sortTags from 'flarum-tags/utils/sort-tags';

export default function() {
  // Add tag labels to each discussion in the discussion list.
  extend(DiscussionList.prototype, 'infoItems', function(items, discussion) {
    var tags = discussion.tags();
    if (tags) {
      tags = tags.filter(tag => tag.slug() !== this.props.params.tags);
      if (tags.length) {
        items.add('tags', tagsLabel(tags), {first: true});
      }
    }
  });

  // Include a discussion's tags when fetching it.
  extend(DiscussionPage.prototype, 'params', function(params) {
    params.include.push('tags');
  });

  // Restyle a discussion's hero to use its first tag's color.
  extend(DiscussionHero.prototype, 'view', function(view) {
    var tags = sortTags(this.props.discussion.tags());
    if (tags && tags.length) {
      var color = tags[0].color();
      if (color) {
        view.attrs.style = 'background-color: '+color;
        view.attrs.className += ' discussion-hero-colored';
      }
    }
  });

  // Add a list of a discussion's tags to the discussion hero, displayed
  // before the title. Put the title on its own line.
  extend(DiscussionHero.prototype, 'items', function(items) {
    var tags = this.props.discussion.tags();
    if (tags && tags.length) {
      items.add('tags', tagsLabel(tags, {link: true}), {before: 'title'});

      items.title.content.wrapperClass = 'block-item';
    }
  });
};
