import Ember from 'ember';

export default Ember.Mixin.create(Ember.Evented, {
  actions: {
    focus: function() {
      this.trigger('focus');
    }
  }
});
