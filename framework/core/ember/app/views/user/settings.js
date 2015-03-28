import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import NotificationGrid from 'flarum/components/user/notification-grid';
import FieldSet from 'flarum/components/ui/field-set';
import ActionButton from 'flarum/components/ui/action-button';
import SwitchInput from 'flarum/components/ui/switch-input';

export default Ember.View.extend(HasItemLists, {
  itemLists: ['settings'],
  classNames: ['settings'],

  populateSettings: function(items) {
    items.pushObjectWithTag(FieldSet.extend({
      label: 'Account',
      className: 'settings-account',
      fields: this.populateItemList('account')
    }), 'account');

    items.pushObjectWithTag(FieldSet.extend({
      label: 'Notifications',
      fields: [NotificationGrid.extend({
        notificationTypes: this.populateItemList('notificationTypes'),
        user: this.get('controller.model')
      })]
    }), 'notifications');

    items.pushObjectWithTag(FieldSet.extend({
      label: 'Privacy',
      fields: this.populateItemList('privacy')
    }), 'privacy');
  },

  populateAccount: function(items) {
    items.pushObjectWithTag(ActionButton.extend({
      label: 'Change Password',
      className: 'btn btn-default'
    }), 'changePassword');

    items.pushObjectWithTag(ActionButton.extend({
      label: 'Change Email',
      className: 'btn btn-default'
    }), 'changeEmail');

    items.pushObjectWithTag(ActionButton.extend({
      label: 'Delete Account',
      className: 'btn btn-default btn-danger'
    }), 'deleteAccount');
  },

  updateSetting: function(key) {
    var controller = this.get('controller');
    return function(value, component) {
      component.set('loading', true);
      var user = controller.get('model');
      user.set(key, value).save().then(function() {
        component.set('loading', false);
      });
    };
  },

  populatePrivacy: function(items) {
    var self = this;

    items.pushObjectWithTag(SwitchInput.extend({
      label: 'Allow others to see when I am online',
      parentController: this.get('controller'),
      toggleState: Ember.computed.alias('parentController.model.preferences.discloseOnline'),
      changed: function(value, component) {
        self.set('controller.model.lastSeenTime', null);
        self.updateSetting('preferences.discloseOnline')(value, component);
      }
    }), 'discloseOnline');

    items.pushObjectWithTag(SwitchInput.extend({
      label: 'Allow search engines to index my profile',
      parentController: this.get('controller'),
      toggleState: Ember.computed.alias('parentController.model.preferences.indexProfile'),
      changed: this.updateSetting('preferences.indexProfile')
    }), 'indexProfile');
  },

  populateNotificationTypes: function(items) {
    items.pushObjectWithTag({
      name: 'discussionRenamed',
      label: 'Someone renames a discussion I started'
    }, 'discussionRenamed');
  }
});
