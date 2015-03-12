import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';

export default Ember.View.extend(HasItemLists, {
  itemLists: ['sidebar']
});
