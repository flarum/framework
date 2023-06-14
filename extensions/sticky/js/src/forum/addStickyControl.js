import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import Button from 'flarum/common/components/Button';

export default function addStickyControl() {
  extend(DiscussionControls, 'moderationControls', function (items, discussion) {
    if (discussion.canSticky()) {
      items.add(
        'sticky',
        <Button icon="fas fa-thumbtack" onclick={this.stickyAction.bind(discussion)}>
          {app.translator.trans(`flarum-sticky.forum.discussion_controls.${discussion.isSticky() ? 'unsticky' : 'sticky'}_button`)}
        </Button>
      );
    }
  });

  DiscussionControls.stickyAction = function () {
    this.save({ isSticky: !this.isSticky() }).then(() => {
      if (app.current.matches(DiscussionPage)) {
        app.current.get('stream').update();
      }

      m.redraw();
    });
  };
}
