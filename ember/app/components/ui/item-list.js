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
    var instances = [];
    items.forEach(function(item) {
      item = item.create();
      item.set('isListItem', item.constructor.proto().tagName === 'li');
      instances.pushObject(item);
    });
    return instances;
  })
});
