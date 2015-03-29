import Ember from 'ember';

import DropdownButton from './dropdown-button';

/**
  Given a list of items, this component displays a split button: the left side
  is the first item in the list, while the right side is a dropdown-toggle
  which shows a dropdown menu containing all of the items.
 */
export default DropdownButton.extend({
  layoutName: 'components/ui/dropdown-split',
  classNames: ['dropdown', 'dropdown-split', 'btn-group'],
  menuClass: 'pull-right',

  mainButtonClass: Ember.computed('buttonClass', function() {
    return 'btn '+this.get('buttonClass');
  }),

  firstItem: Ember.computed('items.[]', function() {
    return this.get('items').objectAt(0);
  })
});
