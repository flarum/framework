import { extend } from 'flarum/extend';
import DiscussionControls from 'flarum/utils/DiscussionControls';
import DiscussionPage from 'flarum/components/DiscussionPage';
import Button from 'flarum/components/Button';

export default function addLockControl() {
  extend(DiscussionControls, 'moderationControls', function(items, discussion) {
    if (discussion.canLock()) {
      items.add('lock', Button.component({
        children: app.trans(discussion.isLocked() ? 'lock.unlock' : 'lock.lock'),
        icon: 'lock',
        onclick: this.lockAction.bind(discussion)
      }));
    }
  });

  DiscussionControls.lockAction = function() {
    this.save({isLocked: !this.isLocked()}).then(() => {
      if (app.current instanceof DiscussionPage) {
        app.current.stream.update();
      }

      m.redraw();
    });
  };
}
