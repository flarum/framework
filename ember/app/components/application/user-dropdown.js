import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import DropdownButton from 'flarum/components/ui/dropdown-button';
import SeparatorItem from 'flarum/components/ui/separator-item';

export default DropdownButton.extend(HasItemLists, {
  layoutName: 'components/application/user-dropdown',
  itemLists: ['items'],

  buttonClass: 'btn btn-default btn-naked btn-rounded btn-user',
  menuClass: 'pull-right',
  label: Ember.computed.alias('user.username'),

  populateItems: function(items) {
    this.addActionItem(items, 'profile', 'Profile', 'user');
    this.addActionItem(items, 'settings', 'Settings', 'cog');
    items.pushObject(SeparatorItem.create());
    this.addActionItem(items, 'logout', 'Log Out', 'sign-out', null, null, this);
  },

  actions: {
    logout: function() {
      this.logout();
    }
  }
})
