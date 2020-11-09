import { extend, override } from 'flarum/extend';
import IndexPage from 'flarum/components/IndexPage';
import DiscussionComposer from 'flarum/components/DiscussionComposer';

import TagDiscussionModal from './components/TagDiscussionModal';
import tagsLabel from '../common/helpers/tagsLabel';

export default function () {
  extend(IndexPage.prototype, 'newDiscussionAction', function (promise) {
    const tag = app.store.getBy('tags', 'slug', app.search.params().tags);

    if (tag) {
      const parent = tag.parent();
      const tags = parent ? [parent, tag] : [tag];
      promise.then(composer => composer.fields.tags = tags);
    } else {
      app.composer.fields.tags = [];
    }
  });

  // Add tag-selection abilities to the discussion composer.
  DiscussionComposer.prototype.chooseTags = function () {
    app.modal.show(TagDiscussionModal, {
      selectedTags: (this.composer.fields.tags || []).slice(0),
      onsubmit: tags => {
        this.composer.fields.tags = tags;
        this.$('textarea').focus();
      }
    });
  };

  // Add a tag-selection menu to the discussion composer's header, after the
  // title.
  extend(DiscussionComposer.prototype, 'headerItems', function (items) {
    const tags = this.composer.fields.tags || [];

    items.add('tags', (
      <a className="DiscussionComposer-changeTags" onclick={this.chooseTags.bind(this)}>
        {tags.length
          ? tagsLabel(tags)
          : <span className="TagLabel untagged">{app.translator.trans('flarum-tags.forum.composer_discussion.choose_tags_link')}</span>}
      </a>
    ), 10);
  });

  override(DiscussionComposer.prototype, 'onsubmit', function (original) {
    const chosenTags = this.composer.fields.tags || [];
    const chosenPrimaryTags = chosenTags.filter(tag => tag.position() !== null && !tag.isChild());
    const chosenSecondaryTags = chosenTags.filter(tag => tag.position() === null);
    if (!chosenTags.length
      || (chosenPrimaryTags.length < app.forum.attribute('minPrimaryTags'))
      || (chosenSecondaryTags.length < app.forum.attribute('minSecondaryTags'))) {
      app.modal.show(TagDiscussionModal, {
          selectedTags: chosenTags,
          onsubmit: tags => {
            this.composer.fields.tags = tags;
            original();
          }
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
