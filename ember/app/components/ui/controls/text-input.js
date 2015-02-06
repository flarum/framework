import Ember from 'ember';

export default Ember.TextField.extend({
	didInsertElement: function() {
		var component = this;
		this.$().on('input', function() {
			var empty = !$(this).val();
			if (empty) {
				$(this).val(component.get('placeholder'));
			}
			$(this).css('width', 0);
			$(this).width($(this)[0].scrollWidth);
			if (empty) {
				$(this).val('');
			}
		});
	}
});
