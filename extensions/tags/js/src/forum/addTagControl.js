import { extend } from 'flarum/common/extend';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import Button from 'flarum/common/components/Button';

export default function addTagControl() {
  // Add a control allowing the discussion to be moved to another category.
  extend(DiscussionControls, 'moderationControls', function (items, discussion) {
    if (discussion.canTag()) {
      items.add(
        'tags',
        <Button icon="fas fa-tag" onclick={() => app.modal.show(() => import('./components/TagDiscussionModal'), { discussion })}>
          {app.translator.trans('flarum-tags.forum.discussion_controls.edit_tags_button')}
        </Button>
      );
    }
  });
}
