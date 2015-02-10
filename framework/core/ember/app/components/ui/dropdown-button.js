import Ember from 'ember';

/**
  Button which has an attached dropdown menu containing an item list.
 */
export default Ember.Component.extend({
  layoutName: 'components/ui/dropdown-button',
  classNames: ['dropdown', 'btn-group'],
  classNameBindings: ['itemCountClass', 'class'],

  label: 'Controls',
  icon: 'ellipsis-v',
  buttonClass: 'btn btn-default',
  menuClass: '',
  items: null,

  dropdownMenuClass: Ember.computed('menuClass', function() {
    return 'dropdown-menu '+this.get('menuClass');
  }),

  itemCountClass: Ember.computed('items.length', function() {
    return 'item-count-'+this.get('items.length');
  }),

  actions: {
    buttonClick: function() {
      this.sendAction('buttonClick');
    }
  }
});
