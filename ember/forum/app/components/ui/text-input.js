import Ember from 'ember';

/**
  An extension of Ember's text field with an option to set up an auto-growing
  text input.
 */
export default Ember.TextField.extend({
  autoGrow: false,

	didInsertElement: function() {
    if (this.get('autoGrow')) {
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
	}
});
