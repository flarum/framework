import Ember from 'ember';

export default Ember.Mixin.create({
	focusEventOn: function() {
		this.get('controller').on('focus', this, this.focus);
	}.on('didInsertElement'),

	focusEventOff: function() {
		this.get('controller').off('focus', this, this.focus);
	}.on('willDestroyElement'),

	focus: function() {
		this.$('input:first:visible:enabled').focus();
		console.log('focus first')
	}.on('didInsertElement')
});
