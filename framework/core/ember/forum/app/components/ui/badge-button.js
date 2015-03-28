import ActionButton from 'flarum/components/ui/action-button';

export default ActionButton.extend({
  tagName: 'span',
  classNames: ['badge'],
  title: Ember.computed.alias('label'),

  didInsertElement: function() {
    this.$().tooltip();
  }
});
