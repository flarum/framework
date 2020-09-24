import { extend } from 'flarum/extend';
import DiscussionControls from 'flarum/utils/DiscussionControls';
import DiscussionPage from 'flarum/components/DiscussionPage';
import Button from 'flarum/components/Button';

export default function addStickyControl() {
  extend(DiscussionControls, 'moderationControls', function(items, discussion) {
    if (discussion.canSticky()) {
      items.add('sticky', Button.component({
        icon: 'fas fa-thumbtack',
        onclick: this.stickyAction.bind(discussion)
      }, app.translator.trans(discussion.isSticky() ? 'flarum-sticky.forum.discussion_controls.unsticky_button' : 'flarum-sticky.forum.discussion_controls.sticky_button')));
    }
  });

  DiscussionControls.stickyAction = function() {
    this.save({isSticky: !this.isSticky()}).then(() => {
      if (app.current.matches(DiscussionPage)) {
        app.current.get('stream').update();
      }

      m.redraw();
    });
  };
}
