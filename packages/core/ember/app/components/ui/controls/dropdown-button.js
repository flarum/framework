import Ember from 'ember';

export default Ember.Component.extend({
    items: null, // TaggedArray
    layoutName: 'components/ui/controls/dropdown-button',
    classNames: ['dropdown', 'btn-group'],
    classNameBindings: ['itemCountClass', 'class'],

    label: 'Controls',
    icon: 'ellipsis-v',
    buttonClass: 'btn btn-default',
    menuClass: '',

    dropdownMenuClass: function() {
    	return 'dropdown-menu '+this.get('menuClass');
    }.property('menuClass'),

    itemCountClass: function() {
        return 'item-count-'+this.get('items.length');
    }.property('items.length'),

    actions: {
        buttonClick: function() {
            this.sendAction('buttonClick');
        }
    }
});
