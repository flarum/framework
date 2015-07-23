import { extend } from 'flarum/extend';
import app from 'flarum/app';
import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';
import NotificationGrid from 'flarum/components/NotificationGrid';

import addSubscriptionBadge from 'subscriptions/addSubscriptionBadge';
import addSubscriptionControls from 'subscriptions/addSubscriptionControls';
import addSubscriptionFilter from 'subscriptions/addSubscriptionFilter';
import NewPostNotification from 'subscriptions/components/NewPostNotification';

app.initializers.add('subscriptions', function() {
  app.notificationComponents.newPost = NewPostNotification;

  Discussion.prototype.subscription = Model.attribute('subscription');

  addSubscriptionBadge();
  addSubscriptionControls();
  addSubscriptionFilter();

  extend(NotificationGrid.prototype, 'notificationTypes', function(items) {
    items.add('newPost', {
      name: 'newPost',
      icon: 'star',
      label: app.trans('subscriptions.notify_new_post')
    });
  });
});
