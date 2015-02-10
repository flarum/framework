import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import ActionButton from 'flarum/components/ui/action-button';

/**
  A text editor. Contains a textarea and an item list of `controls`, including
  a submit button.
 */
export default Ember.Component.extend(HasItemLists, {
  classNames: ['text-editor'],
  itemLists: ['controls'],

  value: '',
  disabled: false,

  didInsertElement: function() {
    var component = this;
    this.$('textarea').bind('keydown', 'meta+return', function() {
      component.send('submit');
    });
  },

  populateControls: function(items) {
    this.addActionItem(items, 'submit', this.get('submitLabel')).set('className', 'btn btn-primary');
  },

  actions: {
    submit: function() {
      this.sendAction('submit', this.get('value'));
    }
  }
});
