import { extend, override } from 'flarum/extend';
import IndexPage from 'flarum/components/IndexPage';
import DiscussionComposer from 'flarum/components/DiscussionComposer';

import TagDiscussionModal from 'tags/components/TagDiscussionModal';
import tagsLabel from 'tags/helpers/tagsLabel';

export default function() {
  override(IndexPage.prototype, 'composeNewDiscussion', function(original, deferred) {
    const tag = app.store.getBy('tags', 'slug', this.params().tags);

    app.modal.show(
      new TagDiscussionModal({
        selectedTags: tag ? [tag] : [],
        onsubmit: tags => {
          original(deferred).then(component => component.tags = tags);
        }
      })
    );

    return deferred.promise;
  });

  // Add tag-selection abilities to the discussion composer.
  DiscussionComposer.prototype.tags = [];
  DiscussionComposer.prototype.chooseTags = function() {
    app.modal.show(
      new TagDiscussionModal({
        selectedTags: this.tags.slice(0),
        onsubmit: tags => {
          this.tags = tags;
          this.$('textarea').focus();
        }
      })
    );
  };

  // Add a tag-selection menu to the discussion composer's header, after the
  // title.
  extend(DiscussionComposer.prototype, 'headerItems', function(items) {
    items.add('tags', (
      <a className="DiscussionComposer-changeTags" onclick={this.chooseTags.bind(this)}>
        {tagsLabel(this.tags)}
      </a>
    ), 10);
  });

  // Add the selected tags as data to submit to the server.
  extend(DiscussionComposer.prototype, 'data', function(data) {
    data.relationships = data.relationships || {};
    data.relationships.tags = this.tags;
  });
}
