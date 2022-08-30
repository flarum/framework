import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';
import IndexPage from 'flarum/forum/components/IndexPage';
import NotificationGrid from 'flarum/forum/components/NotificationGrid';

import addSubscriptionBadge from './addSubscriptionBadge';
import addSubscriptionControls from './addSubscriptionControls';
import addSubscriptionFilter from './addSubscriptionFilter';
import addSubscriptionSettings from './addSubscriptionSettings';

import NewPostNotification from './components/NewPostNotification';

app.initializers.add('subscriptions', function () {
  app.routes.following = { path: '/following', component: IndexPage };
  app.notificationComponents.newPost = NewPostNotification;

  Discussion.prototype.subscription = Model.attribute('subscription');

  addSubscriptionBadge();
  addSubscriptionControls();
  addSubscriptionFilter();
  addSubscriptionSettings();

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('newPost', {
      name: 'newPost',
      icon: 'fas fa-star',
      label: app.translator.trans('flarum-subscriptions.forum.settings.notify_new_post_label'),
    });
  });
});
