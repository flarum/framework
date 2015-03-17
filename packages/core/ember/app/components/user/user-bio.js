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
      var height = this.$().height();
      Ember.run.scheduleOnce('afterRender', this, function() {
        var save = function(e) {
          if (e.shiftKey) { return; }
          e.preventDefault();
          component.send('save', $(this).val());
        };
        this.$('textarea').css('height', height).focus().bind('blur', save).bind('keydown', 'return', save);
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
