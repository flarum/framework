import Ember from 'ember';

import ComponentItem from '../items/component-item';

export default Ember.Component.extend({
    tagName: 'ul',
    layoutName: 'components/ui/controls/item-list',

    listItems: function() {
        if (!this.get('items')) return [];
        var listItems = [];
        this.get('items').forEach(function(item) {
            if (item.tagName != 'li') {
                item = ComponentItem.extend({component: item});
            }
            listItems.push(item);
        });
        return listItems;
    }.property('items.[]')
});
