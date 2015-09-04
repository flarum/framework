import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PostControls from 'flarum/utils/PostControls';
import Button from 'flarum/components/Button';

import ReportPostModal from 'reports/components/ReportPostModal';

export default function() {
  extend(PostControls, 'userControls', function(items, post) {
    if (post.isHidden() || post.contentType() !== 'comment' || !post.canReport() || post.user() === app.session.user) return;

    items.add('report',
      <Button icon="flag" onclick={() => app.modal.show(new ReportPostModal({post}))}>Report</Button>
    );
  });
}
