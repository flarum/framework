import { extend } from 'flarum/extend';
import Button from 'flarum/components/Button';
import DiscussionPage from 'flarum/components/DiscussionPage';
import DiscussionControls from 'flarum/utils/DiscussionControls';

import SubscriptionMenu from 'subscriptions/components/SubscriptionMenu';

export default function addSubscriptionControls() {
  extend(DiscussionControls, 'userControls', function(items, discussion, context) {
    if (app.session.user && !(context instanceof DiscussionPage)) {
      const states = {
        none: {label: app.trans('subscriptions.follow'), icon: 'star', save: 'follow'},
        follow: {label: app.trans('subscriptions.unfollow'), icon: 'star-o', save: false},
        ignore: {label: app.trans('subscriptions.unignore'), icon: 'eye', save: false}
      };

      const subscription = discussion.subscription() || 'none';

      items.add('subscription', Button.component({
        children: states[subscription].label,
        icon: states[subscription].icon,
        onclick: discussion.save.bind(discussion, {subscription: states[subscription].save})
      }));
    }
  });

  extend(DiscussionPage.prototype, 'sidebarItems', function(items) {
    if (app.session.user) {
      const discussion = this.discussion;

      items.add('subscription', SubscriptionMenu.component({discussion}));
    }
  });
}
