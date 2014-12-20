import Ember from 'ember';

export default Ember.View.extend({
	classNames: ['modal', 'fade'],
	templateName: 'login',

	didInsertElement: function() {
		var self = this;
		this.$().modal('show').on('hidden.bs.modal', function() {
			self.get('controller').send('closeModal');
		});

		this.get('controller.session').on('sessionAuthenticationSucceeded', this, this.hide);
	},

	willDestroyElement: function() {
		this.get('controller.session').off('sessionAuthenticationSucceeded', this, this.hide)
	},

	hide: function() {
		this.$().modal('hide');
	},

	actions: {
		close: function() {
			this.$().modal('hide');
		}
	}
});
