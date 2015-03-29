import Ember from 'ember';

import TaggedArray from '../utils/tagged-array';
import ActionButton from '../components/ui/action-button';
import SeparatorItem from '../components/ui/separator-item';

export default Ember.Mixin.create({
  itemLists: [],

  initItemLists: Ember.on('init', function() {
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
    this.removeUnneededSeparatorItems(items);
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

    items.pushObjectWithTag(item, tag);

    return item;
  },

  addSeparatorItem: function(items) {
    items.pushObject(SeparatorItem);
  },

  removeUnneededSeparatorItems: function(items) {
    var prevItem = null;
    items.forEach(function(item) {
      if (prevItem === SeparatorItem && item === SeparatorItem) {
        items.removeObject(item);
        return;
      }
      prevItem = item;
    });
    if (prevItem === SeparatorItem) {
      items.removeObject(prevItem);
    }
  }
});
