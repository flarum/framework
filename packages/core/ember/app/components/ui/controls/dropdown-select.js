import Ember from 'ember';

export default Ember.Component.extend({
    items: [],
    layoutName: 'components/ui/controls/dropdown-select',
    classNames: ['dropdown', 'dropdown-select', 'btn-group'],
    classNameBindings: ['itemCountClass', 'class'],

    buttonClass: 'btn btn-default',
    menuClass: '',
    icon: 'ellipsis-v',

    mainButtonClass: function() {
    	return 'btn '+this.get('buttonClass');
    }.property('buttonClass'),

    dropdownMenuClass: function() {
    	return 'dropdown-menu '+this.get('menuClass');
    }.property('menuClass'),

    itemCountClass: function() {
        return 'item-count-'+this.get('items.length');
    }.property('items.length'),

    activeItem: function() {
        return this.get('menu.childViews').findBy('active');
    }.property('menu.childViews.@each.active')
});