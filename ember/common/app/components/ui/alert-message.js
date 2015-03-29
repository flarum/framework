import Ember from 'ember';

import HasItemLists from '../../mixins/has-item-lists';
import ActionButton from './action-button';

/**
  An alert message. Has a message, a `controls` item list, and a dismiss
  button.
 */
export default Ember.Component.extend(HasItemLists, {
  layoutName: 'components/ui/alert-message',
  classNames: ['alert'],
  classNameBindings: ['classForType'],
  itemLists: ['controls'],

  message: '',
  type: '',
  dismissable: true,
  buttons: [],

  classForType: Ember.computed('type', function() {
    return 'alert-'+this.get('type');
  }),

  populateControls: function(controls) {
    var component = this;

    this.get('buttons').forEach(function(button) {
      controls.pushObject(ActionButton.extend({
        label: button.label,
        action: function() {
          component.send('dismiss');
          button.action();
        }
      }));
    });

    if (this.get('dismissable')) {
      var dismiss = ActionButton.extend({
        icon: 'times',
        className: 'btn btn-icon btn-link',
        action: function() { component.send('dismiss'); }
      });
      controls.pushObjectWithTag(dismiss, 'dismiss');
    }
  },

  actions: {
    dismiss: function() {
      this.sendAction('dismiss', this);
    }
  }
});
