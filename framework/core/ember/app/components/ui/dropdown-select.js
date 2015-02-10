import Ember from 'ember';

/**
  Button which has an attached dropdown menu containing an item list. The
  currently-active item's label is displayed as the label of the button.
 */
export default Ember.Component.extend({
  layoutName: 'components/ui/dropdown-select',
  classNames: ['dropdown', 'dropdown-select', 'btn-group'],
  classNameBindings: ['itemCountClass', 'class'],

  buttonClass: 'btn btn-default',
  menuClass: '',
  icon: 'ellipsis-v',
  items: [],

  mainButtonClass: Ember.computed('buttonClass', function() {
    return 'btn '+this.get('buttonClass');
  }),

  dropdownMenuClass: Ember.computed('menuClass', function() {
    return 'dropdown-menu '+this.get('menuClass');
  }),

  itemCountClass: Ember.computed('items.length', function() {
    return 'item-count-'+this.get('items.length');
  }),

  activeItem: Ember.computed('menu.childViews.@each.active', function() {
    return this.get('menu.childViews').findBy('active');
  })
});
