import Ember from 'ember';

export default Ember.Component.extend({
  layoutName: 'components/user/user-bio',
  classNames: ['user-bio'],
  classNameBindings: ['isEditable:editable', 'editing'],

  isEditable: Ember.computed.and('user.canEdit', 'editable'),
  editing: false,

  didInsertElement: function() {
    this.$().on('click', 'a', function(e) {
      e.stopPropagation();
    });
  },

  click: function() {
    this.send('edit');
  },

  actions: {
    edit: function() {
      if (!this.get('isEditable')) { return; }

      this.set('editing', true);
      var component = this;
      Ember.run.scheduleOnce('afterRender', this, function() {
        this.$('textarea').focus().blur(function() {
          component.send('save', $(this).val());
        });
      });
    },

    save: function(value) {
      this.set('editing', false);

      var user = this.get('user');
      user.set('bio', value);
      user.save();
    }
  }
});
