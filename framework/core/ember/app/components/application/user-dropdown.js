import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import DropdownButton from 'flarum/components/ui/dropdown-button';

export default DropdownButton.extend(HasItemLists, {
  layoutName: 'components/application/user-dropdown',
  itemLists: ['items'],

  buttonClass: 'btn btn-default btn-naked btn-rounded btn-user',
  menuClass: 'pull-right',
  label: Ember.computed.alias('user.username'),

  populateItems: function(items) {
    var self = this;
    this.addActionItem(items, 'profile', 'Profile', 'user');
    this.addActionItem(items, 'settings', 'Settings', 'cog');
    this.addSeparatorItem(items);
    this.addActionItem(items, 'logout', 'Log Out', 'sign-out', null, function() {
      self.sendAction('logout');
    });
  }
})
