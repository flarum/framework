import DropdownButton from './dropdown-button';

export default DropdownButton.extend({
    layoutName: 'components/ui/controls/dropdown-split',
    classNames: ['dropdown', 'dropdown-split', 'btn-group'],
    menuClass: 'pull-right',

    mainButtonClass: function() {
    	return 'btn '+this.get('buttonClass');
    }.property('buttonClass'),

    firstItem: function() {
        return this.get('items').objectAt(0);
    }.property('items.[]')
});
