import Ember from 'ember';

import DropdownButton from 'flarum-forum/components/ui/dropdown-button';

var precompileTemplate = Ember.Handlebars.compile;

export default DropdownButton.extend({
  layoutName: 'components/application/user-notifications',
  classNames: ['notifications'],
  classNameBindings: ['unread'],

  buttonClass: 'btn btn-default btn-rounded btn-naked btn-icon',
  menuClass: 'pull-right',

  unread: Ember.computed.bool('user.unreadNotificationsCount'),

  actions: {
    buttonClick: function() {
      if (!this.get('notifications')) {
        var component = this;
        this.set('notificationsLoading', true);
        this.get('parentController.store').find('notification').then(function(notifications) {
          component.set('user.unreadNotificationsCount', 0);
          component.set('notifications', notifications);
          component.set('notificationsLoading', false);
        });
      }
    },

    markAllAsRead: function() {
      this.get('notifications').forEach(function(notification) {
        if (!notification.get('isRead')) {
          notification.set('isRead', true);
          notification.save();
        }
      })
    },
  }
})
