import { extend } from 'flarum/extend';
import DiscussionControls from 'flarum/utils/DiscussionControls';
import Button from 'flarum/components/Button';

import TagDiscussionModal from 'tags/components/TagDiscussionModal';

export default function() {
  // Add a control allowing the discussion to be moved to another category.
  extend(DiscussionControls, 'moderationControls', function(items, discussion) {
    if (discussion.canTag()) {
      items.add('tags', Button.component({
        children: app.trans('tags.edit_discussion_tags_link'),
        icon: 'tag',
        onclick: () => app.modal.show(new TagDiscussionModal({discussion}))
      }));
    }
  });
}
