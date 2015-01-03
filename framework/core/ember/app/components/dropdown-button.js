import Ember from 'ember';

import MenuItemContainer from '../components/menu-item-container';

export default Ember.Component.extend({
    items: null, // NamedContainerView/Menu
    layoutName: 'components/dropdown-button',
    classNames: ['dropdown', 'btn-group'],
    classNameBindings: ['itemCountClass'],

    title: 'Controls',
    icon: 'ellipsis-v',
    buttonClass: 'btn-default',
    menuClass: 'pull-right',

    dropdownMenuClass: function() {
    	return 'dropdown-menu '+this.get('menuClass');
    }.property('menuClass'),

    itemCountClass: function() {
        return 'item-count-'+this.get('items.length');
    }.property('items'),

    containedItems: function() {
        var contained = [];
        this.get('items').forEach(function(item) {
            if (item.tagName != 'li') {
                contained.push(MenuItemContainer.extend({
                    item: item
                }));
            } else {
                contained.push(item);
            }
        });
        return contained;
    }.property('items.[]')
});
