import Ember from 'ember';

/**
  Output a list of components within a <ul>, making sure each one is contained
  in an <li> element.
 */
export default Ember.Component.extend({
  layoutName: 'components/ui/item-list',
  tagName: 'ul',

  listItems: Ember.computed('items.[]', function() {
    var items = this.get('items');
    if (!Ember.isArray(items)) {
      return [];
    }
    items.forEach(function(item) {
      item.set('isListItem', item.get('tagName') === 'li');
    });
    return items;
  })
});
