import Ember from 'ember';

export default Ember.Mixin.create({
  focusEventOn: Ember.on('didInsertElement', function() {
    this.get('controller').on('focus', this, this.focus);
  }),

  focusEventOff: Ember.on('willDestroyElement', function() {
    this.get('controller').off('focus', this, this.focus);
  }),

  focus: Ember.on('didInsertElement', function() {
    this.$('input:first:visible:enabled').focus();
  })
});
