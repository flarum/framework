import { extend } from 'flarum/extend';
import DiscussionControls from 'flarum/utils/DiscussionControls';
import DiscussionPage from 'flarum/components/DiscussionPage';
import Button from 'flarum/components/Button';

export default function addStickyControl() {
  extend(DiscussionControls, 'moderationControls', function(items, discussion) {
    if (discussion.canSticky()) {
      items.add('sticky', Button.component({
        children: app.trans(discussion.isSticky() ? 'sticky.unsticky' : 'sticky.sticky'),
        icon: 'thumb-tack',
        onclick: this.stickyAction.bind(discussion)
      }));
    }
  });

  DiscussionControls.stickyAction = function() {
    this.save({isSticky: !this.isSticky()}).then(() => {
      if (app.current instanceof DiscussionPage) {
        app.current.stream.update();
      }

      m.redraw();
    });
  };
}
