import Ember from 'ember';

import Menu from '../utils/menu';

export default Ember.Component.extend({
    items: [],
    layoutName: 'components/dropdown-select',
    classNames: ['dropdown', 'dropdown-select', 'btn-group'],
    classNameBindings: ['itemCountClass'],

    buttonClass: 'btn-default',
    menuClass: 'pull-right',
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

    currentItem: function() {
        return this.get('menu.childViews').findBy('active');
    }.property('menu.childViews.@each.active')
});
