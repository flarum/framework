import { extend } from 'flarum/extend';
import Button from 'flarum/components/Button';
import DiscussionPage from 'flarum/components/DiscussionPage';
import DiscussionControls from 'flarum/utils/DiscussionControls';

import SubscriptionMenu from './components/SubscriptionMenu';

export default function addSubscriptionControls() {
  extend(DiscussionControls, 'userControls', function(items, discussion, context) {
    if (app.session.user && !(context instanceof DiscussionPage)) {
      const states = {
        none: {label: app.translator.trans('flarum-subscriptions.forum.discussion_controls.follow_button'), icon: 'fas fa-star', save: 'follow'},
        follow: {label: app.translator.trans('flarum-subscriptions.forum.discussion_controls.unfollow_button'), icon: 'far fa-star', save: null},
        ignore: {label: app.translator.trans('flarum-subscriptions.forum.discussion_controls.unignore_button'), icon: 'fas fa-eye', save: null}
      };

      const subscription = discussion.subscription() || 'none';

      items.add('subscription', Button.component({
        icon: states[subscription].icon,
        onclick: discussion.save.bind(discussion, {subscription: states[subscription].save})
      }, states[subscription].label));
    }
  });

  extend(DiscussionPage.prototype, 'sidebarItems', function(items) {
    if (app.session.user) {
      const discussion = this.discussion;

      items.add('subscription', SubscriptionMenu.component({discussion}));
    }
  });
}
