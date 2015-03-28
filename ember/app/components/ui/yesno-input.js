import Ember from 'ember';

/**
  A toggle switch.
 */
export default Ember.Component.extend({
  layoutName: 'components/ui/yesno-input',
  tagName: 'label',
  classNames: ['yesno-control'],

  toggleState: true,

  didInsertElement: function() {
    var component = this;
    this.$('input').on('change', function() {
      component.get('changed')($(this).prop('checked'), component);
    });
  }
});
