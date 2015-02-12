import Ember from 'ember';

import TaggedArray from 'flarum/utils/tagged-array';
import ActionButton from 'flarum/components/ui/action-button';
import SeparatorItem from 'flarum/components/ui/separator-item';

export default Ember.Mixin.create({
  itemLists: [],

  initItemLists: Ember.on('didInsertElement', function() {
    var self = this;
    this.get('itemLists').forEach(function(name) {
      self.initItemList(name);
    });
  }),

  initItemList: function(name) {
    this.set(name, this.populateItemList(name));
  },

  populateItemList: function(name) {
    var items = TaggedArray.create();
    this.trigger('populate'+name.charAt(0).toUpperCase()+name.slice(1), items);
    return items;
  },

  addActionItem: function(items, tag, label, icon, conditionProperty, action) {
    if (conditionProperty && !this.get(conditionProperty)) { return; }

    var self = this;
    var item = ActionButton.extend({
      label: label,
      icon: icon,
      action: action || function() {
        self.get('controller').send(tag);
      }
    });

    var itemInstance = item.create();

    items.pushObjectWithTag(itemInstance, tag);

    return itemInstance;
  },

  addSeparatorItem: function(items) {
    var length = items.get('length');
    var last = items.objectAt(length - 1);
    if (last && !(last instanceof SeparatorItem)) {
      items.pushObject(SeparatorItem.create());
    }
  }
});
