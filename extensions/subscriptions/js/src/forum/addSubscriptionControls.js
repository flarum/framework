import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';

import SubscriptionMenu from './components/SubscriptionMenu';

export default function addSubscriptionControls() {
  extend(DiscussionControls, 'userControls', function (items, discussion, context) {
    if (app.session.user && !(context instanceof DiscussionPage)) {
      const states = {
        none: {
          label: app.translator.trans('flarum-subscriptions.forum.discussion_controls.follow_button'),
          icon: <Icon name="fa-solid fa-star" className="Button-icon" noStyleOverride />,
          save: 'follow',
        },
        follow: {
          label: app.translator.trans('flarum-subscriptions.forum.discussion_controls.unfollow_button'),
          icon: <Icon name="fa-regular fa-star" className="Button-icon" noStyleOverride />,
          save: null,
        },
        ignore: { label: app.translator.trans('flarum-subscriptions.forum.discussion_controls.unignore_button'), icon: 'fas fa-eye', save: null },
      };

      const subscription = discussion.subscription() || 'none';

      items.add(
        'subscription',
        <Button icon={states[subscription].icon} onclick={discussion.save.bind(discussion, { subscription: states[subscription].save })}>
          {states[subscription].label}
        </Button>
      );
    }
  });

  extend(DiscussionPage.prototype, 'sidebarItems', function (items) {
    if (app.session.user) {
      const discussion = this.discussion;

      items.add('subscription', <SubscriptionMenu discussion={discussion} />, 80);
    }
  });
}
