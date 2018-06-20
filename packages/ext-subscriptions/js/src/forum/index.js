import { extend } from 'flarum/extend';
import app from 'flarum/app';
import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';
import NotificationGrid from 'flarum/components/NotificationGrid';

import addSubscriptionBadge from './addSubscriptionBadge';
import addSubscriptionControls from './addSubscriptionControls';
import addSubscriptionFilter from './addSubscriptionFilter';
import addSubscriptionSettings from './addSubscriptionSettings';

import NewPostNotification from './components/NewPostNotification';

app.initializers.add('subscriptions', function() {
  app.notificationComponents.newPost = NewPostNotification;

  Discussion.prototype.subscription = Model.attribute('subscription');

  addSubscriptionBadge();
  addSubscriptionControls();
  addSubscriptionFilter();
  addSubscriptionSettings();

  extend(NotificationGrid.prototype, 'notificationTypes', function(items) {
    items.add('newPost', {
      name: 'newPost',
      icon: 'fas fa-star',
      label: app.translator.trans('flarum-subscriptions.forum.settings.notify_new_post_label')
    });
  });
});
