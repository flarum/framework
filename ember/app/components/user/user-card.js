import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import UserBio from 'flarum/components/user/user-bio';

export default Ember.Component.extend(HasItemLists, {
  layoutName: 'components/user/user-card',
  classNames: ['user-card'],
  attributeBindings: ['style'],
  itemLists: ['controls', 'info'],

  style: Ember.computed('user.color', function() {
    return 'background-color: '+this.get('user.color');
  }),

  populateControls: function(items) {
    this.addActionItem(items, 'edit', 'Edit', 'pencil');
    this.addActionItem(items, 'delete', 'Delete', 'times');
  },

  populateInfo: function(items) {
    if (this.get('user.bioHtml') || (this.get('editable') && this.get('user.canEdit'))) {
      items.pushObjectWithTag(UserBio.extend({
        user: this.get('user'),
        editable: this.get('editable'),
        listItemClass: 'block-item'
      }), 'bio');
    }

    items.pushObjectWithTag(Ember.Component.extend({
      layout: Ember.Handlebars.compile('{{fa-icon "circle"}} Online')
    }), 'lastActiveTime');

    items.pushObjectWithTag(Ember.Component.extend({
      layout: Ember.Handlebars.compile('Joined {{human-time user.joinTime}}'),
      user: this.get('user')
    }), 'joinTime');
  }
});
