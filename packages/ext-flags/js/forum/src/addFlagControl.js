import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PostControls from 'flarum/utils/PostControls';
import Button from 'flarum/components/Button';

import FlagPostModal from 'flags/components/FlagPostModal';

export default function() {
  extend(PostControls, 'userControls', function(items, post) {
    if (post.isHidden() || post.contentType() !== 'comment' || !post.canFlag() || post.user() === app.session.user) return;

    items.add('flag',
      <Button icon="flag" onclick={() => app.modal.show(new FlagPostModal({post}))}>Flag</Button>
    );
  });
}
