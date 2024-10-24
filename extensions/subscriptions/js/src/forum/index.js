import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';

import addSubscriptionBadge from './addSubscriptionBadge';
import addSubscriptionControls from './addSubscriptionControls';
import addSubscriptionFilter from './addSubscriptionFilter';
import addSubscriptionSettings from './addSubscriptionSettings';

export { default as extend } from './extend';

app.initializers.add('flarum-subscriptions', () => {
  addSubscriptionBadge();
  addSubscriptionControls();
  addSubscriptionFilter();
  addSubscriptionSettings();

  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items) {
    items.add('newPost', {
      name: 'newPost',
      icon: 'fas fa-star',
      label: app.translator.trans('flarum-subscriptions.forum.settings.notify_new_post_label'),
    });
  });
});
