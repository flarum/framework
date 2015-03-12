import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';

export default Ember.Component.extend(HasItemLists, {
  layoutName: 'components/user/user-card',
  classNames: ['user-card'],
  attributeBindings: ['style'],
  itemLists: ['controls', 'info'],

  style: Ember.computed('user.color', function() {
    return 'background-color: '+this.get('user.color');
  }),

  bioEditable: Ember.computed.and('user.canEdit', 'editable'),
  showBio: Ember.computed.or('user.bioHtml', 'bioEditable'),

  didInsertElement: function() {
    this.$().on('click', '.user-bio a', function(e) {
      e.stopPropagation();
    });
  },

  actions: {
    editBio: function() {
      if (!this.get('bioEditable')) {
        return;
      }

      this.set('editingBio', true);
      var component = this;
      Ember.run.scheduleOnce('afterRender', this, function() {
        this.$('.user-bio textarea').focus().blur(function() {
          component.send('saveBio', $(this).val());
        });
      });
    },

    saveBio: function(value) {
      var user = this.get('user');
      user.set('bio', value);
      user.save();
      this.set('editingBio', false);
    }
  },

  populateControls: function(items) {
    this.addActionItem(items, 'edit', 'Edit', 'pencil');
    this.addActionItem(items, 'delete', 'Delete', 'times');
  },

  populateInfo: function(items) {
    items.pushObjectWithTag(Ember.Component.extend({
      layout: Ember.Handlebars.compile('{{fa-icon "circle"}} Online')
    }), 'lastActiveTime');

    items.pushObjectWithTag(Ember.Component.extend({
      layout: Ember.Handlebars.compile('Joined {{human-time user.joinTime}}'),
      user: this.get('user')
    }), 'joinTime');
  }
});
