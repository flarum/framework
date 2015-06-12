import { extend, override } from 'flarum/extension-utils';
import IndexPage from 'flarum/components/index-page';
import DiscussionComposer from 'flarum/components/discussion-composer';
import icon from 'flarum/helpers/icon';

import TagDiscussionModal from 'flarum-tags/components/tag-discussion-modal';
import tagsLabel from 'flarum-tags/helpers/tags-label';

export default function() {
  override(IndexPage.prototype, 'composeNewDiscussion', function(original, deferred) {
    var tag = app.store.getBy('tags', 'slug', this.params().tags);

    app.modal.show(
      new TagDiscussionModal({
        selectedTags: tag ? [tag] : [],
        onsubmit: tags => {
          original(deferred).then(component => component.tags(tags));
        }
      })
    );

    return deferred.promise;
  });

  // Add tag-selection abilities to the discussion composer.
  DiscussionComposer.prototype.tags = m.prop([]);
  DiscussionComposer.prototype.chooseTags = function() {
    app.modal.show(
      new TagDiscussionModal({
        selectedTags: this.tags().slice(0),
        onsubmit: tags => {
          this.tags(tags);
          this.$('textarea').focus();
        }
      })
    );
  };

  // Add a tag-selection menu to the discussion composer's header, after the
  // title.
  extend(DiscussionComposer.prototype, 'headerItems', function(items) {
    var tags = this.tags();

    items.add('tags', m('a[href=javascript:;][tabindex=-1].control-change-tags', {onclick: this.chooseTags.bind(this)}, [
      tagsLabel(tags)
    ]));
  });

  // Add the selected tags as data to submit to the server.
  extend(DiscussionComposer.prototype, 'data', function(data) {
    data.links = data.links || {};
    data.links.tags = this.tags();
  });
};
