import { extend } from 'flarum/extend';
import DiscussionControls from 'flarum/utils/DiscussionControls';
import Button from 'flarum/components/Button';

import TagDiscussionModal from './components/TagDiscussionModal';

export default function() {
  // Add a control allowing the discussion to be moved to another category.
  extend(DiscussionControls, 'moderationControls', function(items, discussion) {
    if (discussion.canTag()) {
      items.add('tags', <Button icon="fas fa-tag" onclick={() => app.modal.show(TagDiscussionModal, { discussion })}>
        {app.translator.trans('flarum-tags.forum.discussion_controls.edit_tags_button')}
      </Button>);
    }
  });
}
