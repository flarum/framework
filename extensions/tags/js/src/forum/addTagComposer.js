import { extend, override } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import classList from 'flarum/common/utils/classList';

import TagDiscussionModal from './components/TagDiscussionModal';
import tagsLabel from '../common/helpers/tagsLabel';
import getSelectableTags from './utils/getSelectableTags';

export default function () {
  extend(IndexPage.prototype, 'newDiscussionAction', function (promise) {
    // From `addTagFilter
    const tag = this.currentTag();

    if (tag) {
      const parent = tag.parent();
      const tags = parent ? [parent, tag] : [tag];
      promise.then((composer) => (composer.fields.tags = tags));
    } else {
      app.composer.fields.tags = [];
    }
  });

  extend(DiscussionComposer.prototype, 'oninit', function () {
    app.tagList.load(['parent']).then(() => m.redraw());
  });

  // Add tag-selection abilities to the discussion composer.
  DiscussionComposer.prototype.chooseTags = function () {
    const selectableTags = getSelectableTags();

    if (!selectableTags.length) return;

    app.modal.show(TagDiscussionModal, {
      selectedTags: (this.composer.fields.tags || []).slice(0),
      onsubmit: (tags) => {
        this.composer.fields.tags = tags;
        this.$('textarea').focus();
      },
    });
  };

  // Add a tag-selection menu to the discussion composer's header, after the
  // title.
  extend(DiscussionComposer.prototype, 'headerItems', function (items) {
    const tags = this.composer.fields.tags || [];
    const selectableTags = getSelectableTags();

    items.add(
      'tags',
      <a className={classList(['DiscussionComposer-changeTags', !selectableTags.length && 'disabled'])} onclick={this.chooseTags.bind(this)}>
        {tags.length ? (
          tagsLabel(tags)
        ) : (
          <span className="TagLabel untagged">{app.translator.trans('flarum-tags.forum.composer_discussion.choose_tags_link')}</span>
        )}
      </a>,
      10
    );
  });

  override(DiscussionComposer.prototype, 'onsubmit', function (original) {
    const chosenTags = this.composer.fields.tags || [];
    const chosenPrimaryTags = chosenTags.filter((tag) => tag.position() !== null && !tag.isChild());
    const chosenSecondaryTags = chosenTags.filter((tag) => tag.position() === null);
    const selectableTags = getSelectableTags();

    const minPrimaryTags = parseInt(app.forum.attribute('minPrimaryTags'));
    const minSecondaryTags = parseInt(app.forum.attribute('minSecondaryTags'));
    const maxPrimaryTags = parseInt(app.forum.attribute('maxPrimaryTags'));
    const maxSecondaryTags = parseInt(app.forum.attribute('maxSecondaryTags'));

    if (
      ((!chosenTags.length && maxPrimaryTags !== 0 && maxSecondaryTags !== 0) ||
        chosenPrimaryTags.length < minPrimaryTags ||
        chosenSecondaryTags.length < minSecondaryTags) &&
      selectableTags.length
    ) {
      app.modal.show(TagDiscussionModal, {
        selectedTags: chosenTags,
        onsubmit: (tags) => {
          this.composer.fields.tags = tags;
          original();
        },
      });
    } else {
      original();
    }
  });

  // Add the selected tags as data to submit to the server.
  extend(DiscussionComposer.prototype, 'data', function (data) {
    data.relationships = data.relationships || {};
    data.relationships.tags = this.composer.fields.tags;
  });
}
