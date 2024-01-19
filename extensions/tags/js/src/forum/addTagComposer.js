import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import classList from 'flarum/common/utils/classList';

import tagsLabel from '../common/helpers/tagsLabel';
import getSelectableTags from './utils/getSelectableTags';

export default function addTagComposer() {
  extend(IndexSidebar.prototype, 'newDiscussionAction', function (promise) {
    // From `addTagFilter
    const tag = app.currentTag();

    if (tag) {
      const parent = tag.parent();
      const tags = parent ? [parent, tag] : [tag];
      promise.then((composer) => (composer.fields.tags = tags));
    } else {
      app.composer.fields.tags = [];
    }
  });

  extend('flarum/forum/components/DiscussionComposer', 'oninit', function () {
    app.tagList.load(['parent']).then(() => m.redraw());

    // Add tag-selection abilities to the discussion composer.
    this.constructor.prototype.chooseTags = function () {
      const selectableTags = getSelectableTags();

      if (!selectableTags.length) return;

      app.modal.show(() => import('./components/TagDiscussionModal'), {
        selectedTags: (this.composer.fields.tags || []).slice(0),
        onsubmit: (tags) => {
          this.composer.fields.tags = tags;
          this.$('textarea').focus();
        },
      });
    };
  });

  // Add a tag-selection menu to the discussion composer's header, after the
  // title.
  extend('flarum/forum/components/DiscussionComposer', 'headerItems', function (items) {
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

  override('flarum/forum/components/DiscussionComposer', 'onsubmit', function (original) {
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
      app.modal.show(() => import('./components/TagDiscussionModal'), {
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
  extend('flarum/forum/components/DiscussionComposer', 'data', function (data) {
    data.relationships = data.relationships || {};
    data.relationships.tags = this.composer.fields.tags;
  });
}
