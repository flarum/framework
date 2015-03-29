import Ember from 'ember';

export default Ember.Mixin.create({
  activate: function() {
    var cssClass = this.toCssClass();
    Ember.$('body').addClass(cssClass);
  },

  deactivate: function() {
    Ember.$('body').removeClass(this.toCssClass());
  },

  toCssClass: function() {
    return this.routeName.replace(/\./g, '-').dasherize();
  }
});