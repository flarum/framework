import { extend } from 'flarum/extension-utils';
import Discussion from 'flarum/models/discussion';
import ActionButton from 'flarum/components/action-button';

import TagDiscussionModal from 'flarum-tags/components/tag-discussion-modal';

export default function() {
  // Add a control allowing the discussion to be moved to another category.
  extend(Discussion.prototype, 'controls', function(items) {
    if (this.canTag()) {
      items.add('tags', ActionButton.component({
        label: 'Edit Tags',
        icon: 'tag',
        onclick: () => app.modal.show(new TagDiscussionModal({ discussion: this }))
      }), {after: 'rename'});
    }
  });
};
