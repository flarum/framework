import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import DropdownButton from 'flarum/components/ui/dropdown-button';
import config from 'flarum/config/environment';

var precompileTemplate = Ember.Handlebars.compile;

export default DropdownButton.extend(HasItemLists, {
  layoutName: 'components/application/user-dropdown',
  itemLists: ['items'],

  buttonClass: 'btn btn-default btn-naked btn-rounded btn-user',
  menuClass: 'pull-right',
  label: Ember.computed.alias('user.username'),

  populateItems: function(items) {
    var self = this;

    items.pushObjectWithTag(Ember.Component.extend({
      tagName: 'li',
      layout: precompileTemplate('{{#link-to "user" user}}{{fa-icon "user"}} Profile{{/link-to}}'),
      user: this.get('user')
    }));

    items.pushObjectWithTag(Ember.Component.extend({
      tagName: 'li',
      layout: precompileTemplate('{{#link-to "user.settings" user}}{{fa-icon "cog"}} Settings{{/link-to}}'),
      user: this.get('user')
    }));

    if (this.get('user.groups').findBy('id', '1')) {
      items.pushObjectWithTag(Ember.Component.extend({
        tagName: 'li',
        layout: precompileTemplate('<a href="'+config.baseURL+'admin" target="_blank">{{fa-icon "wrench"}} Administration</a>')
      }));
    }

    this.addSeparatorItem(items);

    this.addActionItem(items, 'logout', 'Log Out', 'sign-out', null, function() {
      self.get('parentController').send('invalidateSession');
    });
  }
})
