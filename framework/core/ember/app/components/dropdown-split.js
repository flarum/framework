import Ember from 'ember';

export default Ember.Component.extend({
    items: null, // NamedContainerView/Menu
    layoutName: 'components/dropdown-split',
    classNames: ['dropdown', 'dropdown-split', 'btn-group'],
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
    }.property('items')
});
