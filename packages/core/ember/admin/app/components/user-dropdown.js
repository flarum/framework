import Ember from 'ember';

import HasItemLists from '../mixins/has-item-lists';
import DropdownButton from './ui/dropdown-button';

var precompileTemplate = Ember.Handlebars.compile;

export default DropdownButton.extend(HasItemLists, {
  layoutName: 'components/application/user-dropdown',
  itemLists: ['items'],

  buttonClass: 'btn btn-default btn-naked btn-rounded btn-user',
  menuClass: 'pull-right',
  label: Ember.computed.alias('user.username'),

  populateItems: function(items) {
    var self = this;

    this.addActionItem(items, 'logout', 'Log Out', 'sign-out', null, function() {
      self.get('parentController').send('invalidateSession');
    });
  }
})
